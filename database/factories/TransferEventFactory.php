<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\Transfer;
use App\Models\Transfer_Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transfer_Event>
 */
class TransferEventFactory extends Factory
{
    protected $model = Transfer_Event::class;

    public function definition(): array
    {
        $actorType = fake()->randomElement(['system', 'user', 'agent', 'admin']);
        $actorId = null;

        if (in_array($actorType, ['user', 'admin'], true)) {
            $actorId = User::factory();
        } elseif ($actorType === 'agent') {
            $actorId = Agent::factory();
        }

        return [
            'transfer_id' => Transfer::factory(),
            'status' => fake()->randomElement([
                'queued',
                'paid',
                'in_progress',
                'available_for_pickup',
                'completed',
                'failed',
                'refunded',
                'disputed',
            ]),
            'note' => fake()->optional()->sentence(),
            'actor_type' => $actorType,
            'actor_id' => $actorId,
        ];
    }
}
