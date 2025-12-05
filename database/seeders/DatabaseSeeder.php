<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
            UsersSeeder::class,
            AdminsSeeder::class,
            CountrySeeder::class,
            CurrencySeeder::class,
            TransferMethodSeeder::class,
            PromotionSeeder::class,
            ExchangeRateSeeder::class,
            TransferFeeSeeder::class,
            LocationsSeeder::class,
            AgentsSeeder::class,
            DemoDataSeeder::class,
        ]);

    }
    
}
