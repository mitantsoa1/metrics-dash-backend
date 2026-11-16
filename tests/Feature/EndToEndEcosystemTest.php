<?php

namespace Tests\Feature;

use App\Domains\IAM\Infrastructure\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EndToEndEcosystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_ecosystem_flow_registration_iam_instances(): void
    {
        // 1. Inscription d'un nouveau Tenant et compte Owner
        $registerPayload = [
            'organizationName' => 'Richardson Sandoval Associates',
            'ownerName' => 'MacKensie Morales',
            'ownerEmail' => 'ainamitantso@gmail.com',
            'password' => '123456789Ca!',
            'password_confirmation' => '123456789Ca!',
        ];

        $registerRes = $this->postJson('/api/v1/auth/register', $registerPayload);

        $registerRes->assertStatus(201)
            ->assertJsonPath('success', true);

        $tenantId = $registerRes->json('data.tenant.id');
        $this->assertNotEmpty($tenantId);

        $user = UserModel::where('email', 'ainamitantso@gmail.com')->firstOrFail();

        // 2. Authentification Login sur l'endpoint de connexion
        $loginPayload = [
            'email' => 'ainamitantso@gmail.com',
            'password' => '123456789Ca!',
        ];

        $loginRes = $this->postJson('/api/v1/auth/login', $loginPayload);
        $loginRes->assertSuccessful()
            ->assertJsonPath('success', true)
            ->assertCookie('access_token');

        // 3. Ajout d'un membre avec rôle ANALYST
        $addMemberPayload = [
            'name' => 'Jean Analyste',
            'email' => 'jean.analyst@gmail.com',
            'password' => 'Password123!',
            'role' => 'ANALYST',
        ];

        $addMemberRes = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/tenants/{$tenantId}/members", $addMemberPayload);

        $addMemberRes->assertStatus(201);

        // 4. Récupération de la liste des membres du Tenant
        $listMembersRes = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/tenants/{$tenantId}/members");

        $listMembersRes->assertStatus(200);

        // 5. Création d'une Instance Client (Site E-Commerce)
        $createInstancePayload = [
            'name' => 'Boutique E-Commerce Live',
            'domain' => 'shop.richardson.com',
            'environment' => 'PRODUCTION',
        ];

        $createInstanceRes = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/instances', $createInstancePayload);

        $createInstanceRes->assertStatus(201);
    }
}
