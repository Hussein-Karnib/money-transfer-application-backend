<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;

class AgentController extends Controller
{
    /**
     * Display a listing of agents.
     * Useful for the "Map" feature (returning JSON) or Admin list.
     */
    public function index(Request $request)
    {
        // Filter by status (e.g., Admin wants to see 'pending' agents)
        $query = Agent::with('user');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Return JSON for the Map if requested via API
        if ($request->wantsJson()) {
            return response()->json($query->get());
        }

        $agents = $query->paginate(10);
        return view('agents.index', compact('agents'));
    }

    /**
     * Show the registration form for a new Agent.
     */
    public function create()
    {
        return view('agents.register');
    }

    /**
     * Handle the registration of a new Agent.
     * Creates both the User account and the Agent profile.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // User Fields
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            
            // Agent Specific Fields
            'store_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        DB::transaction(function () use ($validated) {
            // 1. Create the User Account
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                // 'role_id' => 2 // Assuming 2 is for Agents
            ]);

            // 2. Create the Agent Profile (Status defaults to 'pending')
            Agent::create([
                'user_id' => $user->id,
                'store_name' => $validated['store_name'],
                'address' => $validated['address'],
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'status' => 'pending', 
            ]);
        });

        return redirect()->route('login')->with('success', 'Registration successful! Your account is pending admin approval.');
    }

    /**
     * Display specific agent details (Profile page).
     */
    public function show(Agent $agent)
    {
        $agent->load(['user', 'hours']); // Load working hours if available
        return view('agents.show', compact('agent'));
    }

    /**
     * Show form to edit agent details (Address, Store Name).
     */
    public function edit(Agent $agent)
    {
        // Ensure only the agent themselves or an Admin can edit
        // $this->authorize('update', $agent); 
        
        return view('agents.edit', compact('agent'));
    }

    /**
     * Update agent profile information.
     */
    public function update(Request $request, Agent $agent)
    {
        $validated = $request->validate([
            'store_name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $agent->update($validated);

        return redirect()->route('agents.show', $agent)->with('success', 'Store details updated.');
    }

    /**
     * Admin Action: Approve or Suspend an agent.
     */
    public function updateStatus(Request $request, Agent $agent)
    {
        // $this->authorize('adminAction', Agent::class); // Security check

        $request->validate([
            'status' => 'required|in:approved,suspended,pending',
        ]);

        $agent->update(['status' => $request->status]);

        // Optional: Send notification to agent about status change
        // Notification::send($agent->user, new AgentStatusChanged($request->status));

        return back()->with('success', "Agent status updated to {$request->status}.");
    }

    /**
     * Remove the agent profile.
     */
    public function destroy(Agent $agent)
    {
        // Optional: Delete the associated user as well?
        $agent->delete();
        return redirect()->route('agents.index')->with('success', 'Agent deleted.');
    }
}