<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        $generatedAt = fake()->dateTimeBetween('-15 days', 'now');

        return [
            'type' => fake()->randomElement(['daily_transfers', 'agent_performance', 'user_growth']),
            'generated_at' => $generatedAt,
            'generated_by' => User::factory(),
            'file_path' => 'reports/' . fake()->uuid() . '.pdf',
            'parameters' => [
                'from' => fake()->dateTimeBetween('-30 days', '-15 days')->format('Y-m-d'),
                'to' => $generatedAt->format('Y-m-d'),
            ],
        ];
    }
}
