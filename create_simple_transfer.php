<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Transfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Get the user
$user = User::where('email', 'chrisrizk@gmail.com')->first();
if (!$user) {
    echo "User not found.\n";
    exit(1);
}

// Generate a unique reference
$reference = 'TRF' . strtoupper(Str::random(10));

// Check if USD and EUR currencies exist, if not use any available
$currencyFrom = DB::table('currencies')->where('code', 'USD')->first();
$currencyTo = DB::table('currencies')->where('code', 'EUR')->first();

if (!$currencyFrom) {
    $currencyFrom = DB::table('currencies')->first();
}
if (!$currencyTo) {
    $currencyTo = DB::table('currencies')->where('code', '!=', $currencyFrom->code)->first() ?? $currencyFrom;
}

// Get or create required data
$country = DB::table('countries')->first();
if (!$country) {
    $countryId = DB::table('countries')->insertGetId([
        'name' => 'United States',
        'iso2' => 'US',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $country = (object)['id' => $countryId];
}

$transferMethod = DB::table('transfer_methods')->first();
if (!$transferMethod) {
    $methodId = DB::table('transfer_methods')->insertGetId([
        'name' => 'Bank Transfer',
        'code' => 'BANK',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $transferMethod = (object)['id' => $methodId];
}

// Get or create a beneficiary (minimal)
$beneficiary = DB::table('beneficiaries')->where('user_id', $user->id)->first();
if (!$beneficiary) {
    $beneficiaryId = DB::table('beneficiaries')->insertGetId([
        'user_id' => $user->id,
        'full_name' => 'Test Beneficiary',
        'country_id' => $country->id,
        'transfer_method_id' => $transferMethod->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
} else {
    $beneficiaryId = $beneficiary->id;
}

// Create transfer directly
$transferId = DB::table('transfers')->insertGetId([
    'sender_id' => $user->id,
    'beneficiary_id' => $beneficiaryId,
    'amount' => 100.00,
    'currency_from' => $currencyFrom->code,
    'currency_to' => $currencyTo->code,
    'exchange_rate' => 0.85,
    'fee' => 5.00,
    'total_amount' => 105.00,
    'status' => 'paid', // Status that allows cash_in
    'initiated_at' => now(),
    'reference' => $reference,
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "✅ Test transfer created successfully!\n";
echo "Reference: {$reference}\n";
echo "Status: paid\n";
echo "\n📋 Use this in your Postman request:\n";
echo json_encode([
    'transfer_reference' => $reference,
    'type' => 'cash_in'
], JSON_PRETTY_PRINT);
echo "\n";

