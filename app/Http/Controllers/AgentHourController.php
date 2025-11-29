<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Agent_Hour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentHourController extends Controller
{
    /**
     * Display the working hours for a specific agent.
     */
    public function index(Agent $agent)
    {
        // Get hours ordered by day of week (0 = Sunday, 1 = Monday, etc.)
        $hours = $agent->hours()->orderBy('day_of_week')->get();
        
        // Helper to map integers to day names
        $days = [
            0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 
            3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'
        ];

        return view('agents.hours.index', compact('agent', 'hours', 'days'));
    }

    /**
     * Show the form for editing working hours.
     */
    public function edit(Agent $agent)
    {
        // $this->authorize('update', $agent); // Ensure user owns this agent profile

        // Key the collection by 'day_of_week' for easy lookup in the view
        $hours = $agent->hours->keyBy('day_of_week');
        
        $days = [
            0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 
            3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'
        ];

        return view('agents.hours.edit', compact('agent', 'hours', 'days'));
    }

    /**
     * Update or Create working hours for the week.
     * Expects an array of hours indexed by day_of_week.
     */
    public function update(Request $request, Agent $agent)
    {
        // Validation: Expecting an array 'hours' where key is day (0-6)
        $validated = $request->validate([
            'hours' => 'required|array',
            'hours.*.open_time' => 'nullable|date_format:H:i',
            'hours.*.close_time' => 'nullable|date_format:H:i|after:hours.*.open_time',
            'hours.*.enabled' => 'nullable|boolean', // Checkbox to mark day as "Open"
        ]);

        DB::transaction(function () use ($agent, $validated) {
            foreach ($validated['hours'] as $day => $times) {
                
                // If "enabled" is not checked, we assume the shop is closed that day
                if (!isset($times['enabled']) || !$times['enabled']) {
                    // Remove existing entry if it exists (Closed)
                    Agent_Hour::where('agent_id', $agent->id)
                        ->where('day_of_week', $day)
                        ->delete();
                    continue;
                }

                // If enabled, Update or Create the record
                // We cannot use updateOrCreate easily with composite keys and $primaryKey=null
                // So we use standard query builder logic
                Agent_Hour::updateOrInsert(
                    [
                        'agent_id' => $agent->id,
                        'day_of_week' => $day
                    ],
                    [
                        'open_time' => $times['open_time'],
                        'close_time' => $times['close_time']
                    ]
                );
            }
        });

        return redirect()->route('agents.hours.index', $agent)
                         ->with('success', 'Working hours updated successfully.');
    }
}