<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Ingestion\Infrastructure\Models\RawEventModel;
use App\Domains\Instance\Infrastructure\Models\ApiKeyModel;
use App\Domains\Instance\Services\InstanceProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushEventValidationTest extends TestCase
{
    use RefreshDatabase;

    private string $sk;

    protected function setUp(): void
    {
        parent::setUp();
        $tenant = \App\Domains\IAM\Infrastructure\Models\TenantModel::create(['id' => (string) \Illuminate\Support\Str::uuid(), 'name' => 't', 'slug' => 't']);
        $out = app(InstanceProvisioningService::class)->createInstance(
            new \App\Domains\Instance\DTOs\CreateInstanceInputDTO($tenant->id, 'i', 'i.test', 'DEVELOPMENT')
        );
        $this->sk = $out->apiKeys->privatePushKey;
    }

    private function push(array $body)
    {
        return $this->postJson('/api/v1/ingest/push', $body, ['X-Push-Secret-Key' => $this->sk]);
    }

    public function test_missing_event_type_returns_400_with_field(): void
    {
        $this->push(['payload' => ['amount' => 5]])
            ->assertStatus(400)
            ->assertJsonPath('error.field', 'event_type')
            ->assertJsonPath('error.code', 'required');
    }

    public function test_rejects_invalid_name_amount_and_timestamp(): void
    {
        $this->push(['event_type' => '<script>x'])->assertStatus(400)->assertJsonPath('error.code', 'invalid_format');
        $this->push(['event_type' => 'order', 'payload' => ['amount' => -5]])->assertStatus(400)->assertJsonPath('error.field', 'payload.amount');
        $this->push(['event_type' => 'order', 'timestamp' => 'pas une date'])->assertStatus(400)->assertJsonPath('error.field', 'timestamp');
    }

    public function test_normalizes_timestamp_amount_and_keeps_unknown_fields(): void
    {
        $this->push([
            'event_type' => 'order_completed',
            'timestamp' => '2026-11-01 10:00:00',
            'payload' => ['amount' => '12,50', 'custom' => ['a' => 1]],
        ])->assertStatus(201);

        $event = RawEventModel::query()->where('event_name', 'order_completed')->firstOrFail();
        $this->assertSame(12.5, $event->payload['amount']);
        $this->assertSame(['a' => 1], $event->payload['custom']);
        $this->assertSame('2026-11-01 10:00:00', $event->occurred_at->format('Y-m-d H:i:s'));
    }

    public function test_legacy_eventName_alias_still_accepted(): void
    {
        $this->push(['eventName' => 'order_completed', 'eventData' => ['amount' => 1]])->assertStatus(201);
    }
}
