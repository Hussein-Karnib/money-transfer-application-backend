<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'name' => 'role_' . fake()->unique()->lexify('????'),
            'permissions' => fake()->optional()->randomElement([
                null,
                ['can_manage_transfers' => true],
                ['can_manage_users' => true, 'can_manage_agents' => fake()->boolean()],
            ]),
        ];
    }
}
