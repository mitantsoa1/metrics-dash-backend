<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\IAM\Infrastructure\Models\TenantModel;
use App\Domains\Ingestion\Infrastructure\Models\RawEventModel;
use App\Domains\Instance\DTOs\CreateInstanceInputDTO;
use App\Domains\Instance\Services\InstanceProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;

/** PU-54 (endpoint /api/events), PU-55 (auth Bearer, 401, journal des refus), PU-51 (tampon), batch SDK (PU-31). */
class ApiEventsEndpointTest extends TestCase
{
    use RefreshDatabase;

    private string $sk;
    private string $pk;
    private string $instanceId;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $tenant = TenantModel::create(['id' => (string) Str::uuid(), 'name' => 't', 'slug' => 't']);
        $out = app(InstanceProvisioningService::class)->createInstance(
            new CreateInstanceInputDTO($tenant->id, 'i', 'i.test', 'DEVELOPMENT')
        );
        $this->sk = $out->apiKeys->privatePushKey;
        $this->pk = $out->apiKeys->publicSdkKey;
        $this->instanceId = $out->id;
    }

    private function push(array $body, array $headers = [], ?string $key = null)
    {
        return $this->postJson('/api/events', $body, ['Authorization' => 'Bearer '.($key ?? $this->sk)] + $headers);
    }

    public function test_accepts_with_202_correlation_id_and_server_side_persistence(): void
    {
        $r = $this->push(['event_type' => 'order_completed', 'timestamp' => '2026-11-01T10:00:00Z', 'payload' => ['order_id' => 'O1', 'amount' => 49.9, 'category' => 'shoes']])
            ->assertStatus(202)
            ->assertJsonPath('status', 'accepted')
            ->assertJsonPath('duplicate', false);

        $this->assertNotEmpty($r->headers->get('X-Correlation-Id'));
        $event = RawEventModel::findOrFail($r->json('event_id'));
        $this->assertSame($this->instanceId, $event->instance_id);
        $this->assertSame('BUSINESS', $event->event_type);
        $this->assertSame($r->json('correlation_id'), $event->context['correlation_id']);
        $this->assertArrayHasKey('received_at', $event->context);
    }

    public function test_provided_correlation_id_is_echoed(): void
    {
        $this->push(['event_type' => 'x_event'], ['X-Correlation-Id' => 'corr-12345678'])
            ->assertStatus(202)->assertHeader('X-Correlation-Id', 'corr-12345678');
    }

    public function test_unknown_events_are_accepted_and_stored_raw(): void
    {
        $this->push(['event_type' => 'cart_abandoned', 'payload' => ['custom' => ['a' => 1]]])->assertStatus(202);
        $this->assertSame(['custom' => ['a' => 1]], RawEventModel::where('event_name', 'cart_abandoned')->firstOrFail()->payload);
    }

    public function test_idempotency_key_prevents_duplicates(): void
    {
        $first = $this->push(['event_type' => 'order_completed', 'payload' => ['order_id' => 'A']], ['Idempotency-Key' => 'abc-1'])->assertStatus(202);
        $second = $this->push(['event_type' => 'order_completed', 'payload' => ['order_id' => 'A']], ['Idempotency-Key' => 'abc-1'])->assertStatus(202)->assertJsonPath('duplicate', true);

        $this->assertSame($first->json('event_id'), $second->json('event_id'));
        $this->assertSame(1, RawEventModel::where('event_name', 'order_completed')->count());
    }

    public function test_missing_or_invalid_key_returns_401_without_leaking_the_key_and_is_logged(): void
    {
        Log::spy();

        $this->postJson('/api/events', ['event_type' => 'x_event'])->assertStatus(401);
        $r = $this->push(['event_type' => 'x_event'], [], 'sk_live_wrongwrongwrongwrong-wrongwrong12')->assertStatus(401);

        $this->assertStringNotContainsString('wrongwrongwrong', $r->getContent());
        Log::shouldHaveReceived('warning')->with('ingestion.key_refused', \Mockery::on(
            fn ($ctx) => ($ctx['type'] ?? null) === 'PRIVATE_PUSH' && strlen($ctx['key_prefix']) <= 12
        ))->atLeast()->twice();
        $this->assertSame(0, RawEventModel::count());
    }

    public function test_public_sdk_key_cannot_push_business_events(): void
    {
        $this->push(['event_type' => 'x_event'], [], $this->pk)->assertStatus(401);
    }

    public function test_structured_400_and_413(): void
    {
        $this->push(['payload' => []])->assertStatus(400)->assertJsonPath('error.field', 'event_type');
        $this->push(['event_type' => 'order', 'payload' => ['amount' => -1]])->assertStatus(400)->assertJsonPath('error.code', 'invalid_amount');

        $this->push(['event_type' => 'order', 'payload' => ['blob' => str_repeat('a', 270000)]])
            ->assertStatus(413)->assertJsonPath('error.code', 'too_large');
    }

    public function test_rate_limit_of_1000_events_per_minute_per_key(): void
    {
        // Compteur de la clé testée pré-rempli à la limite ; une autre clé n'est pas affectée
        $bucket = md5('push-ingest'.hash('sha256', $this->sk));
        for ($i = 0; $i < 1000; $i++) {
            \Illuminate\Support\Facades\RateLimiter::hit($bucket, 60);
        }

        $this->push(['event_type' => 'x_event'])->assertStatus(429);
    }

    public function test_sdk_batch_is_expanded_into_events_with_dashboard_names(): void
    {
        $this->postJson('/api/v1/ingest/sdk', [
            'public_sdk_key' => $this->pk,
            'visitor_id' => 'v1',
            'session_id' => 'sess_1',
            'events' => [
                ['event_type' => 'pageview', 'timestamp' => '2026-11-01T10:00:00Z', 'path' => '/home', 'title' => 'Home'],
                ['event_type' => 'click', 'timestamp' => '2026-11-01T10:00:05Z', 'path' => '/home', 'selector' => 'a.cta', 'label' => 'Buy', 'element_type' => 'a'],
                ['event_type' => 'form_submit', 'path' => '/home', 'form_id' => 'f1', 'field_count' => 3],
                ['event_type' => 'session_ping', 'path' => '/home', 'total_duration_s' => 60, 'engaged_duration_s' => 40, 'is_bounce' => false],
            ],
        ], ['User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Mobile Safari'])
            ->assertStatus(201)->assertJsonPath('eventName', 'batch');

        $names = RawEventModel::orderBy('occurred_at')->pluck('event_name')->all();
        sort($names);
        $this->assertSame(['click_interaction', 'form_submit', 'page_duration', 'page_view'], $names);

        $duration = RawEventModel::where('event_name', 'page_duration')->firstOrFail();
        $this->assertSame(40, $duration->payload['active_duration']);
        $this->assertSame('MOBILE', $duration->context['device_type']);
        $this->assertSame('sess_1', $duration->session_id);
    }
}
