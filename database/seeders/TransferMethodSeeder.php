<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransferMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'name' => 'Bank Deposit',
                'description' => 'Send directly to a bank account',
            ],
            [
                'name' => 'Cash Pickup',
                'description' => 'Pickup cash at an agent location',
            ],
            [
                'name' => 'Bank Transfer',
                'description' => 'Traditional bank-to-bank transfer.',
            ],
            [
                'name' => 'Cash Pickup',
                'description' => 'Receiver collects cash from a partner location.',
            ],
            [
                'name' => 'Mobile Wallet',
                'description' => 'Funds delivered to a mobile money wallet.',
            ],
            [
                'name' => 'ATM Withdrawal',
                'description' => 'Cash withdrawal using a code at ATM.',
            ],
            [
                'name' => 'Card-to-Card Transfer',
                'description' => 'Direct transfer between debit/credit cards.',
            ],
        ];

        DB::table('transfer_methods')->insert($methods);
    }
}

