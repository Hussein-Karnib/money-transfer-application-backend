<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\Agent_Hour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Agent_Hour>
 */
class AgentHourFactory extends Factory
{
    protected $model = Agent_Hour::class;

    public function definition(): array
    {
        $isClosed = fake()->boolean(5);

        return [
            'agent_id' => Agent::factory(),
            'day_of_week' => fake()->numberBetween(0, 6),
            'open_time' => $isClosed ? null : '09:00:00',
            'close_time' => $isClosed ? null : '17:00:00',
            'is_closed' => $isClosed,
        ];
    }
}
