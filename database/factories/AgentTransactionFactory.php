<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\Agent_Transaction;
use App\Models\Transfer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Agent_Transaction>
 */
class AgentTransactionFactory extends Factory
{
    protected $model = Agent_Transaction::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 20, 5000);
        $commission = round($amount * fake()->randomFloat(3, 0.005, 0.05), 2);

        return [
            'agent_id' => Agent::factory(),
            'transfer_id' => Transfer::factory(),
            'type' => fake()->randomElement(['cash_in', 'cash_out']),
            'amount' => $amount,
            'commission' => $commission,
            'processed_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ];
    }
}
