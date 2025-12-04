<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['iso2' => 'LB', 'name' => 'Lebanon'],
            ['iso2' => 'US', 'name' => 'United States'],
            ['iso2' => 'GB', 'name' => 'United Kingdom'],
            ['iso2' => 'FR', 'name' => 'France'],
            ['iso2' => 'DE', 'name' => 'Germany'],
            ['iso2' => 'CA', 'name' => 'Canada'],
            ['iso2' => 'AU', 'name' => 'Australia'],
            ['iso2' => 'SA', 'name' => 'Saudi Arabia'],
            ['iso2' => 'AE', 'name' => 'United Arab Emirates'],
            ['iso2' => 'TR', 'name' => 'Turkey'],
            ['iso2' => 'QA', 'name' => 'Qatar'],
            ['iso2' => 'EG', 'name' => 'Egypt'],
            ['iso2' => 'JO', 'name' => 'Jordan'],
            ['iso2' => 'CY', 'name' => 'Cyprus'],
            ['iso2' => 'ES', 'name' => 'Spain'],
            ['iso2' => 'IT', 'name' => 'Italy'],
            ['iso2' => 'NL', 'name' => 'Netherlands'],
            ['iso2' => 'CH', 'name' => 'Switzerland'],
            ['iso2' => 'BR', 'name' => 'Brazil'],
            ['iso2' => 'AR', 'name' => 'Argentina'],
            ['iso2' => 'IN', 'name' => 'India'],
            ['iso2' => 'CN', 'name' => 'China'],
            ['iso2' => 'JP', 'name' => 'Japan'],
            ['iso2' => 'KR', 'name' => 'South Korea'],
        ];

        DB::table('countries')->insert($countries);
    }
}
