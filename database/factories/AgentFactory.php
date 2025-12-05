<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Agent>
 */
class AgentFactory extends Factory
{
    protected $model = Agent::class;

    public function definition(): array
    {
        $agentRoleId = $this->agentRoleId();

        return [
            'user_id' => User::factory()->state(['role_id' => $agentRoleId]),
            'store_name' => fake()->company() . ' Transfers',
            'address' => fake()->address(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'status' => fake()->randomElement(['pending', 'approved', 'suspended']),
            'commission_rate' => fake()->randomFloat(4, 0.005, 0.025),
        ];
    }

    private function agentRoleId(): int
    {
        $role = Role::query()->where('name', 'agent')->first();

        if ($role) {
            return $role->id;
        }

        return Role::factory()->create(['name' => 'agent'])->id;
    }
}
