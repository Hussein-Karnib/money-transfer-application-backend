<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Transfer_Fee;
use Illuminate\Database\Seeder;

class TransferFeeSeeder extends Seeder
{
    public function run(): void
    {
        $pairs = [
            ['from' => 'US', 'to' => 'LB', 'fee_fixed' => 5.00, 'fee_percent' => 1.25],
            ['from' => 'US', 'to' => 'EG', 'fee_fixed' => 3.50, 'fee_percent' => 1.10],
            ['from' => 'US', 'to' => 'AE', 'fee_fixed' => 2.00, 'fee_percent' => 0.90],
            ['from' => 'GB', 'to' => 'IN', 'fee_fixed' => 4.00, 'fee_percent' => 1.40],
            ['from' => 'GB', 'to' => 'JP', 'fee_fixed' => 4.50, 'fee_percent' => 1.35],
            ['from' => 'AE', 'to' => 'LB', 'fee_fixed' => 2.50, 'fee_percent' => 0.80],
        ];

        foreach ($pairs as $pair) {
            $fromId = Country::where('iso2', $pair['from'])->value('id');
            $toId = Country::where('iso2', $pair['to'])->value('id');

            if (! $fromId || ! $toId) {
                continue;
            }

            Transfer_Fee::updateOrCreate(
                [
                    'country_from_id' => $fromId,
                    'country_to_id' => $toId,
                    'min_amount' => 0,
                    'max_amount' => 10000,
                ],
                [
                    'fee_fixed' => $pair['fee_fixed'],
                    'fee_percent' => $pair['fee_percent'],
                    'last_updated' => now(),
                ]
            );
        }
    }
}
