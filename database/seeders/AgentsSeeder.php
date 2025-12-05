<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Agent_Hour;
use Illuminate\Database\Seeder;

class AgentsSeeder extends Seeder
{
    public function run(): void
    {
        $agents = Agent::factory()
            ->count(3)
            ->state(['status' => 'approved'])
            ->create();

        foreach ($agents as $agent) {
            foreach (range(0, 6) as $day) {
                Agent_Hour::updateOrCreate(
                    [
                        'agent_id' => $agent->id,
                        'day_of_week' => $day,
                    ],
                    [
                        'open_time' => '09:00:00',
                        'close_time' => '17:00:00',
                        'is_closed' => false,
                    ]
                );
            }
        }
    }
}
