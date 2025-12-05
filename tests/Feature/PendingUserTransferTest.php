<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PendingUserTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_user_cannot_access_transfer_summary()
    {
        // Ensure a role exists
        if (Role::count() === 0) {
            Role::factory()->create(['name' => 'User']);
        }

        $user = User::factory()->create([
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/transfers/summary?beneficiary_id=1&amount=100&currency_from=USD&currency_to=EUR');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Your account is pending approval. You cannot make transfers yet.',
            ]);
    }

    public function test_pending_user_cannot_create_transfer()
    {
        // Ensure a role exists
        if (Role::count() === 0) {
            Role::factory()->create(['name' => 'User']);
        }

        $user = User::factory()->create([
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/transfers', [
                'beneficiary_id' => 1,
                'amount' => 100,
                'currency_from' => 'USD',
                'currency_to' => 'EUR',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Your account is pending approval. You cannot make transfers yet.',
            ]);
    }

    public function test_active_user_can_access_transfer_summary()
    {
        // Ensure a role exists
        if (Role::count() === 0) {
            Role::factory()->create(['name' => 'User']);
        }

        $user = User::factory()->create([
            'status' => 'active',
        ]);

        // Note: This might fail with 422 or 500 if dependencies (beneficiary, etc.) are missing,
        // but it should NOT be 403.
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/transfers/summary?beneficiary_id=1&amount=100&currency_from=USD&currency_to=EUR');

        // We expect 422 because beneficiary_id=1 likely doesn't exist or belong to user
        // But crucially, NOT 403.
        $response->assertStatus(422); 
    }

    public function test_pending_user_web_request_redirects_back()
    {
        // Ensure a role exists
        if (Role::count() === 0) {
            Role::factory()->create(['name' => 'User']);
        }

        $user = User::factory()->create([
            'status' => 'pending',
        ]);

        // Make a standard request (not JSON)
        $response = $this->actingAs($user)
            ->from('/app/transfers/create') // Simulate coming from the create page
            ->post('/transfers', [
                'beneficiary_id' => 1,
                'amount' => 100,
                'currency_from' => 'USD',
                'currency_to' => 'EUR',
            ]);

        $response->assertStatus(302); // Redirect
        $response->assertRedirect('/app/transfers/create');
        $response->assertSessionHasErrors(['amount' => 'Your account is pending approval. You cannot make transfers yet.']);
    }
}
