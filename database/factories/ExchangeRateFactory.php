<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Exchange_Rate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exchange_Rate>
 */
class ExchangeRateFactory extends Factory
{
    protected $model = Exchange_Rate::class;

    public function definition(): array
    {
        $from = $this->resolveCurrencyCode();
        $to = $this->resolveCurrencyCode($from);

        return [
            'currency_from' => $from,
            'currency_to' => $to,
            'rate' => fake()->randomFloat(6, 0.2, 5),
            'last_updated' => fake()->dateTimeBetween('-5 days', 'now'),
        ];
    }

    private function resolveCurrencyCode(?string $differentFrom = null): string
    {
        $query = Currency::query();

        if ($differentFrom !== null) {
            $query->where('code', '!=', $differentFrom);
        }

        $code = $query->inRandomOrder()->value('code');

        if ($code) {
            return $code;
        }

        $currency = Currency::factory()->create();

        if ($differentFrom !== null && $currency->code === $differentFrom) {
            $currency = Currency::factory()->create();
        }

        return $currency->code;
    }
}
