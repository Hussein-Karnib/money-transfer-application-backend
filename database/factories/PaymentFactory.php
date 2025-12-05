<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Payment;
use App\Models\Transfer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $status = fake()->randomElement(['authorized', 'captured', 'failed', 'refunded']);

        return [
            'transfer_id' => Transfer::factory(),
            'amount' => fake()->randomFloat(2, 10, 5000),
            'currency_code' => $this->resolveCurrencyCode(),
            'gateway' => fake()->randomElement(['stripe', 'paypal', 'adyen', 'manual']),
            'gateway_ref' => fake()->optional()->uuid(),
            'status' => $status,
            'authorized_at' => fake()->dateTimeBetween('-5 days', 'now'),
            'captured_at' => null,
            'refunded_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Payment $payment) {
            $transfer = $payment->transfer ?: Transfer::factory()->create();
            $payment->transfer()->associate($transfer);
            $payment->currency_code = $transfer->currency_from;
            $payment->amount = $transfer->total_amount;

            if ($payment->status === 'captured') {
                $payment->captured_at = $payment->authorized_at
                    ? (clone $payment->authorized_at)->modify('+1 hour')
                    : now();
            }

            if ($payment->status === 'refunded') {
                $payment->refunded_at = $payment->authorized_at
                    ? (clone $payment->authorized_at)->modify('+2 hours')
                    : now();
            }
        });
    }

    private function resolveCurrencyCode(): string
    {
        $code = Currency::query()->inRandomOrder()->value('code');

        if ($code) {
            return $code;
        }

        return Currency::factory()->create()->code;
    }
}
