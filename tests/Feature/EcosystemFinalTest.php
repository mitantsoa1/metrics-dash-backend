<?php

namespace Tests\Feature;

use App\Domains\Analytics\Jobs\SendAutomatedReportJob;
use App\Domains\Analytics\Services\QuotaEvaluationService;
use App\Domains\IAM\DTOs\RegisterTenantInputDTO;
use App\Domains\IAM\Services\TenantRegistrationService;
use App\Domains\Ingestion\Infrastructure\Models\RawEventModel;
use App\Domains\Instance\DTOs\CreateInstanceInputDTO;
use App\Domains\Instance\Services\InstanceProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EcosystemFinalTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_export_metrics_report_via_api(): void
    {
        /** @var TenantRegistrationService $tenantService */
        $tenantService = app(TenantRegistrationService::class);
        $tenantDto = $tenantService->register(new RegisterTenantInputDTO(
            organizationName: 'Final Test Org',
            ownerName: 'Final Owner',
            ownerEmail: 'final@test.com',
            password: 'Password123!'
        ));

        /** @var InstanceProvisioningService $instanceService */
        $instanceService = app(InstanceProvisioningService::class);
        $instanceDto = $instanceService->createInstance(new CreateInstanceInputDTO(
            tenantId: $tenantDto->tenantId,
            name: 'App Final Test',
            domain: 'final.test.com',
            environment: 'PRODUCTION'
        ));

        $response = $this->postJson('/api/v1/reports/export', [
            'instanceId' => $instanceDto->id,
            'format' => 'csv',
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'instanceId' => $instanceDto->id,
            'format' => 'csv',
        ]);
    }

    public function test_quota_evaluation_service(): void
    {
        /** @var TenantRegistrationService $tenantService */
        $tenantService = app(TenantRegistrationService::class);
        $tenantDto = $tenantService->register(new RegisterTenantInputDTO(
            organizationName: 'Quota Org',
            ownerName: 'Quota Owner',
            ownerEmail: 'quota@test.com',
            password: 'Password123!'
        ));

        /** @var InstanceProvisioningService $instanceService */
        $instanceService = app(InstanceProvisioningService::class);
        $instanceDto = $instanceService->createInstance(new CreateInstanceInputDTO(
            tenantId: $tenantDto->tenantId,
            name: 'App Quota Test',
            domain: 'quota.test.com',
            environment: 'PRODUCTION'
        ));

        $quotaService = new QuotaEvaluationService();

        // 1. Initialement non dépassé
        $this->assertFalse($quotaService->isQuotaExceeded($instanceDto->id, 5));

        // 2. Insérer 5 événements
        for ($i = 0; $i < 5; $i++) {
            RawEventModel::create([
                'id' => (string) Str::uuid(),
                'instance_id' => $instanceDto->id,
                'event_type' => 'NAVIGATION',
                'event_name' => 'page_view',
                'visitor_id' => 'vis_quota_' . $i,
                'context' => [],
                'occurred_at' => now(),
            ]);
        }

        // 3. Quota à la limite (5 / 5) -> Dépassé
        $this->assertTrue($quotaService->isQuotaExceeded($instanceDto->id, 5));

        $usage = $quotaService->getQuotaUsage($instanceDto->id, 5);
        $this->assertEquals(5, $usage['used']);
        $this->assertEquals(100.0, $usage['percentage']);
        $this->assertTrue($usage['exceeded']);
    }

    public function test_automated_report_job_execution(): void
    {
        $job = new SendAutomatedReportJob('tenant_123', 'admin@org.com');
        $job->handle(app(\App\Domains\Analytics\Services\MetricAggregationService::class));

        $this->assertTrue(true);
    }
}
