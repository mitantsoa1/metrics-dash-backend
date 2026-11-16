<?php

namespace Tests\Feature;

use App\Domains\IAM\DTOs\RegisterTenantInputDTO;
use App\Domains\IAM\Services\TenantRegistrationService;
use App\Domains\Instance\DTOs\CreateInstanceInputDTO;
use App\Domains\Instance\Services\InstanceProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StandaloneScriptTelemetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_serve_standalone_script_js(): void
    {
        $response = $this->get('/api/v1/script.js');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/javascript; charset=utf-8');
        $this->assertStringContainsString('MetricsDash', $response->getContent());
        $this->assertStringContainsString('page_duration', $response->getContent());
        $this->assertStringContainsString('click_interaction', $response->getContent());
    }

    public function test_can_ingest_page_duration_and_click_interaction_events(): void
    {
        /** @var TenantRegistrationService $tenantService */
        $tenantService = app(TenantRegistrationService::class);
        $tenantDto = $tenantService->register(new RegisterTenantInputDTO(
            organizationName: 'Standalone Test Org',
            ownerName: 'Alice Standalone',
            ownerEmail: 'alice.standalone@example.com',
            password: 'Password123!'
        ));

        /** @var InstanceProvisioningService $instanceService */
        $instanceService = app(InstanceProvisioningService::class);
        $instanceDto = $instanceService->createInstance(new CreateInstanceInputDTO(
            tenantId: $tenantDto->tenantId,
            name: 'App Web Standalone',
            domain: 'standalone.example.com',
            environment: 'PRODUCTION'
        ));

        $plainKey = $instanceDto->apiKeys->publicSdkKey;

        // 1. Ingestion Vue de page
        $pvResponse = $this->postJson('/api/v1/telemetry/sdk', [
            'publicSdkKey' => $plainKey,
            'eventName' => 'page_view',
            'visitorId' => 'vis_123',
            'sessionId' => 'sess_456',
            'payload' => [
                'url' => 'http://example.com/checkout',
                'path' => '/checkout',
                'title' => 'Checkout Page',
            ],
        ]);
        $pvResponse->assertStatus(201);

        // 2. Ingestion Clic Interaction
        $clickResponse = $this->postJson('/api/v1/telemetry/sdk', [
            'publicSdkKey' => $plainKey,
            'eventName' => 'click_interaction',
            'visitorId' => 'vis_123',
            'sessionId' => 'sess_456',
            'payload' => [
                'selector' => 'button#pay-now.btn-primary',
                'element_type' => 'button',
                'label' => 'Payer maintenant',
                'path' => '/checkout',
            ],
        ]);
        $clickResponse->assertStatus(201);

        // 3. Ingestion Durée Active de Page
        $durationResponse = $this->postJson('/api/v1/telemetry/sdk', [
            'publicSdkKey' => $plainKey,
            'eventName' => 'page_duration',
            'visitorId' => 'vis_123',
            'sessionId' => 'sess_456',
            'payload' => [
                'path' => '/checkout',
                'page_duration' => 60,
                'active_duration' => 45,
                'is_bounce' => false,
            ],
        ]);
        $durationResponse->assertStatus(201);

        // Vérification en BDD
        $this->assertDatabaseHas('raw_events', [
            'instance_id' => $instanceDto->id,
            'event_name' => 'click_interaction',
        ]);

        $this->assertDatabaseHas('raw_events', [
            'instance_id' => $instanceDto->id,
            'event_name' => 'page_duration',
        ]);
    }
}
