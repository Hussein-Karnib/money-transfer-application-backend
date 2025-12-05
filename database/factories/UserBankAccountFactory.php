<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\User;
use App\Models\UserBankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserBankAccount>
 */
class UserBankAccountFactory extends Factory
{
    protected $model = UserBankAccount::class;

    public function definition(): array
    {
        $status = fake()->randomElement(['pending', 'verified', 'rejected']);

        return [
            'user_id' => User::factory(),
            'bank_name' => fake()->company() . ' Bank',
            'account_number' => fake()->bankAccountNumber(),
            'currency_code' => $this->resolveCurrencyCode(),
            'status' => $status,
            'verified_at' => $status === 'verified' ? now() : null,
        ];
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
