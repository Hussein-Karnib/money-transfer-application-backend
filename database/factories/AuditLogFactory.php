<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        $actorType = fake()->randomElement(['user', 'agent', 'admin', 'system']);
        $actorId = null;
        $userId = fake()->boolean(70) ? User::factory() : null;

        if ($actorType === 'user' || $actorType === 'admin') {
            $actorId = User::factory();
        } elseif ($actorType === 'agent') {
            $actorId = Agent::factory();
        }

        return [
            'user_id' => $userId,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'action' => fake()->randomElement(['create', 'update', 'delete', 'login']),
            'table_name' => fake()->randomElement(['users', 'transfers', 'beneficiaries', 'payments']),
            'record_id' => fake()->numberBetween(1, 5000),
            'metadata' => [
                'ip' => fake()->ipv4(),
                'user_agent' => fake()->userAgent(),
            ],
            'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
