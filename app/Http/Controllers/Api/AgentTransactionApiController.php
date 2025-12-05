<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Agent_Transaction;
use App\Models\Transfer;
use App\Models\Transfer_Event;
use App\Services\TransferService;
use App\Support\NotificationHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\TransactionCompletedMail;

class AgentTransactionApiController extends Controller
{
    protected $transferService;

    public function __construct(TransferService $transferService)
    {
        $this->transferService = $transferService;
    }
    /**
     * Return all transactions processed by the given agent.
     */
    public function index(Request $request, Agent $agent): JsonResponse
    {
        $this->authorizeForView($agent);

        $transactions = $agent->transactions()
            ->with('transfer')
            ->latest('processed_at')
            ->get()
            ->map(fn (Agent_Transaction $transaction) => $this->formatTransaction($transaction));

        return response()->json([
            'success' => true,
            'data' => $transactions,
            'count' => $transactions->count(),
        ]);
    }

    /**
     * Process a cash-in or cash-out operation at an agent location.
     */
    public function process(Request $request, Agent $agent): JsonResponse
    {
        $this->authorizeForProcessing($agent);

        if ($agent->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Agent is not approved to process transactions.',
            ], 422);
        }

        $validated = $request->validate([
            'transfer_reference' => ['required', 'string', 'exists:transfers,reference'],
            'type' => ['required', 'in:cash_in,cash_out'],
        ]);

        $transfer = Transfer::where('reference', $validated['transfer_reference'])->firstOrFail();
        $wasCompleted = $transfer->status === 'completed';

        if (! $this->canHandleTransfer($transfer, $validated['type'])) {
            return response()->json([
                'success' => false,
                'message' => 'Transfer cannot be processed in its current state.',
                'transfer_status' => $transfer->status,
            ], 409);
        }

        if ($this->alreadyProcessed($transfer, $validated['type'])) {
            return response()->json([
                'success' => false,
                'message' => 'This transfer was already processed for the requested operation.',
            ], 409);
        }

        // RESTRICTION: Check agent balance for cash-out
        if ($validated['type'] === 'cash_out') {
            $requiredAmount = $transfer->amount;
            $currentBalance = $agent->balance ?? 0;
            
            if ($currentBalance < $requiredAmount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient agent balance to process this cash-out. Required: $' . number_format($requiredAmount, 2) . ', Available: $' . number_format($currentBalance, 2),
                ], 422);
            }
        }

        $commissionRate = $agent->commission_rate ?? 0.01;
        $commission = round($transfer->amount * $commissionRate, 2);
        $newStatus = $validated['type'] === 'cash_out' ? 'completed' : 'available_for_pickup';
        $processedAt = now();

        $transaction = DB::transaction(function () use (
            $agent,
            $transfer,
            $validated,
            $commission,
            $newStatus,
            $processedAt
        ) {
            $agentTransaction = Agent_Transaction::create([
                'agent_id' => $agent->id,
                'transfer_id' => $transfer->id,
                'type' => $validated['type'],
                'amount' => $transfer->amount,
                'commission' => $commission,
                'processed_at' => $processedAt,
            ]);

            // Update agent balance based on transaction type
            if ($validated['type'] === 'cash_in') {
                // Cash-in: Agent receives money from customer → balance INCREASES
                $agent->increment('balance', $transfer->amount);
            } elseif ($validated['type'] === 'cash_out') {
                // Cash-out: Agent pays money to customer → balance DECREASES
                $agent->decrement('balance', $transfer->amount);
            }

            // Use TransferService to update status - this ensures balance is deducted when status becomes 'completed'
            if ($validated['type'] === 'cash_out') {
                // Cash-out: Transfer is completed - balance will be deducted
                $this->transferService->updateStatus(
                    $transfer->id,
                    'completed',
                    sprintf('Agent %s processed cash-out (reference %s)', $agent->store_name, $transfer->reference),
                    'agent',
                    $agent->id
                );
            } else {
                // Cash-in: Transfer becomes available for pickup - balance not deducted yet
                $this->transferService->updateStatus(
                    $transfer->id,
                    'available_for_pickup',
                    sprintf('Agent %s processed cash-in (reference %s)', $agent->store_name, $transfer->reference),
                    'agent',
                    $agent->id
                );
            }

            // Transfer status update and event creation is now handled by TransferService->updateStatus()
            // No need to create events manually here

            return $agentTransaction->load('transfer');
        });

        $transfer->refresh()->loadMissing(['sender', 'beneficiary', 'currencyFrom', 'currencyTo']);

        // Person 4: send user-facing notifications
        if ($validated['type'] === 'cash_out') {
            NotificationHelper::transferCashedOut($transfer);
        } elseif ($validated['type'] === 'cash_in' && $newStatus === 'available_for_pickup') {
            NotificationHelper::transferReadyForPickup($transfer);
        }

        if (! $wasCompleted && $newStatus === 'completed' && $transfer->sender?->email) {
            Mail::to($transfer->sender->email)->send(new TransactionCompletedMail($transfer));
        }

        $beneficiaryDetails = $transfer->beneficiary?->payout_details ?? [];
        $beneficiaryEmail = is_array($beneficiaryDetails) ? ($beneficiaryDetails['email'] ?? null) : null;
        if (! $wasCompleted && $newStatus === 'completed' && $beneficiaryEmail) {
            Mail::to($beneficiaryEmail)->send(new TransactionCompletedMail($transfer));
        }

        return response()->json([
            'success' => true,
            'message' => 'Transfer processed successfully.',
            'data' => $this->formatTransaction($transaction),
        ]);
    }

    protected function authorizeForView(Agent $agent): void
    {
        $user = Auth::user();

        if (! $user) {
            abort(401, 'Authentication required.');
        }

        if ($user->id === $agent->user_id) {
            return;
        }

        if ($user->role && $user->role->name === 'admin') {
            return;
        }

        abort(403, 'You are not allowed to view these transactions.');
    }

    protected function authorizeForProcessing(Agent $agent): void
    {
        $user = Auth::user();

        if (! $user) {
            abort(401, 'Authentication required.');
        }

        if ($user->id === $agent->user_id) {
            return;
        }

        abort(403, 'Only the agent owner can process transactions.');
    }

    protected function canHandleTransfer(Transfer $transfer, string $type): bool
    {
        if ($type === 'cash_out') {
            return $transfer->status === 'available_for_pickup';
        }

        return in_array($transfer->status, ['queued', 'paid'], true);
    }

    protected function alreadyProcessed(Transfer $transfer, string $type): bool
    {
        if ($type === 'cash_out' && $transfer->status === 'completed') {
            return true;
        }

        if ($type === 'cash_in' && $transfer->status === 'available_for_pickup') {
            return true;
        }

        return Agent_Transaction::where('transfer_id', $transfer->id)
            ->where('type', $type)
            ->exists();
    }

    protected function formatTransaction(Agent_Transaction $transaction): array
    {
        $transfer = $transaction->transfer;

        return [
            'id' => $transaction->id,
            'type' => $transaction->type,
            'amount' => (float) $transaction->amount,
            'commission' => (float) $transaction->commission,
            'processed_at' => optional($transaction->processed_at)->toIso8601String(),
            'transfer' => [
                'id' => $transfer->id ?? null,
                'reference' => $transfer->reference ?? null,
                'status' => $transfer->status ?? null,
                'completed_at' => $transfer?->completed_at ? $transfer->completed_at->toIso8601String() : null,
            ],
        ];
    }
}
