<?php

namespace Tests\Feature;

use App\Domains\IAM\DTOs\RegisterTenantInputDTO;
use App\Domains\IAM\Infrastructure\Models\UserModel;
use App\Domains\IAM\Services\TenantRegistrationService;
use App\Domains\Instance\DTOs\CreateInstanceInputDTO;
use App\Domains\Instance\Services\InstanceProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_fetch_metrics_via_api(): void
    {
        /** @var TenantRegistrationService $tenantService */
        $tenantService = app(TenantRegistrationService::class);
        $tenantDto = $tenantService->register(new RegisterTenantInputDTO(
            organizationName: 'Api Analytics Org',
            ownerName: 'Api Owner',
            ownerEmail: 'api_analytics@test.com',
            password: 'Password123!'
        ));

        $user = UserModel::find($tenantDto->ownerUserId);

        /** @var InstanceProvisioningService $instanceService */
        $instanceService = app(InstanceProvisioningService::class);
        $instanceDto = $instanceService->createInstance(new CreateInstanceInputDTO(
            tenantId: $tenantDto->tenantId,
            name: 'App Api Analytics Test',
            domain: 'api-analytics.test.com',
            environment: 'PRODUCTION'
        ));

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/metrics?instanceId=' . $instanceDto->id);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'instanceId' => $instanceDto->id,
        ]);
    }

    public function test_can_fetch_metrics_when_raw_events_exist(): void
    {
        /** @var TenantRegistrationService $tenantService */
        $tenantService = app(TenantRegistrationService::class);
        $tenantDto = $tenantService->register(new RegisterTenantInputDTO(
            organizationName: 'Api Analytics Org 2',
            ownerName: 'Api Owner 2',
            ownerEmail: 'api_analytics2@test.com',
            password: 'Password123!'
        ));

        $user = UserModel::find($tenantDto->ownerUserId);

        /** @var InstanceProvisioningService $instanceService */
        $instanceService = app(InstanceProvisioningService::class);
        $instanceDto = $instanceService->createInstance(new CreateInstanceInputDTO(
            tenantId: $tenantDto->tenantId,
            name: 'App Telemetry Test',
            domain: 'api-telemetry.test.com',
            environment: 'PRODUCTION'
        ));

        // Insérer des événements bruts (raw_events)
        \App\Domains\Ingestion\Infrastructure\Models\RawEventModel::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'instance_id' => $instanceDto->id,
            'event_type' => 'NAVIGATION',
            'event_name' => 'page_view',
            'visitor_id' => 'vis_123',
            'session_id' => 'sess_123',
            'payload' => ['path' => '/dashboard'],
            'context' => ['device_type' => 'DESKTOP', 'browser' => 'Chrome'],
            'occurred_at' => now(),
        ]);

        \App\Domains\Ingestion\Infrastructure\Models\RawEventModel::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'instance_id' => $instanceDto->id,
            'event_type' => 'NAVIGATION',
            'event_name' => 'click_interaction',
            'visitor_id' => 'vis_123',
            'session_id' => 'sess_123',
            'payload' => ['selector' => 'button#cta', 'label' => 'Acheter', 'element_type' => 'button'],
            'context' => ['device_type' => 'DESKTOP', 'browser' => 'Chrome'],
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/metrics?instanceId=' . $instanceDto->id);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'instanceId' => $instanceDto->id,
        ]);
        
        $data = $response->json()[0];
        $this->assertNotEmpty($data['topPages']);
        $this->assertEquals('/dashboard', $data['topPages'][0]['path']);
        $this->assertNotEmpty($data['topClicks']);
        $this->assertEquals('button#cta', $data['topClicks'][0]['selector']);
    }
}
