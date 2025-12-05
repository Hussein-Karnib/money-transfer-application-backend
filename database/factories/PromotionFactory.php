<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Promotion>
 */
class PromotionFactory extends Factory
{
    protected $model = Promotion::class;

    public function definition(): array
    {
        $countryId = Country::query()->inRandomOrder()->value('id');

        $startsAt = fake()->dateTimeBetween('-10 days', '+5 days');
        $endsAt = fake()->dateTimeBetween('+6 days', '+60 days');

        $discountType = fake()->randomElement(['percent', 'fixed']);
        $discountValue = $discountType === 'percent'
            ? fake()->numberBetween(1, 20)
            : fake()->randomFloat(2, 1, 25);

        return [
            'code' => strtoupper(Str::random(8)),
            'description' => fake()->sentence(),
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'max_discount' => fake()->optional()->randomFloat(2, 10, 5000),
            'min_amount' => fake()->randomFloat(2, 0, 50),
            'country_to_id' => fake()->boolean(30) ? $countryId : null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'usage_limit' => fake()->optional()->numberBetween(50, 500),
            'used_count' => 0,
            'active' => true,
        ];
    }
}
