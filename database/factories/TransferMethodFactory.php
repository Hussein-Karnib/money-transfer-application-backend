<?php

namespace Database\Factories;

use App\Models\Transfer_Method;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transfer_Method>
 */
class TransferMethodFactory extends Factory
{
    protected $model = Transfer_Method::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'Bank Deposit',
                'Cash Pickup',
                'Mobile Wallet',
                'ATM Withdrawal',
                'Card-to-Card Transfer',
            ]),
            'description' => fake()->sentence(),
        ];
    }
}
