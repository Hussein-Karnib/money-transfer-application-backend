<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Promotion;
use App\Models\Country;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        $now   = now();
        $start = $now->copy()->subDay();       // started yesterday
        $end   = $now->copy()->addMonths(1);   // valid for 1 month

        // Look up country IDs by ISO2 (adjust if your country codes differ)
        $lebanonId = Country::where('iso2', 'LB')->value('id');
        $usId      = Country::where('iso2', 'US')->value('id');

        $promos = [
            // 1) Lebanon-only, percent on fee
            [
                'code'          => 'WELCOME10',
                'description'   => '10% off fees to Lebanon',
                'discount_type' => 'percent',  // percent / fixed
                'discount_value'=> 10,         // 10% of fee
                'max_discount'  => 5000,       // cap discount on big fees
                'min_amount'    => 0,
                'country_to_id' => $lebanonId, 
                'starts_at'     => $start,
                'ends_at'       => $end,
                'usage_limit'   => null,       // unlimited
                'used_count'    => 0,
                'active'        => true,
            ],

            // 2) Global fixed discount on fee
            [
                'code'          => 'GLOBAL5',
                'description'   => '5 units off transfer fees globally',
                'discount_type' => 'fixed',
                'discount_value'=> 5,          // 5 (same currency as fee)
                'max_discount'  => null,       // no extra cap
                'min_amount'    => 0,
                'country_to_id' => null,       // 👈 works for all countries
                'starts_at'     => $start,
                'ends_at'       => $end,
                'usage_limit'   => null,
                'used_count'    => 0,
                'active'        => true,
            ],

            // 3) US-only small percent discount
            [
                'code'          => 'USONLY5',
                'description'   => '5% off fees to United States',
                'discount_type' => 'percent',
                'discount_value'=> 5,
                'max_discount'  => 3000,
                'min_amount'    => 0,
                'country_to_id' => $usId,     
                'starts_at'     => $start,
                'ends_at'       => $end,
                'usage_limit'   => 100,
                'used_count'    => 0,
                'active'        => true,
            ],
        ];

        foreach ($promos as $data) {
            Promotion::updateOrCreate(
                ['code' => $data['code']], // key for upsert
                $data
            );
        }
    }
}
