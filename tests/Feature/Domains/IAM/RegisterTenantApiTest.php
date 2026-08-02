<?php

namespace Tests\Feature\Domains\IAM;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTenantApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_can_register_new_tenant(): void
    {
        $payload = [
            'organizationName' => 'Acme Analytics',
            'ownerName' => 'Alice Founder',
            'ownerEmail' => 'alice@acme-analytics.com',
            'password' => 'SecurePass123!',
        ];

        $response = $this->postJson('/api/v1/tenants/register', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'organizationName',
                'slug',
                'ownerUserId',
                'ownerEmail',
                'status',
                'token',
            ]);

        $this->assertDatabaseHas('tenants', [
            'name' => 'Acme Analytics',
            'slug' => 'acme-analytics',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'alice@acme-analytics.com',
            'role' => 'OWNER',
        ]);
    }

    public function test_api_cannot_register_with_duplicate_email(): void
    {
        $payload = [
            'organizationName' => 'First Corp',
            'ownerName' => 'Bob',
            'ownerEmail' => 'bob@corp.com',
            'password' => 'SecurePass123!',
        ];

        $this->postJson('/api/v1/tenants/register', $payload);

        $secondPayload = [
            'organizationName' => 'Second Corp',
            'ownerName' => 'Bob Duplicate',
            'ownerEmail' => 'bob@corp.com',
            'password' => 'SecurePass123!',
        ];

        $response = $this->postJson('/api/v1/tenants/register', $secondPayload);
        $response->assertStatus(500); // Exception is thrown
    }
}
