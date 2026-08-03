<?php

namespace Tests\Feature;

use App\Domains\Analytics\Infrastructure\Models\DailyMetricModel;
use App\Domains\Analytics\Services\MetricAggregationService;
use App\Domains\IAM\DTOs\RegisterTenantInputDTO;
use App\Domains\IAM\Services\TenantRegistrationService;
use App\Domains\Ingestion\Infrastructure\Models\RawEventModel;
use App\Domains\Instance\DTOs\CreateInstanceInputDTO;
use App\Domains\Instance\Services\InstanceProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MetricAggregationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_aggregate_raw_events_into_daily_metrics(): void
    {
        /** @var TenantRegistrationService $tenantService */
        $tenantService = app(TenantRegistrationService::class);
        $tenantDto = $tenantService->register(new RegisterTenantInputDTO(
            organizationName: 'Analytics Test Org',
            ownerName: 'Analytics Owner',
            ownerEmail: 'analytics@test.com',
            password: 'Password123!'
        ));

        /** @var InstanceProvisioningService $instanceService */
        $instanceService = app(InstanceProvisioningService::class);
        $instanceDto = $instanceService->createInstance(new CreateInstanceInputDTO(
            tenantId: $tenantDto->tenantId,
            name: 'App Analytics Test',
            domain: 'analytics.test.com',
            environment: 'PRODUCTION'
        ));

        $today = date('Y-m-d');

        // Création de 3 vues de page avec 2 visiteurs uniques et 2 sessions
        RawEventModel::create([
            'id' => (string) Str::uuid(),
            'instance_id' => $instanceDto->id,
            'event_type' => 'NAVIGATION',
            'event_name' => 'page_view',
            'visitor_id' => 'vis_user_1',
            'session_id' => 'sess_1',
            'context' => [],
            'occurred_at' => now(),
        ]);

        RawEventModel::create([
            'id' => (string) Str::uuid(),
            'instance_id' => $instanceDto->id,
            'event_type' => 'NAVIGATION',
            'event_name' => 'page_view',
            'visitor_id' => 'vis_user_1',
            'session_id' => 'sess_1',
            'context' => [],
            'occurred_at' => now(),
        ]);

        RawEventModel::create([
            'id' => (string) Str::uuid(),
            'instance_id' => $instanceDto->id,
            'event_type' => 'NAVIGATION',
            'event_name' => 'page_view',
            'visitor_id' => 'vis_user_2',
            'session_id' => 'sess_2',
            'context' => [],
            'occurred_at' => now(),
        ]);

        // Création d'une conversion de type BUSINESS
        RawEventModel::create([
            'id' => (string) Str::uuid(),
            'instance_id' => $instanceDto->id,
            'event_type' => 'BUSINESS',
            'event_name' => 'order_completed',
            'visitor_id' => 'vis_user_2',
            'session_id' => 'sess_2',
            'context' => [],
            'occurred_at' => now(),
        ]);

        /** @var MetricAggregationService $aggregationService */
        $aggregationService = app(MetricAggregationService::class);
        $summary = $aggregationService->aggregateForDate($today);

        $this->assertEquals(1, $summary['instances_processed']);
        $this->assertEquals(4, $summary['metrics_upserted']);

        // Vérifications en BDD
        $this->assertDatabaseHas('daily_metrics', [
            'instance_id' => $instanceDto->id,
            'date' => $today,
            'metric_name' => 'page_views',
            'metric_value' => 3,
        ]);

        $this->assertDatabaseHas('daily_metrics', [
            'instance_id' => $instanceDto->id,
            'date' => $today,
            'metric_name' => 'unique_visitors',
            'metric_value' => 2,
        ]);

        $this->assertDatabaseHas('daily_metrics', [
            'instance_id' => $instanceDto->id,
            'date' => $today,
            'metric_name' => 'sessions',
            'metric_value' => 2,
        ]);

        $this->assertDatabaseHas('daily_metrics', [
            'instance_id' => $instanceDto->id,
            'date' => $today,
            'metric_name' => 'conversions',
            'metric_value' => 1,
        ]);
    }

    public function test_artisan_command_aggregate_metrics(): void
    {
        $this->artisan('metrics:aggregate')
             ->assertExitCode(0);
    }
}
