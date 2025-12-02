<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\User;
use App\Support\NotificationHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
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

        // Notify agent about status change (Person 4 helper)
        NotificationHelper::agentStatusChanged($agent->fresh('user'), $request->status);

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

    /**
     * Public API endpoint for agent map.
     * Returns approved agents with location data, optionally filtered by distance and open status.
     */
    public function map(Request $request)
    {
        $query = Agent::with(['user', 'hours'])
            ->where('status', 'approved') // Only show approved agents
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        // Filter by distance if latitude, longitude, and radius provided
        if ($request->has('latitude') && $request->has('longitude') && $request->has('radius')) {
            $lat = $request->latitude;
            $lng = $request->longitude;
            $radius = $request->radius; // in kilometers

            // Haversine formula for distance calculation
            $query->selectRaw('*, (
                6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )
            ) AS distance', [$lat, $lng, $lat])
            ->havingRaw('distance < ?', [$radius])
            ->orderBy('distance');
        }

        // Filter by currently open agents
        if ($request->boolean('open_now')) {
            $currentDay = now()->dayOfWeek; // 0 = Sunday, 6 = Saturday
            $currentTime = now()->format('H:i:s');

            $query->whereHas('hours', function ($q) use ($currentDay, $currentTime) {
                $q->where('day_of_week', $currentDay)
                  ->where('is_closed', false)
                  ->where('open_time', '<=', $currentTime)
                  ->where('close_time', '>=', $currentTime);
            });
        }

        $agents = $query->get()->map(function ($agent) {
            return [
                'id' => $agent->id,
                'store_name' => $agent->store_name,
                'address' => $agent->address,
                'latitude' => (float) $agent->latitude,
                'longitude' => (float) $agent->longitude,
                'owner_name' => $agent->user->name,
                'hours' => $agent->hours->map(function ($hour) {
                    $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                    return [
                        'day' => $days[$hour->day_of_week],
                        'day_of_week' => $hour->day_of_week,
                        'open_time' => $hour->open_time,
                        'close_time' => $hour->close_time,
                        'is_closed' => $hour->is_closed,
                    ];
                }),
                'distance' => $agent->distance ?? null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $agents,
            'count' => $agents->count(),
        ]);
    }

    /**
     * Display commission report for an agent.
     */
    public function commissions(Request $request, Agent $agent)
    {
        // Security: Ensure the logged-in user owns this agent profile
        if (Auth::id() !== $agent->user_id) {
            abort(403, 'Unauthorized access to commission records.');
        }

        $query = $agent->transactions();

        // Filter by date range if provided
        if ($request->has('from')) {
            $query->whereDate('processed_at', '>=', $request->from);
        }
        if ($request->has('to')) {
            $query->whereDate('processed_at', '<=', $request->to);
        }

        $transactions = $query->with('transfer')
            ->latest('processed_at')
            ->paginate(20);

        // Calculate totals
        $totalCommission = $agent->transactions()->sum('commission');
        $monthlyCommission = $agent->transactions()
            ->whereYear('processed_at', now()->year)
            ->whereMonth('processed_at', now()->month)
            ->sum('commission');
        $todayCommission = $agent->transactions()
            ->whereDate('processed_at', today())
            ->sum('commission');

        // Filtered totals
        $filteredCommission = $query->sum('commission');

        return view('portal.commissions', compact(
            'agent',
            'transactions',
            'totalCommission',
            'monthlyCommission',
            'todayCommission',
            'filteredCommission'
        ));
    }
}