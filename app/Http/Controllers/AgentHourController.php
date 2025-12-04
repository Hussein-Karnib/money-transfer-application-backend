<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Agent_Hour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
        $validator = Validator::make($request->all(), [
            'hours' => 'required|array',
            'hours.*.open_time' => 'nullable|date_format:H:i',
            'hours.*.close_time' => 'nullable|date_format:H:i',
            'hours.*.enabled' => 'nullable|boolean',
        ]);

        $validator->after(function ($validator) {
            $hours = $validator->getData()['hours'] ?? [];

            foreach ($hours as $day => $times) {
                $enabled = filter_var($times['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);

                if ($enabled) {
                    if (empty($times['open_time']) || empty($times['close_time'])) {
                        $validator->errors()->add("hours.$day.open_time", 'Open and close times are required when enabled.');
                        continue;
                    }

                    if ($times['close_time'] <= $times['open_time']) {
                        $validator->errors()->add("hours.$day.close_time", 'Close time must be later than open time.');
                    }
                }
            }
        });

        $validated = $validator->validate();

        DB::transaction(function () use ($agent, $validated) {
            foreach (range(0, 6) as $day) {
                $times = $validated['hours'][$day] ?? null;
                $enabled = $times ? filter_var($times['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) : false;

                Agent_Hour::updateOrCreate(
                    [
                        'agent_id' => $agent->id,
                        'day_of_week' => $day,
                    ],
                    [
                        'open_time' => $enabled ? $times['open_time'] : null,
                        'close_time' => $enabled ? $times['close_time'] : null,
                        'is_closed' => !$enabled,
                    ]
                );
            }
        });

        return redirect()->route('portal.hours.index', $agent)
                         ->with('success', 'Working hours updated successfully.');
    }
}