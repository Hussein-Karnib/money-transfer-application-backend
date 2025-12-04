<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run()
    {
        $currencies = require database_path('seeders/data/currencies.php');

        $timestamp = now();

        $currencies = array_map(fn (array $currency) => $currency + [
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ], $currencies);

        Currency::upsert(
            $currencies,
            ['code'],
            ['name', 'decimals', 'updated_at']
        );
    }
}
