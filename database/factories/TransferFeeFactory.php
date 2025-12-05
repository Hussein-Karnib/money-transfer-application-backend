<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\Transfer_Fee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transfer_Fee>
 */
class TransferFeeFactory extends Factory
{
    protected $model = Transfer_Fee::class;

    public function definition(): array
    {
        $min = fake()->randomFloat(2, 10, 1000);
        $max = $min + fake()->randomFloat(2, 50, 1500);

        $fromId = Country::query()->inRandomOrder()->value('id') ?? Country::factory()->create()->id;
        $toId = Country::query()
            ->where('id', '!=', $fromId)
            ->inRandomOrder()
            ->value('id') ?? Country::factory()->create()->id;

        return [
            'country_from_id' => $fromId,
            'country_to_id' => $toId,
            'min_amount' => $min,
            'max_amount' => $max,
            'fee_fixed' => fake()->randomFloat(2, 0, 15),
            'fee_percent' => fake()->randomFloat(2, 0, 5),
            'last_updated' => fake()->dateTimeBetween('-10 days', 'now'),
        ];
    }
}
