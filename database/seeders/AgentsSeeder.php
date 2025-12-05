<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Agent_Hour;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AgentsSeeder extends Seeder
{
    public function run(): void
    {
        $agentRoleId = Role::query()->where('name', 'agent')->value('id');

        $agents = [
            [
                'user' => [
                    'name' => 'Beirut Agent',
                    'email' => 'beirut.agent@example.com',
                ],
                'store_name' => 'Beirut Central Transfers',
                'address' => 'Beirut Souks, Beirut, Lebanon',
                'latitude' => 33.8983,
                'longitude' => 35.5045,
            ],
            [
                'user' => [
                    'name' => 'NYC Agent',
                    'email' => 'nyc.agent@example.com',
                ],
                'store_name' => 'Hudson Transfer Hub',
                'address' => '200 Hudson St, New York, NY, USA',
                'latitude' => 40.7233,
                'longitude' => -74.0090,
            ],
            [
                'user' => [
                    'name' => 'Dubai Agent',
                    'email' => 'dubai.agent@example.com',
                ],
                'store_name' => 'Dubai Marina Transfers',
                'address' => 'Dubai Marina Mall, Dubai, UAE',
                'latitude' => 25.0773,
                'longitude' => 55.1390,
            ],
            [
                'user' => [
                    'name' => 'London Agent',
                    'email' => 'london.agent@example.com',
                ],
                'store_name' => 'Canary Wharf Transfers',
                'address' => '1 Canada Square, London, UK',
                'latitude' => 51.5054,
                'longitude' => -0.0235,
            ],
        ];

        foreach ($agents as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['user']['email']],
                [
                    'name' => $data['user']['name'],
                    'password' => bcrypt('Password123!'),
                    'role_id' => $agentRoleId ?? 2,
                    'status' => 'approved',
                    'email_verified_at' => now(),
                ]
            );

            $agent = Agent::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'store_name' => $data['store_name'],
                    'address' => $data['address'],
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'status' => 'approved',
                    'commission_rate' => 0.0100,
                    'balance' => 100000.00, // Start with $100,000
                ]
            );

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
