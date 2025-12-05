<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Agent_Transaction;
use App\Models\Transfer;
use App\Services\TransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AgentTransactionController extends Controller
{
    protected $transferService;

    public function __construct(TransferService $transferService)
    {
        $this->transferService = $transferService;
    }
    /**
     * Display a history of transactions for a specific agent.
     */
    public function index(Agent $agent)
    {
        // Security: Ensure the logged-in user owns this agent profile
        if (Auth::id() !== $agent->user_id) {
            abort(403, 'Unauthorized access to store records.');
        }

        $transactions = $agent->transactions()
            ->with('transfer') // Load transfer details
            ->latest('processed_at')
            ->paginate(15);

        return view('portal.transactions.index', compact('agent', 'transactions'));
    }

    /**
     * Show the form to process a generic transaction (Cash In or Out).
     */
    public function create(Agent $agent, Request $request)
    {
        // Get transfer reference if provided
        $transferReference = $request->get('reference');
        $type = $request->get('type', 'cash_in'); // cash_in or cash_out
        
        $transfer = null;
        $statusError = null;
        
        if ($transferReference) {
            $transfer = Transfer::where('reference', $transferReference)
                ->with(['beneficiary.country', 'beneficiary.method', 'sender'])
                ->first();
            
            if ($transfer) {
                // Validate transfer status matches the operation type
                if ($type === 'cash_out') {
                    // Cash-out requires 'available_for_pickup' status
                    if ($transfer->status !== 'available_for_pickup') {
                        $statusError = 'This transfer is not ready for pickup. Status must be "available_for_pickup" but current status is "' . $transfer->status . '".';
                    }
                } elseif ($type === 'cash_in') {
                    // Cash-in requires 'queued' or 'paid' status
                    if (!in_array($transfer->status, ['queued', 'paid'])) {
                        $statusError = 'This transfer cannot be processed for cash-in. Status must be "queued" or "paid" but current status is "' . $transfer->status . '".';
                    }
                }
            }
        }
        
        // Get list of available transfers for the selected type (for reference)
        $availableTransfers = [];
        if ($type === 'cash_out') {
            $availableTransfers = Transfer::where('status', 'available_for_pickup')
                ->with(['beneficiary.country', 'beneficiary.method', 'sender'])
                ->orderBy('initiated_at', 'desc')
                ->limit(10)
                ->get();
        } else {
            $availableTransfers = Transfer::whereIn('status', ['queued', 'paid'])
                ->with(['beneficiary.country', 'beneficiary.method', 'sender'])
                ->orderBy('initiated_at', 'desc')
                ->limit(10)
                ->get();
        }
        
        return view('portal.transactions.create', compact('agent', 'transfer', 'type', 'statusError', 'availableTransfers'));
    }

    /**
     * Store (Process) a new transaction.
     * This handles the logic of finding the transfer and marking it as paid/received.
     */
    public function store(Request $request, Agent $agent)
    {
        // Security: Ensure the logged-in user owns this agent profile
        if (Auth::id() !== $agent->user_id) {
            abort(403, 'Unauthorized access to store records.');
        }

        $validated = $request->validate([
            'transfer_reference' => 'required|string|exists:transfers,reference',
            'type' => 'required|in:cash_in,cash_out',
        ]);

        // Find the transfer
        $transfer = Transfer::where('reference', $validated['transfer_reference'])->firstOrFail();

        // LOGIC CHECKS
        if ($validated['type'] === 'cash_out') {
            // Rule: Can only cash out if status is 'available_for_pickup'
            if ($transfer->status !== 'available_for_pickup') {
                return back()->withErrors(['transfer_reference' => 'This transfer is not ready for pickup yet.']);
            }
            
            // RESTRICTION: Check if agent has sufficient balance to pay out
            // For cash-out, agent pays money to customer, so balance must be sufficient
            $requiredAmount = $transfer->amount;
            $currentBalance = $agent->balance ?? 0;
            
            if ($currentBalance < $requiredAmount) {
                return back()->withErrors([
                    'transfer_reference' => 'Insufficient agent balance to process this cash-out. Required: $' . number_format($requiredAmount, 2) . ', Available: $' . number_format($currentBalance, 2) . '. Please ensure you have sufficient funds before processing payouts.'
                ]);
            }
        } elseif ($validated['type'] === 'cash_in') {
            // Rule: Can only cash in if status is 'queued' or 'paid'
            if (!in_array($transfer->status, ['queued', 'paid'])) {
                return back()->withErrors(['transfer_reference' => 'This transfer cannot be processed for cash-in.']);
            }
        }

        // Calculate Commission (can be made configurable per agent or system-wide)
        $commissionRate = $agent->commission_rate ?? 0.01; // Default 1%, can be overridden per agent
        $commission = $transfer->amount * $commissionRate;

        DB::transaction(function () use ($agent, $transfer, $validated, $commission) {
            
            // 1. Create the Agent Transaction Record
            Agent_Transaction::create([
                'agent_id' => $agent->id,
                'transfer_id' => $transfer->id,
                'type' => $validated['type'],
                'amount' => $transfer->amount,
                'commission' => $commission,
                'processed_at' => now(),
            ]);

            // 2. Update agent balance based on transaction type
            if ($validated['type'] === 'cash_in') {
                // Cash-in: Agent receives money from customer → balance INCREASES
                $agent->increment('balance', $transfer->amount);
            } elseif ($validated['type'] === 'cash_out') {
                // Cash-out: Agent pays money to customer → balance DECREASES
                // Balance check already done above, safe to deduct
                $agent->decrement('balance', $transfer->amount);
            }

            // 3. Update the Main Transfer Status using TransferService
            // This ensures balance is deducted when status becomes 'completed'
            if ($validated['type'] === 'cash_out') {
                // Cash-out: Agent pays customer → transfer is completed
                // This will trigger balance deduction in TransferService
                $this->transferService->updateStatus(
                    $transfer->id,
                    'completed',
                    "Agent {$agent->store_name} processed cash-out",
                    'agent',
                    $agent->id
                );
            } elseif ($validated['type'] === 'cash_in') {
                // Cash-in: When agent processes payment, transfer becomes available for pickup
                // This makes it appear in "Pending Cash-Out (Payouts)" section
                // Balance is NOT deducted yet - will be deducted when cash-out is processed
                $this->transferService->updateStatus(
                    $transfer->id,
                    'available_for_pickup',
                    "Agent {$agent->store_name} processed cash-in - ready for pickup",
                    'agent',
                    $agent->id
                );
            }
            
            // Optional: Create an Audit Log here (via helper/observer)
        });

        // Refresh agent to get updated balance
        $agent->refresh();
        
        // Create success message based on transaction type
        if ($validated['type'] === 'cash_in') {
            $message = 'Cash-in processed successfully! Commission earned: $' . number_format($commission, 2) . '. Your balance increased by $' . number_format($transfer->amount, 2) . '. New balance: $' . number_format($agent->balance, 2) . '. Transfer is now available for cash-out in Pending Cash-Out section.';
        } else {
            $message = 'Cash-out processed successfully! Commission earned: $' . number_format($commission, 2) . '. Your balance decreased by $' . number_format($transfer->amount, 2) . '. New balance: $' . number_format($agent->balance, 2) . '. Transfer completed.';
        }
        
        return redirect()->route('portal.transactions.index')
            ->with('success', $message);
    }

    /**
     * Display details of a specific agent transaction.
     */
    public function show(Agent $agent, Agent_Transaction $transaction)
    {
        // Ensure the transaction belongs to this agent
        if ($transaction->agent_id !== $agent->id) {
            abort(403);
        }

        return view('portal.transactions.show', compact('agent', 'transaction'));
    }
}