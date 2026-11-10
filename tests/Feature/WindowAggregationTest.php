<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Analytics\Infrastructure\Models\MetricWindowModel;
use App\Domains\Analytics\Services\WindowAggregationService;
use App\Domains\IAM\Infrastructure\Models\TenantModel;
use App\Domains\Ingestion\Infrastructure\Models\RawEventModel;
use App\Domains\Instance\Infrastructure\Models\InstanceModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

/** PU-52 : agrégation par fenêtre de 10 minutes. */
class WindowAggregationTest extends TestCase
{
    use RefreshDatabase;

    private InstanceModel $instance;

    protected function setUp(): void
    {
        parent::setUp();
        $t = TenantModel::create(['id' => (string) Str::uuid(), 'name' => 't', 'slug' => 't']);
        $this->instance = InstanceModel::create([
            'id' => (string) Str::uuid(), 'tenant_id' => $t->id, 'name' => 'i', 'domain' => 'i.test',
            'environment' => 'DEVELOPMENT', 'status' => 'ACTIVE',
        ]);
    }

    private function event(string $name, string $when, array $payload = [], string $type = 'NAVIGATION', ?string $visitor = null): void
    {
        RawEventModel::create([
            'id' => (string) Str::uuid(), 'instance_id' => $this->instance->id, 'event_type' => $type,
            'event_name' => $name, 'visitor_id' => $visitor, 'session_id' => $visitor ? 's'.$visitor : null,
            'payload' => $payload, 'occurred_at' => $when,
        ]);
    }

    private function value(string $window, string $metric): float
    {
        return (float) MetricWindowModel::where('instance_id', $this->instance->id)
            ->where('window_start', $window)->where('metric_name', $metric)->value('metric_value');
    }

    public function test_window_is_aligned_on_ten_minutes(): void
    {
        $svc = new WindowAggregationService();
        $this->assertSame('2026-11-10 12:30:00', $svc->windowStart(Carbon::parse('2026-11-10 12:39:59'))->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-10 12:40:00', $svc->windowStart(Carbon::parse('2026-11-10 12:40:00'))->format('Y-m-d H:i:s'));
    }

    public function test_only_events_of_the_window_are_aggregated(): void
    {
        $this->event('page_view', '2026-11-10 12:30:00', [], 'NAVIGATION', 'v1');
        $this->event('page_view', '2026-11-10 12:39:59', [], 'NAVIGATION', 'v2');
        $this->event('page_view', '2026-11-10 12:40:00', [], 'NAVIGATION', 'v3'); // fenêtre suivante
        $this->event('page_view', '2026-11-10 12:29:59', [], 'NAVIGATION', 'v4'); // fenêtre précédente
        $this->event('order_completed', '2026-11-10 12:35:00', ['order_id' => 'A', 'amount' => 40], 'BUSINESS');
        $this->event('order_completed', '2026-11-10 12:36:00', ['order_id' => 'A', 'amount' => 40], 'BUSINESS'); // rejeu
        $this->event('lead_captured', '2026-11-10 12:37:00', [], 'BUSINESS');

        $summary = app(WindowAggregationService::class)->aggregateWindow(Carbon::parse('2026-11-10 12:34:00'));

        $w = '2026-11-10 12:30:00';
        $this->assertSame(1, $summary['instances']);
        $this->assertSame(2.0, $this->value($w, 'page_views'));
        $this->assertSame(2.0, $this->value($w, 'unique_visitors'));
        $this->assertSame(1.0, $this->value($w, 'orders'));
        $this->assertSame(40.0, $this->value($w, 'revenue'));
        $this->assertSame(1.0, $this->value($w, 'leads'));
    }

    public function test_rerun_is_idempotent_and_picks_up_late_events(): void
    {
        $svc = app(WindowAggregationService::class);
        $this->event('page_view', '2026-11-10 12:31:00');
        $svc->aggregateWindow(Carbon::parse('2026-11-10 12:31:00'));
        $this->event('page_view', '2026-11-10 12:32:00'); // arrivé en retard
        $svc->aggregateWindow(Carbon::parse('2026-11-10 12:31:00'));

        $this->assertSame(2.0, $this->value('2026-11-10 12:30:00', 'page_views'));
        $this->assertSame(9, MetricWindowModel::count()); // 9 métriques, pas de doublon
    }

    public function test_sessions_are_deduplicated_and_bounce_engagement_are_aggregated(): void
    {
        // une seule session (s1) : 3 événements -> 1 session ; une autre (s2) : 1 événement
        foreach (['12:31:00', '12:32:00', '12:33:00'] as $t) {
            RawEventModel::create(['id' => (string) Str::uuid(), 'instance_id' => $this->instance->id, 'event_type' => 'NAVIGATION', 'event_name' => 'page_view', 'visitor_id' => 'v1', 'session_id' => 'sv1', 'payload' => [], 'occurred_at' => "2026-11-10 $t"]);
        }
        RawEventModel::create(['id' => (string) Str::uuid(), 'instance_id' => $this->instance->id, 'event_type' => 'NAVIGATION', 'event_name' => 'page_view', 'visitor_id' => 'v2', 'session_id' => 'sv2', 'payload' => [], 'occurred_at' => '2026-11-10 12:34:00']);
        // rebond : 1 visite sur 2 ; engagement : 10 s et 30 s actives
        $this->event('page_duration', '2026-11-10 12:35:00', ['is_bounce' => true, 'active_duration' => 10], 'NAVIGATION', 'v1');
        $this->event('page_duration', '2026-11-10 12:36:00', ['is_bounce' => false, 'active_duration' => 30], 'NAVIGATION', 'v2');

        app(WindowAggregationService::class)->aggregateWindow(Carbon::parse('2026-11-10 12:30:00'));

        $w = '2026-11-10 12:30:00';
        $this->assertSame(2.0, $this->value($w, 'sessions'));
        $this->assertSame(2.0, $this->value($w, 'unique_visitors'));
        $this->assertSame(4.0, $this->value($w, 'page_views'));
        $this->assertSame(2.0, $this->value($w, 'page_durations'));
        $this->assertSame(1.0, $this->value($w, 'bounces'));
        $this->assertSame(40.0, $this->value($w, 'active_seconds'));
    }

    public function test_command_runs_and_is_scheduled(): void
    {
        $this->event('page_view', '2026-11-10 12:31:00');
        $this->artisan('metrics:aggregate-windows', ['--at' => '2026-11-10 12:35:00'])->assertSuccessful();
        $this->assertSame(1.0, $this->value('2026-11-10 12:30:00', 'page_views'));

        $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())->map->command->implode('|');
        $this->assertStringContainsString('metrics:aggregate-windows', $events);
    }
}
