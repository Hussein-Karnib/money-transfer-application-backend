<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Admin>
 */
class AdminFactory extends Factory
{
    protected $model = Admin::class;

    public function definition(): array
    {
        $adminRoleId = $this->adminRoleId();

        return [
            'user_id' => User::factory()->state(['role_id' => $adminRoleId]),
            'privilege_level' => fake()->numberBetween(1, 3),
        ];
    }

    private function adminRoleId(): int
    {
        $role = Role::query()->where('name', 'admin')->first();

        if ($role) {
            return $role->id;
        }

        return Role::factory()->create(['name' => 'admin'])->id;
    }
}
