<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Country;
use App\Models\Transfer_Method;
use App\Models\UserVerification;
use App\Models\Beneficiary;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed roles if necessary or create them
        if (Role::count() === 0) {
            Role::create(['id' => 1, 'name' => 'Admin']);
            Role::create(['id' => 2, 'name' => 'Agent']);
            Role::create(['id' => 3, 'name' => 'Customer']);
        }
    }

    public function test_audit_log_created_on_login()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
            'role_id' => 3
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'login',
            'table_name' => 'users',
            'record_id' => $user->id,
        ]);
    }

    public function test_audit_log_created_on_logout()
    {
        $user = User::factory()->create(['role_id' => 3]);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/logout');

        $response->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'logout',
            'table_name' => 'users',
            'record_id' => $user->id,
        ]);
    }

    public function test_audit_log_created_on_kyc_submission()
    {
        $user = User::factory()->create(['role_id' => 3]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/kyc', [
            'id_type' => 'passport',
            'id_number' => 'AB123456',
            'document_path' => 'test/path.jpg'
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'submit_kyc',
            'table_name' => 'user_verifications',
        ]);
    }

    public function test_audit_log_created_on_kyc_approval()
    {
        $admin = User::factory()->create(['role_id' => 1]); // Admin
        $user = User::factory()->create(['role_id' => 3]);
        
        $verification = UserVerification::create([
            'user_id' => $user->id,
            'id_type' => 'passport',
            'id_number' => 'AB123456',
            'status' => 'pending'
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/kyc/{$verification->id}/approve");

        $response->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'approve_kyc',
            'table_name' => 'user_verifications',
            'record_id' => $verification->id,
        ]);
    }

    public function test_audit_log_created_on_kyc_rejection()
    {
        $admin = User::factory()->create(['role_id' => 1]); // Admin
        $user = User::factory()->create(['role_id' => 3]);
        
        $verification = UserVerification::create([
            'user_id' => $user->id,
            'id_type' => 'passport',
            'id_number' => 'AB123456',
            'status' => 'pending'
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/kyc/{$verification->id}/reject");

        $response->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'reject_kyc',
            'table_name' => 'user_verifications',
            'record_id' => $verification->id,
        ]);
    }

    public function test_audit_log_created_on_beneficiary_creation()
    {
        $user = User::factory()->create(['role_id' => 3]);
        Sanctum::actingAs($user);

        $country = Country::factory()->create();
        $method = Transfer_Method::factory()->create();

        $response = $this->postJson('/api/beneficiaries', [
            'full_name' => 'John Doe',
            'country_id' => $country->id,
            'transfer_method_id' => $method->id,
            'payout_details' => ['account_number' => '1234567890']
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'create_beneficiary',
            'table_name' => 'beneficiaries',
        ]);
    }

    public function test_audit_log_created_on_transfer_creation_with_sender_info()
    {
        $user = User::factory()->create(['role_id' => 3, 'status' => 'active']);
        Sanctum::actingAs($user);

        $country = Country::factory()->create();
        $method = Transfer_Method::factory()->create();
        $beneficiary = Beneficiary::factory()->create([
            'user_id' => $user->id,
            'country_id' => $country->id,
            'transfer_method_id' => $method->id
        ]);
        
        // Ensure currency exists
        \App\Models\Currency::factory()->create(['code' => 'USD']);
        \App\Models\Currency::factory()->create(['code' => 'EUR']);

        $response = $this->postJson('/api/transfers', [
            'beneficiary_id' => $beneficiary->id,
            'amount' => 100,
            'currency_from' => 'USD',
            'currency_to' => 'EUR',
            'speed' => 'standard',
        ]);

        $response->assertStatus(302); // Redirects on success

        $log = AuditLog::where('action', 'create_transfer')->latest()->first();
        
        $this->assertNotNull($log);
        $this->assertEquals($user->id, $log->user_id);
        $this->assertEquals($user->name, $log->metadata['sender_name']);
        $this->assertEquals($user->email, $log->metadata['sender_email']);
    }
}
