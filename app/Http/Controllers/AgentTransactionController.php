<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Agent_Transaction;
use App\Models\Transfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AgentTransactionController extends Controller
{
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
        if ($transferReference) {
            $transfer = Transfer::where('reference', $transferReference)->first();
        }
        
        return view('portal.transactions.create', compact('agent', 'transfer', 'type'));
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

            // 2. Update the Main Transfer Status
            if ($validated['type'] === 'cash_out') {
                $transfer->update(['status' => 'completed']);
            } elseif ($validated['type'] === 'cash_in') {
                $transfer->update(['status' => 'in_progress']);
            }
            
            // Optional: Create an Audit Log here (via helper/observer)
        });

        return redirect()->route('portal.transactions.index')
            ->with('success', 'Transaction processed successfully. Commission earned: ' . number_format($commission, 2));
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