<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SaaSSubscriptionTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test POST /api/register/trial creates owner user and 14-day trial subscription.
     */
    public function test_can_register_trial_with_owner_role_and_14_days_subscription(): void
    {
        $payload = [
            'name' => 'Owner Trial',
            'email' => 'owner.trial@example.com',
            'password' => 'secret123',
            'nama_kos' => 'Kos Berkah Pintu Satu',
            'role' => 'admin', // Should be ignored and forced to 'owner'
        ];

        $response = $this->postJson('/api/register/trial', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Registrasi akun trial berhasil.',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email', 'role'],
                    'nama_kos',
                    'subscription' => ['id', 'user_id', 'package_name', 'status', 'starts_at', 'trial_ends_at', 'expires_at'],
                    'token',
                    'token_type',
                    'expires_in',
                ],
            ]);

        // Verifikasi role harus 'owner'
        $this->assertDatabaseHas('users', [
            'email' => 'owner.trial@example.com',
            'role' => 'owner',
        ]);

        // Verifikasi subscription trial
        $user = User::where('email', 'owner.trial@example.com')->first();
        $this->assertNotNull($user);

        $subscription = Subscription::where('user_id', $user->id)->first();
        $this->assertNotNull($subscription);
        $this->assertEquals('trial', $subscription->status);
        $this->assertNotNull($subscription->starts_at);
        $this->assertNotNull($subscription->trial_ends_at);
        $this->assertNull($subscription->expires_at);
    }

    /**
     * Test POST /api/register/paid creates owner user and active subscription with expires_at.
     */
    public function test_can_register_paid_with_monthly_package(): void
    {
        $payload = [
            'name' => 'Owner Paid',
            'email' => 'owner.paid@example.com',
            'password' => 'secret123',
            'nama_kos' => 'Kos Sultan Pintu Satu',
            'package_id' => 'monthly',
        ];

        $response = $this->postJson('/api/register/paid', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email', 'role'],
                    'subscription' => ['id', 'package_name', 'status', 'starts_at', 'trial_ends_at', 'expires_at'],
                    'token',
                ],
            ]);

        $user = User::where('email', 'owner.paid@example.com')->first();
        $this->assertEquals('owner', $user->role);

        $subscription = Subscription::where('user_id', $user->id)->first();
        $this->assertEquals('active', $subscription->status);
        $this->assertEquals('Paket Bulanan', $subscription->package_name);
        $this->assertNull($subscription->trial_ends_at);
        $this->assertNotNull($subscription->expires_at);
    }

    /**
     * Test GET /api/packages is accessible publicly without login token.
     */
    public function test_packages_endpoint_is_accessible_publicly(): void
    {
        $response = $this->getJson('/api/packages');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'price', 'duration_days', 'description'],
                ],
            ]);
    }

    /**
     * Test can register paid with integer package_id.
     */
    public function test_can_register_paid_with_numeric_package_id(): void
    {
        $package = Package::first();

        $payload = [
            'name' => 'Owner Numeric',
            'email' => 'owner.numeric@example.com',
            'password' => 'secret123',
            'nama_kos' => 'Kos Angka Pintu Satu',
            'package_id' => $package->id,
        ];

        $response = $this->postJson('/api/register/paid', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
            ]);
    }

    /**
     * Test POST /api/subscription/subscribe updates status to active and extends expiration.
     */
    public function test_user_can_subscribe_or_upgrade_package(): void
    {
        $user = User::factory()->create(['role' => 'owner']);
        $token = auth('api')->login($user);

        // User awalnya trial
        Subscription::create([
            'user_id' => $user->id,
            'package_name' => 'Trial 14 Hari',
            'status' => 'trial',
            'starts_at' => now(),
            'trial_ends_at' => now()->addDays(14),
            'expires_at' => null,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/subscription/subscribe', [
                'package_id' => 'yearly',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonPath('data.subscription.status', 'active')
            ->assertJsonPath('data.subscription.package_name', 'Paket Tahunan');

        $updatedSubscription = Subscription::where('user_id', $user->id)->first();
        $this->assertEquals('active', $updatedSubscription->status);
        $this->assertNull($updatedSubscription->trial_ends_at);
        $this->assertNotNull($updatedSubscription->expires_at);
    }
}
