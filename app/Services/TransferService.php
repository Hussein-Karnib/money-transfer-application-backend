<?php

namespace App\Services;

use App\Models\Transfer;
use App\Models\Transfer_Event;
use App\Models\Transfer_Fee;
use App\Models\Beneficiary;
use App\Models\User;
use App\Models\User_Verification;
use App\Services\ExchangeRateService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransferService
{
    public function __construct(
        private ExchangeRateService $exchangeRateService
    ) {}

   
    public function calculateFee(float $amount, int $countryFromId, int $countryToId): float
    {
        // Validate amount
        if ($amount <= 0) {
            throw new \Exception('Transfer amount must be greater than 0');
        }

        // Find matching fee rule
        $feeRule = Transfer_Fee::where('country_from_id', $countryFromId)
            ->where('country_to_id', $countryToId)
            ->where('min_amount', '<=', $amount)
            ->where('max_amount', '>=', $amount)
            ->first();

        if (!$feeRule) {
            // Default fee: 2% of amount with minimum $5
            Log::info("No fee rule found for countries {$countryFromId} -> {$countryToId}, using default fee");
            return max($amount * 0.02, 5.0);
        }

        // Calculate fee: fixed + percentage
        $fee = $feeRule->fee_fixed ?? 0;
        $fee += ($amount * ($feeRule->fee_percent ?? 0) / 100);

        return round($fee, 2);
    }

   
    public function createTransfer(array $data): Transfer
    {
        return DB::transaction(function () use ($data) {
            // Validate required fields
            $requiredFields = ['sender_id', 'beneficiary_id', 'amount', 'currency_from', 'currency_to'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    throw new \Exception("Missing required field: {$field}");
                }
            }

            // Validate amount
            $amount = (float) $data['amount'];
            if ($amount <= 0) {
                throw new \Exception('Transfer amount must be greater than 0');
            }

            // Validate minimum amount (e.g., $1)
            if ($amount < 1) {
                throw new \Exception('Transfer amount must be at least 1');
            }

            // Validate maximum amount (e.g., $100,000)
            if ($amount > 100000) {
                throw new \Exception('Transfer amount exceeds maximum limit of 100,000');
            }

            // Validate beneficiary exists and belongs to sender
            $beneficiary = Beneficiary::where('id', $data['beneficiary_id'])
                ->where('user_id', $data['sender_id'])
                ->first();

            if (!$beneficiary) {
                throw new \Exception('Beneficiary not found or does not belong to you');
            }

            // Validate sender exists
            $sender = User::find($data['sender_id']);
            if (!$sender) {
                throw new \Exception('Sender not found');
            }

            // Validate currencies are different (optional, but usually transfers are cross-currency)
            if ($data['currency_from'] === $data['currency_to']) {
                // Allow same currency transfers, but log it
                Log::info("Same currency transfer: {$data['currency_from']}");
            }

            // Get exchange rate
            $exchangeRate = $this->exchangeRateService->getRate(
                $data['currency_from'],
                $data['currency_to']
            );

            if ($exchangeRate === null) {
                throw new \Exception('Unable to fetch exchange rate. Please try again later.');
            }

            // Get sender's country
            // Priority: 1. User verification, 2. Default country (ID: 1)
            $senderCountryId = $this->getSenderCountryId($data['sender_id']);

            // Calculate fee
            $fee = $this->calculateFee(
                $amount,
                $senderCountryId,
                $beneficiary->country_id
            );

            // Calculate total amount (amount + fee)
            $totalAmount = $amount + $fee;

            // Generate unique reference code
            $reference = $this->generateReference();

            // Create transfer
            $transfer = Transfer::create([
                'sender_id' => $data['sender_id'],
                'beneficiary_id' => $data['beneficiary_id'],
                'amount' => $amount,
                'currency_from' => strtoupper($data['currency_from']),
                'currency_to' => strtoupper($data['currency_to']),
                'exchange_rate' => $exchangeRate,
                'fee' => $fee,
                'total_amount' => $totalAmount,
                'status' => 'queued',
                'initiated_at' => now(),
                'reference' => $reference,
            ]);

            // Create initial transfer event
            $this->createEvent(
                $transfer->id,
                'queued',
                'Transfer initiated successfully',
                'user',
                $data['sender_id']
            );

            Log::info("Transfer created", [
                'transfer_id' => $transfer->id,
                'reference' => $reference,
                'sender_id' => $data['sender_id'],
                'amount' => $amount,
            ]);

            return $transfer->fresh();
        });
    }

  
    private function getSenderCountryId(int $userId): int
    {
        // Try to get from user's verification
        // Note: This assumes verification has country info, which may need to be added
        $verification = User_Verification::where('user_id', $userId)
            ->where('status', 'approved')
            ->latest()
            ->first();

        // For now, return default country ID (1)
        // In production, you would extract country from verification documents
        // or store it in the users table
        return 1; // Default country ID
    }

    public function updateStatus(int $transferId, string $status, ?string $note = null, ?string $actorType = 'system', ?int $actorId = null): Transfer
    {
        $transfer = Transfer::findOrFail($transferId);

        // Validate status
        $validStatuses = ['queued', 'paid', 'in_progress', 'available_for_pickup', 'completed', 'failed', 'refunded', 'disputed'];
        if (!in_array($status, $validStatuses)) {
            throw new \Exception("Invalid status: {$status}");
        }

        // Validate status transition (basic validation)
        $currentStatus = $transfer->status;
        if ($currentStatus === 'completed' && $status !== 'refunded' && $status !== 'disputed') {
            throw new \Exception('Cannot change status of completed transfer');
        }

        if ($currentStatus === 'refunded') {
            throw new \Exception('Cannot change status of refunded transfer');
        }

        // Update transfer
        $transfer->update([
            'status' => $status,
            'completed_at' => in_array($status, ['completed', 'failed', 'refunded']) ? now() : null,
        ]);

        // Create event
        $this->createEvent($transferId, $status, $note, $actorType, $actorId);

        Log::info("Transfer status updated", [
            'transfer_id' => $transferId,
            'from' => $currentStatus,
            'to' => $status,
            'actor' => $actorType,
        ]);

        return $transfer->fresh();
    }

    public function createEvent(int $transferId, string $status, ?string $note = null, ?string $actorType = 'system', ?int $actorId = null): Transfer_Event
    {
        return Transfer_Event::create([
            'transfer_id' => $transferId,
            'status' => $status,
            'note' => $note ?? "Status changed to {$status}",
            'actor_type' => $actorType,
            'actor_id' => $actorId,
        ]);
    }

 
    private function generateReference(): string
    {
        $maxAttempts = 10;
        $attempts = 0;

        do {
            $reference = 'TRF' . strtoupper(Str::random(10));
            $attempts++;
            
            if ($attempts >= $maxAttempts) {
                throw new \Exception('Failed to generate unique reference code');
            }
        } while (Transfer::where('reference', $reference)->exists());

        return $reference;
    }


    public function cancelTransfer(int $transferId, int $userId): Transfer
    {
        $transfer = Transfer::where('id', $transferId)
            ->where('sender_id', $userId)
            ->firstOrFail();

        // Only allow cancellation if transfer is queued or paid
        if (!in_array($transfer->status, ['queued', 'paid'])) {
            throw new \Exception("Transfer cannot be cancelled. Current status: {$transfer->status}");
        }

        // If payment was made, it should be refunded first
        if ($transfer->status === 'paid' && $transfer->payment) {
            // Note: In production, you might want to automatically refund the payment
            Log::warning("Cancelling transfer with payment", [
                'transfer_id' => $transferId,
                'payment_id' => $transfer->payment->id,
            ]);
        }

        return $this->updateStatus($transferId, 'failed', 'Transfer cancelled by user', 'user', $userId);
    }


    public function processRefund(int $transferId, int $adminId): Transfer
    {
        $transfer = Transfer::findOrFail($transferId);

        // Only completed transfers can be refunded
        if ($transfer->status !== 'completed') {
            throw new \Exception("Only completed transfers can be refunded. Current status: {$transfer->status}");
        }

        // Check if payment exists and needs to be refunded
        if ($transfer->payment && !in_array($transfer->payment->status, ['refunded'])) {
            Log::info("Refunding transfer payment", [
                'transfer_id' => $transferId,
                'payment_id' => $transfer->payment->id,
            ]);
            // Note: In production, you would call PaymentService to refund the payment
        }

        return $this->updateStatus($transferId, 'refunded', 'Transfer refunded by admin', 'admin', $adminId);
    }
}

