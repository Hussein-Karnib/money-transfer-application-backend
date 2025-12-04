<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Transfer_Method;

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

        foreach ($methods as $method) {
            Transfer_Method::updateOrCreate(
                [
                    'name' => $method['name'],
                    'description' => $method['description'],
                ],
                $method
            );
        }
    }
}
