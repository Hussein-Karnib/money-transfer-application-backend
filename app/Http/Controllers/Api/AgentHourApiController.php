<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Agent_Hour;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AgentHourApiController extends Controller
{
    /**
     * Return the weekly schedule for a given agent.
     */
    public function show(Agent $agent): JsonResponse
    {
        $agent->loadMissing('hours');

        return response()->json([
            'success' => true,
            'data' => $this->formatSchedule($agent),
        ]);
    }

    /**
     * Update the weekly schedule for the authenticated agent (or admin).
     */
    public function update(Request $request, Agent $agent): JsonResponse
    {
        $this->ensureCanModify($agent);

        $validator = Validator::make($request->all(), [
            'hours' => ['required', 'array', 'size:7'],
            'hours.*.day' => ['required', 'integer', 'between:0,6', 'distinct'],
            'hours.*.is_closed' => ['sometimes', 'boolean'],
            'hours.*.open_time' => ['nullable', 'date_format:H:i'],
            'hours.*.close_time' => ['nullable', 'date_format:H:i'],
        ]);

        $validator->after(function ($validator) {
            $hours = $validator->getData()['hours'] ?? [];

            foreach ($hours as $index => $hour) {
                $isClosed = (bool)($hour['is_closed'] ?? false);

                if (! $isClosed) {
                    if (empty($hour['open_time']) || empty($hour['close_time'])) {
                        $validator->errors()->add("hours.$index.open_time", 'Open and close times are required when a day is open.');
                        continue;
                    }

                    if ($hour['close_time'] <= $hour['open_time']) {
                        $validator->errors()->add("hours.$index.close_time", 'Close time must be later than open time.');
                    }
                }
            }
        });

        $validated = $validator->validate();

        DB::transaction(function () use ($validated, $agent) {
            $byDay = collect($validated['hours'])->keyBy(fn ($entry) => (int) $entry['day']);

            foreach (range(0, 6) as $day) {
                $entry = $byDay->get($day, ['is_closed' => true]);
                $isClosed = (bool)($entry['is_closed'] ?? false);

                Agent_Hour::updateOrCreate(
                    [
                        'agent_id' => $agent->id,
                        'day_of_week' => $day,
                    ],
                    [
                        'open_time' => $isClosed ? null : $entry['open_time'],
                        'close_time' => $isClosed ? null : $entry['close_time'],
                        'is_closed' => $isClosed,
                    ]
                );
            }
        });

        $agent->load('hours');

        return response()->json([
            'success' => true,
            'message' => 'Working hours updated successfully.',
            'data' => $this->formatSchedule($agent),
        ]);
    }

    /**
     * Ensure the authenticated user can modify the agent.
     */
    protected function ensureCanModify(Agent $agent): void
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

        abort(403, 'You are not allowed to update these hours.');
    }

    /**
     * Build a normalized 7-day schedule for API responses.
     */
    protected function formatSchedule(Agent $agent): array
    {
        $agent->loadMissing('hours');
        $hours = $agent->hours->keyBy('day_of_week');

        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        return collect(range(0, 6))->map(function ($day) use ($hours, $days) {
            $entry = $hours->get($day);

            return [
                'day' => $day,
                'label' => $days[$day],
                'is_closed' => $entry ? (bool) $entry->is_closed : true,
                'open_time' => $entry ? $entry->open_time : null,
                'close_time' => $entry ? $entry->close_time : null,
            ];
        })->all();
    }
}

