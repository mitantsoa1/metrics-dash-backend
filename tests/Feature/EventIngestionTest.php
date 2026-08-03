<?php

namespace Tests\Feature;

use App\Domains\IAM\DTOs\RegisterTenantInputDTO;
use App\Domains\IAM\Services\TenantRegistrationService;
use App\Domains\Instance\DTOs\CreateInstanceInputDTO;
use App\Domains\Instance\Services\InstanceProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventIngestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_ingest_sdk_event_with_invalid_key(): void
    {
        $response = $this->postJson('/api/v1/telemetry/sdk', [
            'eventName' => 'page_view',
            'publicSdkKey' => 'pk_live_invalid_key_12345'
        ]);

        $response->assertStatus(401);
    }

    public function test_can_ingest_sdk_event_with_valid_key(): void
    {
        /** @var TenantRegistrationService $tenantService */
        $tenantService = app(TenantRegistrationService::class);
        $tenantDto = $tenantService->register(new RegisterTenantInputDTO(
            organizationName: 'Acme Test',
            ownerName: 'Alice Owner',
            ownerEmail: 'alice.test@acme.com',
            password: 'Password123!'
        ));

        /** @var InstanceProvisioningService $instanceService */
        $instanceService = app(InstanceProvisioningService::class);
        $instanceDto = $instanceService->createInstance(new CreateInstanceInputDTO(
            tenantId: $tenantDto->tenantId,
            name: 'App Web Test',
            domain: 'test.acme.com',
            environment: 'PRODUCTION'
        ));

        $publicKey = $instanceDto->apiKeys->publicSdkKey;

        $response = $this->postJson('/api/v1/telemetry/sdk', [
            'publicSdkKey' => $publicKey,
            'eventName' => 'page_view',
            'visitorId' => 'vis_998877',
            'sessionId' => 'sess_112233',
            'payload' => ['path' => '/home', 'title' => 'Accueil']
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['eventId', 'instanceId', 'eventName', 'status']);

        $this->assertDatabaseHas('raw_events', [
            'instance_id' => $instanceDto->id,
            'event_type' => 'NAVIGATION',
            'event_name' => 'page_view'
        ]);
    }

    public function test_can_ingest_push_event_with_valid_key(): void
    {
        /** @var TenantRegistrationService $tenantService */
        $tenantService = app(TenantRegistrationService::class);
        $tenantDto = $tenantService->register(new RegisterTenantInputDTO(
            organizationName: 'Stripe Test',
            ownerName: 'Bob Owner',
            ownerEmail: 'bob.test@stripe.com',
            password: 'Password123!'
        ));

        /** @var InstanceProvisioningService $instanceService */
        $instanceService = app(InstanceProvisioningService::class);
        $instanceDto = $instanceService->createInstance(new CreateInstanceInputDTO(
            tenantId: $tenantDto->tenantId,
            name: 'Backend API Test',
            domain: 'backend.stripe.com',
            environment: 'PRODUCTION'
        ));

        $privateKey = $instanceDto->apiKeys->privatePushKey;

        $response = $this->postJson('/api/v1/telemetry/push', [
            'privatePushKey' => $privateKey,
            'eventName' => 'order_completed',
            'eventData' => ['orderId' => 'ord_7744', 'amount' => 199.99]
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['eventId', 'instanceId', 'eventName', 'status']);

        $this->assertDatabaseHas('raw_events', [
            'instance_id' => $instanceDto->id,
            'event_type' => 'BUSINESS',
            'event_name' => 'order_completed'
        ]);
    }
}
