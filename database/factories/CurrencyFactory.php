<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    public function definition(): array
    {
        $code = fake()->unique()->currencyCode();

        return [
            'code' => $code,
            'name' => $code . ' Currency',
            'decimals' => fake()->randomElement([0, 2, 2, 2, 3]),
        ];
    }
}
