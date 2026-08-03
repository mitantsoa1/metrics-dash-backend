<?php

namespace Tests\Feature;

use App\Domains\Ingestion\Enums\DeviceType;
use App\Domains\Ingestion\Infrastructure\Models\RawEventModel;
use App\Domains\Ingestion\Jobs\ProcessRawEventBatchJob;
use App\Domains\Ingestion\Services\EventAnonymizationService;
use App\Domains\Ingestion\Services\UserAgentParserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EventProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_ip_anonymization_and_salted_visitor_id(): void
    {
        $anonymizer = new EventAnonymizationService();

        $anonymizedIpv4 = $anonymizer->anonymizeIp('192.168.1.123');
        $this->assertEquals('192.168.1.0', $anonymizedIpv4);

        $anonymizedIpv6 = $anonymizer->anonymizeIp('2001:0db8:85a3:0000:0000:8a2e:0370:7334');
        $this->assertStringStartsWith('2001:db8:85a3:', $anonymizedIpv6);

        $visitorId = $anonymizer->generateAnonymousVisitorId('vis_123', '192.168.1.123', 'Mozilla/5.0');
        $this->assertStringStartsWith('vis_', $visitorId);
    }

    public function test_user_agent_parser_detection(): void
    {
        $uaParser = new UserAgentParserService();

        // Desktop Windows Chrome
        $desktopUa = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
        $desktopResult = $uaParser->parse($desktopUa);

        $this->assertEquals(DeviceType::DESKTOP->value, $desktopResult['device_type']);
        $this->assertEquals('Chrome', $desktopResult['browser']);
        $this->assertEquals('Windows', $desktopResult['os']);

        // Mobile iPhone Safari
        $mobileUa = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';
        $mobileResult = $uaParser->parse($mobileUa);

        $this->assertEquals(DeviceType::MOBILE->value, $mobileResult['device_type']);
        $this->assertEquals('Safari', $mobileResult['browser']);
        $this->assertEquals('iOS', $mobileResult['os']);
    }

    public function test_process_raw_event_batch_job_execution(): void
    {
        /** @var \App\Domains\IAM\Services\TenantRegistrationService $tenantService */
        $tenantService = app(\App\Domains\IAM\Services\TenantRegistrationService::class);
        $tenantDto = $tenantService->register(new \App\Domains\IAM\DTOs\RegisterTenantInputDTO(
            organizationName: 'Processing Test Org',
            ownerName: 'Proc Owner',
            ownerEmail: 'proc@test.com',
            password: 'Password123!'
        ));

        /** @var \App\Domains\Instance\Services\InstanceProvisioningService $instanceService */
        $instanceService = app(\App\Domains\Instance\Services\InstanceProvisioningService::class);
        $instanceDto = $instanceService->createInstance(new \App\Domains\Instance\DTOs\CreateInstanceInputDTO(
            tenantId: $tenantDto->tenantId,
            name: 'App Processing Test',
            domain: 'proc.test.com',
            environment: 'PRODUCTION'
        ));

        $event = RawEventModel::create([
            'id' => (string) Str::uuid(),
            'instance_id' => $instanceDto->id,
            'event_type' => 'NAVIGATION',
            'event_name' => 'page_view',
            'visitor_id' => 'vis_raw_9988',
            'context' => ['initial' => 'data'],
            'occurred_at' => now(),
        ]);

        $job = new ProcessRawEventBatchJob(
            rawEventId: $event->id,
            rawIp: '198.51.100.45',
            userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36'
        );

        $job->handle(
            new EventAnonymizationService(),
            new UserAgentParserService()
        );

        $event->refresh();

        $this->assertEquals('198.51.100.0', $event->context['anonymized_ip']);
        $this->assertEquals('DESKTOP', $event->context['device_type']);
        $this->assertEquals('Chrome', $event->context['browser']);
        $this->assertEquals('macOS', $event->context['os']);
        $this->assertNotNull($event->context['processed_at']);
    }

}
