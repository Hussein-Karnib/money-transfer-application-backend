<?php

/*
namespace Database\Seeders;

use App\Models\Exchange_Rate;
use Illuminate\Database\Seeder;

class ExchangeRateSeeder extends Seeder
{
    public function run(): void
    {
        $rates = [
            ['currency_from' => 'USD', 'currency_to' => 'LBP', 'rate' => 90000.00000000],
            ['currency_from' => 'USD', 'currency_to' => 'EUR', 'rate' => 0.92],
            ['currency_from' => 'EUR', 'currency_to' => 'USD', 'rate' => 1.09],
            ['currency_from' => 'USD', 'currency_to' => 'GBP', 'rate' => 0.80],
            ['currency_from' => 'USD', 'currency_to' => 'AED', 'rate' => 3.67],
            ['currency_from' => 'USD', 'currency_to' => 'SAR', 'rate' => 3.75],
            ['currency_from' => 'USD', 'currency_to' => 'QAR', 'rate' => 3.64],
            ['currency_from' => 'USD', 'currency_to' => 'JPY', 'rate' => 150.00],
            ['currency_from' => 'USD', 'currency_to' => 'INR', 'rate' => 83.00],
            ['currency_from' => 'USD', 'currency_to' => 'TRY', 'rate' => 32.00],
        ];

        foreach ($rates as $data) {
            Exchange_Rate::updateOrCreate(
                [
                    'currency_from' => $data['currency_from'],
                    'currency_to' => $data['currency_to'],
                ],
                $data
            );
        }
    }
}
*/
