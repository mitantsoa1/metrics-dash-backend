<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\IAM\Infrastructure\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_cookies(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'organizationName' => 'Acme Analytics',
            'ownerName' => 'Alice Admin',
            'ownerEmail' => 'alice@acme.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'alice@acme.com')
            ->assertCookie('access_token')
            ->assertCookie('refresh_token');

        $this->assertDatabaseHas('users', ['email' => 'alice@acme.com']);
        $this->assertDatabaseHas('tenants', ['name' => 'Acme Analytics']);
    }

    public function test_user_can_login_and_receive_http_only_cookies(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'organizationName' => 'Acme Corp',
            'ownerName' => 'Bob Test',
            'ownerEmail' => 'bob@acme.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'bob@acme.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertCookie('access_token')
            ->assertCookie('refresh_token');
    }

    public function test_user_can_access_me_endpoint(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'organizationName' => 'Charlie Org',
            'ownerName' => 'Charlie',
            'ownerEmail' => 'charlie@acme.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $user = UserModel::where('email', 'charlie@acme.com')->firstOrFail();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'charlie@acme.com');
    }

    public function test_user_can_refresh_token(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'organizationName' => 'Delta Org',
            'ownerName' => 'Delta',
            'ownerEmail' => 'delta@acme.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $user = UserModel::where('email', 'delta@acme.com')->firstOrFail();
        $tokenService = app(\App\Domains\IAM\Services\TokenService::class);
        $pair = $tokenService->generateTokenPair($user);

        $response = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $pair['refresh_token'],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertCookie('access_token')
            ->assertCookie('refresh_token');
    }

    public function test_user_can_logout(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'organizationName' => 'Echo Org',
            'ownerName' => 'Echo',
            'ownerEmail' => 'echo@acme.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $user = UserModel::where('email', 'echo@acme.com')->firstOrFail();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
