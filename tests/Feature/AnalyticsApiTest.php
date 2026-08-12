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
}
