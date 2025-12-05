<?php

namespace App\Services;
use App\Notifications\TransferStatusUpdated;
use App\Models\Transfer;
use App\Models\Transfer_Event;
use App\Models\Transfer_Fee;
use App\Models\Promotion;
use App\Models\Beneficiary;
use App\Models\User;
use App\Models\UserVerification;
use App\Services\ExchangeRateService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransferService
{

    
    public function __construct(
        private ExchangeRateService $exchangeRateService
    ) {}

    public function getSpeedProfiles(): array
    {
        return [
            'instant' => [
                'label'          => 'Instant',
                'minutes'        => 15,
                'fee_multiplier' => 1.5,
                'eta_text'       => 'Within 15 mins',
            ],
            'express' => [
                'label'          => 'Express',
                'minutes'        => 120,
                'fee_multiplier' => 1.25,
                'eta_text'       => 'In about 2 hours',
            ],
            'same_day' => [
                'label'          => 'Same Day',
                'minutes'        => 360,
                'fee_multiplier' => 1.15,
                'eta_text'       => 'Arrives today',
            ],
            'standard' => [
                'label'          => 'Standard',
                'minutes'        => 1440,
                'fee_multiplier' => 1.0,
                'eta_text'       => 'By tomorrow',
            ],
        ];
    }

    public function resolveSpeedProfile(string $speed): array
    {
        $profiles = $this->getSpeedProfiles();

        return $profiles[$speed] ?? $profiles['standard'];
    }

    public function calculateFee(float $amount, int $countryFromId, int $countryToId, string $speed = 'standard'): float
    {
        if ($amount <= 0) {
            throw new \Exception('Transfer amount must be greater than 0');
        }

        $speedProfile = $this->resolveSpeedProfile($speed);

        $feeRule = Transfer_Fee::where('country_from_id', $countryFromId)
            ->where('country_to_id', $countryToId)
            ->where('min_amount', '<=', $amount)
            ->where('max_amount', '>=', $amount)
            ->first();

        if (!$feeRule) {
            $baseFee = max($amount * 0.02, 5.0);
            Log::info("No fee rule found for countries {$countryFromId} -> {$countryToId}, using default fee");
        } else {
            $baseFee = ($feeRule->fee_fixed ?? 0) + ($amount * ($feeRule->fee_percent ?? 0) / 100);
        }

        $adjustedFee = $baseFee * $speedProfile['fee_multiplier'];

        return round($adjustedFee, 2);
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

            // Validate currencies are different 
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

            // Calculate fee (allow override from caller)
            if (array_key_exists('fee', $data)) {
                $fee = (float) $data['fee'];
            } else {
                $fee = $this->calculateFee(
                    $amount,
                    $senderCountryId,
                    $beneficiary->country_id,
                    $data['speed'] ?? 'standard'
                );
            }

            // Handle optional promotion / discount
            $promotionId = $data['promotion_id'] ?? null;
            $discountAmount = isset($data['discount_amount']) ? (float) $data['discount_amount'] : 0.0;

            if ($discountAmount < 0) {
                throw new \Exception('Discount amount cannot be negative');
            }

            // Re-validate discount does not exceed amount + fee
            $maxDiscount = $amount + $fee;
            if ($discountAmount > $maxDiscount) {
                throw new \Exception('Discount amount is too large for this transfer');
            }

            // Calculate total amount (amount + fee - discount) with optional override
            if (array_key_exists('total_amount', $data)) {
                $totalAmount = (float) $data['total_amount'];
            } else {
                $totalAmount = max(0, $amount + $fee - $discountAmount);
            }

            // Determine transfer method (explicit or beneficiary default)
            $transferMethodId = $data['transfer_method_id'] ?? $beneficiary->transfer_method_id ?? null;

            // Generate unique reference code
            $reference = $this->generateReference();

            // Handle offers (optional)
            $offers = $data['offers'] ?? null;
            $offersTotal = isset($data['offers_total']) ? (float) $data['offers_total'] : 0.0;

            // Check and deduct user balance before creating transfer
            if ($sender->balance_currency !== strtoupper($data['currency_from'])) {
                throw new \Exception('Currency mismatch. Your wallet is in ' . ($sender->balance_currency ?? 'USD') . ', but transfer requires ' . $data['currency_from']);
            }

            if ($sender->balance < $totalAmount) {
                throw new \Exception('Insufficient balance. Available: ' . number_format($sender->balance ?? 0, 2) . ' ' . ($sender->balance_currency ?? 'USD') . ', Required: ' . number_format($totalAmount, 2) . ' ' . $data['currency_from']);
            }

            // Deduct balance immediately when transfer is created
            $sender->decrement('balance', $totalAmount);

            // Create transfer
            $transfer = Transfer::create([
                'sender_id' => $data['sender_id'],
                'beneficiary_id' => $data['beneficiary_id'],
                'transfer_method_id' => $transferMethodId,
                'amount' => $amount,
                'currency_from' => strtoupper($data['currency_from']),
                'currency_to' => strtoupper($data['currency_to']),
                'exchange_rate' => $exchangeRate,
                'fee' => $fee,
                'total_amount' => $totalAmount,
                'status' => 'queued',
                'initiated_at' => now(),
                'reference' => $reference,
                'promotion_id' => $promotionId,
                'discount_amount' => $discountAmount,
                'speed' => $data['speed'] ?? null,
                'estimated_delivery_at' => $data['estimated_delivery_at'] ?? null,
                'offers' => $offers,
                'offers_total' => $offersTotal,
            ]);

            // Optionally increment promotion usage when attached
            if ($promotionId) {
                Promotion::where('id', $promotionId)->increment('used_count');
            }

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

  
   public function getSenderCountryId(int $userId): int
    {
        // Try to get from user's verification
        
        $verification = UserVerification::where('user_id', $userId)
            ->where('status', 'approved')
            ->latest()
            ->first();

      
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

        // Refund balance if status changes to failed or refunded
        if (in_array($status, ['failed', 'refunded']) && !in_array($currentStatus, ['failed', 'refunded', 'completed'])) {
            $this->refundBalance($transfer);
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

    /**
     * Refund balance to user when transfer is cancelled or fails
     */
    private function refundBalance(Transfer $transfer)
    {
        $sender = $transfer->sender;
        
        if (!$sender) {
            Log::warning('Cannot refund balance: sender not found', [
                'transfer_id' => $transfer->id,
            ]);
            return;
        }

        // Check if balance currency matches
        if ($sender->balance_currency === $transfer->currency_from) {
            $totalToRefund = $transfer->total_amount;
            
            // Refund the amount
            $sender->increment('balance', $totalToRefund);
            
            Log::info('Balance refunded for cancelled/failed transfer', [
                'transfer_id' => $transfer->id,
                'transfer_reference' => $transfer->reference,
                'user_id' => $sender->id,
                'amount_refunded' => $totalToRefund,
                'currency' => $transfer->currency_from,
                'new_balance' => $sender->fresh()->balance,
            ]);
        } else {
            Log::warning('Currency mismatch when refunding balance', [
                'transfer_id' => $transfer->id,
                'user_id' => $sender->id,
                'user_currency' => $sender->balance_currency,
                'transfer_currency' => $transfer->currency_from,
            ]);
        }
    }


    public function cancelTransfer(int $transferId, int $userId): Transfer
    {
        $transfer = Transfer::where('id', $transferId)
            ->where('sender_id', $userId)
            ->firstOrFail();

        // Check if transfer is in a terminal state
        $terminalStates = ['completed', 'failed', 'refunded'];
        if (in_array($transfer->status, $terminalStates)) {
            $statusMessages = [
                'completed' => 'This transfer has already been completed and cannot be cancelled.',
                'failed' => 'This transfer has already failed and cannot be cancelled.',
                'refunded' => 'This transfer has already been refunded and cannot be cancelled.',
            ];
            throw new \Exception($statusMessages[$transfer->status] ?? "Transfer cannot be cancelled. Current status: {$transfer->status}");
        }

        // Only allow cancellation if transfer is queued or paid
        if (!in_array($transfer->status, ['queued', 'paid'])) {
            throw new \Exception("Transfer cannot be cancelled. Current status: {$transfer->status}");
        }

        // If payment was made, it should be refunded first
        if ($transfer->status === 'paid' && $transfer->payment) {
          
            Log::warning("Cancelling transfer with payment", [
                'transfer_id' => $transferId,
                'payment_id' => $transfer->payment->id,
            ]);
        }

        // Refund balance to user when transfer is cancelled
        $this->refundBalance($transfer);

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
            
        }

        return $this->updateStatus($transferId, 'refunded', 'Transfer refunded by admin', 'admin', $adminId);
    }
    private function notifyUserOfStatusChange(Transfer $transfer, string $newStatus, string $note = ''): void
{
    $user = $transfer->sender; // assuming relation Transfer->sender()

    if (!$user) {
        return;
    }

    $oldStatus = $transfer->getOriginal('status'); // status before saving

    $user->notify(new TransferStatusUpdated($transfer, $oldStatus, $newStatus));
}
// in TransferStatusUpdated

public function via($notifiable): array
{
    $channels = ['database', 'mail'];

    // only send SMS if user has a phone
    if (!empty($notifiable->phone_number)) {
        $channels[] = 'sms'; // custom channel name
    }

    return $channels;
}

}
