<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful user registration.
     */
    public function test_user_can_register_successfully(): void
    {
        $payload = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Registrasi berhasil.',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email', 'created_at', 'updated_at'],
                    'token',
                    'token_type',
                    'expires_in',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);
    }

    /**
     * Test registration validation with duplicate email.
     */
    public function test_user_cannot_register_with_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $payload = [
            'name' => 'Duplicate User',
            'email' => 'existing@example.com',
            'password' => 'secret123',
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Validasi gagal.',
            ])
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test registration validation with password shorter than 6 characters.
     */
    public function test_user_cannot_register_with_short_password(): void
    {
        $payload = [
            'name' => 'Short Pass',
            'email' => 'short@example.com',
            'password' => '12345',
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Validasi gagal.',
            ])
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test user login with valid credentials.
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'secret123',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Login berhasil.',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token',
                    'token_type',
                    'expires_in',
                ],
            ]);
    }

    /**
     * Test user login with incorrect password.
     */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'wrong@example.com',
            'password' => 'secret123',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'wrong@example.com',
            'password' => 'incorrect-pass',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => 'Email atau password salah.',
            ]);
    }

    /**
     * Test authenticated user fetching profile data.
     */
    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Profile User',
            'email' => 'profile@example.com',
            'password' => 'secret123',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Data profil berhasil diambil.',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => 'Profile User',
                        'email' => 'profile@example.com',
                    ],
                ],
            ]);
    }

    /**
     * Test unauthorized access to profile endpoint.
     */
    public function test_unauthenticated_user_cannot_access_profile(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
            ]);
    }

    /**
     * Test user logout invalidates the token.
     */
    public function test_user_can_logout_and_token_is_invalidated(): void
    {
        $user = User::factory()->create([
            'email' => 'logout@example.com',
            'password' => 'secret123',
        ]);

        $token = auth('api')->login($user);

        $logoutResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout');

        $logoutResponse->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Logout berhasil. Token telah dinonaktifkan.',
            ]);

        // Accessing /api/me with the logged out token should fail
        $profileResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/me');

        $profileResponse->assertStatus(401);
    }
}
