<?php

namespace Database\Factories;

use App\Models\Beneficiary;
use App\Models\Currency;
use App\Models\Promotion;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Transfer>
 */
class TransferFactory extends Factory
{
    protected $model = Transfer::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 25, 5000);
        $fee = fake()->randomFloat(2, 1, 50);
        $discount = fake()->randomFloat(2, 0, $fee);
        $exchangeRate = fake()->randomFloat(6, 0.5, 4);

        $initiatedAt = fake()->dateTimeBetween('-10 days', 'now');
        $status = fake()->randomElement([
            'queued',
            'paid',
            'in_progress',
            'available_for_pickup',
            'completed',
            'failed',
            'refunded',
            'disputed',
        ]);

        $completedAt = in_array($status, ['completed', 'available_for_pickup', 'paid'], true)
            ? fake()->dateTimeBetween($initiatedAt, 'now')
            : null;

        $promotionId = fake()->boolean(25) ? $this->resolvePromotionId() : null;

        $currencyFrom = $this->resolveCurrencyCode();
        $currencyTo = $this->resolveCurrencyCode($currencyFrom);

        return [
            'sender_id' => User::factory(),
            'beneficiary_id' => Beneficiary::factory(),
            'amount' => $amount,
            'currency_from' => $currencyFrom,
            'currency_to' => $currencyTo,
            'exchange_rate' => $exchangeRate,
            'fee' => $fee,
            'total_amount' => max(($amount + $fee) - $discount, 0),
            'status' => $status,
            'initiated_at' => $initiatedAt,
            'completed_at' => $completedAt,
            'reference' => strtoupper('TRF-' . Str::random(10)),
            'promotion_id' => $promotionId,
            'discount_amount' => $discount,
            'speed' => fake()->randomElement(['standard', 'express']),
            'estimated_delivery_at' => fake()->dateTimeBetween(
                $initiatedAt,
                (clone $initiatedAt)->modify('+4 days')
            ),
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

    private function resolvePromotionId(): int
    {
        $promotionId = Promotion::query()->inRandomOrder()->value('id');

        if ($promotionId) {
            return $promotionId;
        }

        return Promotion::factory()->create()->id;
    }
}
