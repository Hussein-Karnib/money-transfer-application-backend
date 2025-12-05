<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Promotion;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        $lebanonId = Country::where('iso2', 'LB')->value('id');
        $usId = Country::where('iso2', 'US')->value('id');

        $promos = [
            [
                'code' => 'WELCOME10',
                'description' => '10% off fees to Lebanon',
                'discount_type' => 'percent',
                'discount_value' => 10.00,
                'max_discount' => 5000.00,
                'min_amount' => 0.00,
                'country_to_id' => $lebanonId,
                'starts_at' => Carbon::parse('2025-12-03 16:25:50'),
                'ends_at' => Carbon::parse('2026-01-04 16:25:50'),
                'usage_limit' => null,
                'used_count' => 0,
                'active' => true,
            ],
            [
                'code' => 'GLOBAL5',
                'description' => '5 units off transfer fees globally',
                'discount_type' => 'fixed',
                'discount_value' => 5.00,
                'max_discount' => null,
                'min_amount' => 0.00,
                'country_to_id' => null,
                'starts_at' => Carbon::parse('2025-12-03 16:25:50'),
                'ends_at' => Carbon::parse('2026-01-04 16:25:50'),
                'usage_limit' => null,
                'used_count' => 0,
                'active' => true,
            ],
            [
                'code' => 'USONLY5',
                'description' => '5% off fees to United States',
                'discount_type' => 'percent',
                'discount_value' => 5.00,
                'max_discount' => 3000.00,
                'min_amount' => 0.00,
                'country_to_id' => $usId,
                'starts_at' => Carbon::parse('2025-12-03 16:25:50'),
                'ends_at' => Carbon::parse('2026-01-04 16:25:50'),
                'usage_limit' => 100,
                'used_count' => 0,
                'active' => true,
            ],
        ];

        foreach ($promos as $data) {
            Promotion::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }
    }
}
