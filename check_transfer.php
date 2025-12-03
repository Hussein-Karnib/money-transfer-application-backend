<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Transfer;
use Illuminate\Support\Facades\DB;

$reference = 'TRFMYEJY8OZKC';

// Check if transfer exists
$transfer = Transfer::where('reference', $reference)->first();

if ($transfer) {
    echo "✅ Transfer found!\n";
    echo "Reference: {$transfer->reference}\n";
    echo "Status: {$transfer->status}\n";
    echo "ID: {$transfer->id}\n";
} else {
    echo "❌ Transfer not found with reference: {$reference}\n";
    echo "\nChecking all transfers in database:\n";
    
    $all = Transfer::all(['id', 'reference', 'status']);
    echo "Total transfers: {$all->count()}\n";
    
    if ($all->count() > 0) {
        foreach ($all as $t) {
            echo "ID: {$t->id}, Reference: {$t->reference}, Status: {$t->status}\n";
        }
    } else {
        echo "No transfers found in database.\n";
    }
}

// Also check directly in DB
echo "\nDirect DB check:\n";
$dbCheck = DB::table('transfers')->where('reference', $reference)->first();
if ($dbCheck) {
    echo "Found in DB: Reference = {$dbCheck->reference}\n";
} else {
    echo "Not found in DB table.\n";
}

