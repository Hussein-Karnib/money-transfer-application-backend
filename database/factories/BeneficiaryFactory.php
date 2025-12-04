<?php

namespace Database\Factories;

use App\Models\Beneficiary;
use App\Models\Country;
use App\Models\Transfer_Method;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Beneficiary>
 */
class BeneficiaryFactory extends Factory
{
    protected $model = Beneficiary::class;

    public function definition(): array
    {
        $countryId = Country::query()->inRandomOrder()->value('id') ?? Country::factory()->create()->id;
        $methodId = Transfer_Method::query()->inRandomOrder()->value('id') ?? Transfer_Method::factory()->create()->id;

        return [
            'user_id' => User::factory(),
            'full_name' => fake()->name(),
            'country_id' => $countryId,
            'transfer_method_id' => $methodId,
            'payout_details' => [
                'bank_name' => fake()->company(),
                'account_number' => fake()->bankAccountNumber(),
                'instructions' => fake()->sentence(),
            ],
        ];
    }
}
