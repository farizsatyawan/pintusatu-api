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
    /**
     * Test POST /api/register/paid creates user with pending_payment and order with snap_token.
     */
    public function test_can_register_paid_with_monthly_package(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            '*midtrans.com*' => \Illuminate\Support\Facades\Http::response([
                'token' => 'mock-snap-token-12345',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/mock-snap-token-12345',
            ], 201),
        ]);

        $package = Package::first();

        $payload = [
            'name' => 'Owner Paid',
            'email' => 'owner.paid@example.com',
            'password' => 'secret123',
            'nama_kos' => 'Kos Sultan Pintu Satu',
            'package_id' => $package->id,
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
                    'user' => ['id', 'name', 'email', 'role', 'status', 'is_active'],
                    'nama_kos',
                    'order' => ['id', 'order_id', 'package_name', 'gross_amount', 'status', 'snap_token', 'snap_redirect_url'],
                ],
            ]);

        $user = User::where('email', 'owner.paid@example.com')->first();
        $this->assertEquals('owner', $user->role);
        $this->assertEquals('pending_payment', $user->status);
        $this->assertFalse($user->is_active);

        // Verifikasi bahwa user pending_payment TIDAK BISA login
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'owner.paid@example.com',
            'password' => 'secret123',
        ]);
        $loginResponse->assertStatus(403);
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
        \Illuminate\Support\Facades\Http::fake([
            '*midtrans.com*' => \Illuminate\Support\Facades\Http::response([
                'token' => 'mock-snap-token-numeric',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/mock-snap-token-numeric',
            ], 201),
        ]);

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

        $targetPackage = Package::skip(1)->first() ?? Package::first();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/subscription/subscribe', [
                'package_id' => $targetPackage->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonPath('data.subscription.status', 'active')
            ->assertJsonPath('data.subscription.package_name', $targetPackage->name);

        $updatedSubscription = Subscription::where('user_id', $user->id)->first();
        $this->assertEquals('active', $updatedSubscription->status);
        $this->assertNull($updatedSubscription->trial_ends_at);
        $this->assertNotNull($updatedSubscription->expires_at);
    }

    /**
     * Test POST /api/midtrans/notification updates order to paid, activates user, and creates subscription.
     */
    public function test_midtrans_notification_activates_user_and_subscription(): void
    {
        $package = Package::first();
        $user = User::factory()->create([
            'role' => 'owner',
            'status' => 'pending_payment',
            'is_active' => false,
        ]);

        $orderId = 'ORDER-TEST-'.time();
        $grossAmount = (string) $package->price;
        $order = \App\Models\Order::create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'order_id' => $orderId,
            'gross_amount' => $package->price,
            'status' => 'pending',
            'snap_token' => 'mock-token',
        ]);

        $serverKey = config('midtrans.server_key');
        $statusCode = '200';
        $signature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        $payload = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signature,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
        ];

        $response = $this->postJson('/api/midtrans/notification', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Notifikasi Midtrans berhasil diproses.',
            ]);

        // Verifikasi Order paid
        $this->assertEquals('paid', $order->fresh()->status);

        // Verifikasi User aktif dan sekarang BISA login
        $userFresh = $user->fresh();
        $this->assertEquals('active', $userFresh->status);
        $this->assertTrue($userFresh->is_active);

        // Verifikasi Subscription aktif
        $subscription = Subscription::where('user_id', $user->id)->first();
        $this->assertNotNull($subscription);
        $this->assertEquals('active', $subscription->status);
        $this->assertEquals($package->name, $subscription->package_name);
        $this->assertNotNull($subscription->expires_at);
    }
}
