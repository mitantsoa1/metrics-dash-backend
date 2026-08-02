<?php

namespace Tests\Feature;

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
        ];

        $registerRes = $this->postJson('/api/v1/tenants/register', $registerPayload);

        $registerRes->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'organizationName',
                'slug',
                'ownerUserId',
                'ownerEmail',
                'status',
                'token',
            ]);

        $tenantId = $registerRes->json('id');
        $ownerToken = $registerRes->json('token');
        $this->assertNotEmpty($tenantId);
        $this->assertNotEmpty($ownerToken);

        // 2. Authentification Login sur l'endpoint de connexion
        $loginPayload = [
            'email' => 'ainamitantso@gmail.com',
            'password' => '123456789Ca!',
        ];

        $loginRes = $this->postJson('/api/v1/auth/login', $loginPayload);
        $loginRes->assertSuccessful()
            ->assertJsonStructure([
                'token',
                'tokenType',
                'userId',
                'tenantId',
                'role',
                'email',
            ]);

        // 3. Ajout d'un membre avec rôle ANALYST
        $addMemberPayload = [
            'name' => 'Jean Analyste',
            'email' => 'jean.analyst@gmail.com',
            'password' => 'Password123!',
            'role' => 'ANALYST',
        ];

        $addMemberRes = $this->withHeader('Authorization', 'Bearer '.$ownerToken)
            ->postJson("/api/v1/tenants/{$tenantId}/members", $addMemberPayload);

        $addMemberRes->assertStatus(201)
            ->assertJsonStructure([
                'userId',
                'tenantId',
                'name',
                'email',
                'role',
            ]);

        // 4. Récupération de la liste des membres du Tenant
        $listMembersRes = $this->withHeader('Authorization', 'Bearer '.$ownerToken)
            ->getJson("/api/v1/tenants/{$tenantId}/members");

        $listMembersRes->assertStatus(200);
        $this->assertCount(2, $listMembersRes->json());

        // 5. Création d'une Instance Client (Site E-Commerce)
        $createInstancePayload = [
            'name' => 'Boutique E-Commerce Live',
            'domain' => 'shop.richardson.com',
            'environment' => 'PRODUCTION',
        ];

        $createInstanceRes = $this->withHeader('Authorization', 'Bearer '.$ownerToken)
            ->postJson('/api/v1/instances', $createInstancePayload);

        $createInstanceRes->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'tenantId',
                'name',
                'domain',
                'environment',
                'publicSdkKey',
                'privatePushKey',
            ]);

        $instanceId = $createInstanceRes->json('id');
        $publicSdkKey = $createInstanceRes->json('publicSdkKey');
        $privatePushKey = $createInstanceRes->json('privatePushKey');

        $this->assertStringStartsWith('pk_live_', $publicSdkKey);
        $this->assertStringStartsWith('sk_live_', $privatePushKey);

        // 6. Récupération de la liste des instances du Tenant
        $listInstancesRes = $this->withHeader('Authorization', 'Bearer '.$ownerToken)
            ->getJson("/api/v1/tenants/{$tenantId}/instances");

        $listInstancesRes->assertStatus(200);
        $this->assertCount(1, $listInstancesRes->json());

        // 7. Rotation de la Clé API Publique SDK
        $rotateRes = $this->withHeader('Authorization', 'Bearer '.$ownerToken)
            ->postJson("/api/v1/instances/{$instanceId}/rotate-keys", [
                'keyType' => 'PUBLIC_SDK',
            ]);

        $rotateRes->assertSuccessful()
            ->assertJsonStructure([
                'id',
                'rotatedKeyType',
                'newPlainKey',
            ]);

        $newKey = $rotateRes->json('newPlainKey');
        $this->assertStringStartsWith('pk_live_', $newKey);
        $this->assertNotEquals($publicSdkKey, $newKey);
    }
}
