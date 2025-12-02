<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Agent_Transaction;
use App\Models\Transfer;
use App\Models\Transfer_Event;
use App\Support\NotificationHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AgentTransactionApiController extends Controller
{
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

            $transfer->update([
                'status' => $newStatus,
                'completed_at' => $validated['type'] === 'cash_out' ? $processedAt : null,
            ]);

            Transfer_Event::create([
                'transfer_id' => $transfer->id,
                'status' => $newStatus,
                'note' => sprintf(
                    'Agent %s processed %s (reference %s)',
                    $agent->store_name,
                    $validated['type'],
                    $transfer->reference
                ),
                'actor_type' => 'agent',
                'actor_id' => $agent->id,
            ]);

            return $agentTransaction->load('transfer');
        });

        // Person 4: send user-facing notifications
        if ($validated['type'] === 'cash_out') {
            NotificationHelper::transferCashedOut($transfer);
        } elseif ($validated['type'] === 'cash_in' && $newStatus === 'available_for_pickup') {
            NotificationHelper::transferReadyForPickup($transfer);
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

