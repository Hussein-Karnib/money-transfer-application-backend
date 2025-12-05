<?php

namespace Database\Seeders;

use App\Models\Agent_Transaction;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\ChatMessage;
use App\Models\Payment;
use App\Models\Report;
use App\Models\Transfer;
use App\Models\Transfer_Event;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::factory()->count(8)->create();

        Beneficiary::factory()->count(6)->create();

        $transfers = Transfer::factory()->count(10)->create();

        foreach ($transfers as $transfer) {
            Payment::factory()->for($transfer)->create();
            Transfer_Event::factory()->count(2)->for($transfer)->create();
        }

        Agent_Transaction::factory()->count(8)->create();

        ChatMessage::factory()
            ->count(15)
            ->state(fn () => [
                'sender_id' => $users->random()->id,
                'receiver_id' => $users->random()->id,
            ])
            ->create();

        Report::factory()->count(4)->create();

        AuditLog::factory()->count(12)->create();
    }
}
