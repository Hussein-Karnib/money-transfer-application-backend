<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserVerification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserVerification>
 */
class UserVerificationFactory extends Factory
{
    protected $model = UserVerification::class;

    public function definition(): array
    {
        $status = fake()->randomElement(['pending', 'approved', 'rejected']);

        return [
            'user_id' => User::factory(),
            'id_type' => fake()->randomElement(['passport', 'national_id', 'driver_license']),
            'id_number' => strtoupper(fake()->bothify('??######')),
            'document_path' => fake()->optional()->filePath(),
            'status' => $status,
            'verified_at' => $status === 'pending' ? null : now(),
        ];
    }
}
