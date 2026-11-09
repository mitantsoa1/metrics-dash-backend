<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\IAM\Infrastructure\Models\TenantModel;
use App\Domains\IAM\Infrastructure\Models\UserModel;
use App\Domains\Ingestion\Infrastructure\Models\RawEventModel;
use App\Domains\Instance\Infrastructure\Models\InstanceModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * PU-32 (6 KPIs), PU-33 (comparaison vs période précédente), PU-37/38/39 (filtres), PU-40 (top catégories).
 */
class KpiSummaryApiTest extends TestCase
{
    use RefreshDatabase;

    private InstanceModel $instance;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-10 12:00:00');

        $tenant = TenantModel::create(['id' => (string) Str::uuid(), 'name' => 't', 'slug' => 't']);
        $this->instance = InstanceModel::create([
            'id' => (string) Str::uuid(), 'tenant_id' => $tenant->id, 'name' => 'i', 'domain' => 'i.test',
            'environment' => 'DEVELOPMENT', 'status' => 'ACTIVE',
        ]);
        Sanctum::actingAs(UserModel::create([
            'id' => (string) Str::uuid(), 'tenant_id' => $tenant->id, 'name' => 'v', 'email' => 'v@t.test',
            'password' => 'secret123', 'role' => 'VIEWER',
        ]));
    }

    private function event(string $name, string $when, array $payload = [], array $context = [], string $type = 'NAVIGATION', ?string $visitor = null): void
    {
        RawEventModel::create([
            'id' => (string) Str::uuid(), 'instance_id' => $this->instance->id, 'event_type' => $type,
            'event_name' => $name, 'visitor_id' => $visitor, 'payload' => $payload, 'context' => $context,
            'occurred_at' => $when,
        ]);
    }

    private function metrics(string $query = ''): array
    {
        // période courante : 04 -> 10 nov (7 j) ; précédente : 28 oct -> 03 nov
        $r = $this->getJson("/api/v1/metrics?instanceId={$this->instance->id}&startDate=2026-11-04&endDate=2026-11-10".$query);
        $r->assertOk();

        return $r->json()[0] ?? $r->json('hydra:member.0');
    }

    public function test_six_kpis_with_comparison_vs_previous_period(): void
    {
        // courant : 2 commandes (300 €), 4 pages vues, 1 lead, 2 visiteurs
        $this->event('order_completed', '2026-11-05 10:00:00', ['order_id' => 'A', 'amount' => 100], [], 'BUSINESS', 'v1');
        $this->event('order_completed', '2026-11-06 10:00:00', ['order_id' => 'B', 'amount' => 200], [], 'BUSINESS', 'v2');
        foreach (range(1, 4) as $i) {
            $this->event('page_view', '2026-11-07 10:0'.$i.':00', ['path' => '/'], [], 'NAVIGATION', 'v'.($i % 2 + 1));
        }
        $this->event('lead_captured', '2026-11-07 11:00:00', [], [], 'BUSINESS');
        $this->event('page_duration', '2026-11-07 11:05:00', ['active_duration' => 40, 'page_duration' => 60, 'is_bounce' => true]);
        $this->event('page_duration', '2026-11-07 11:06:00', ['active_duration' => 20, 'page_duration' => 30, 'is_bounce' => false]);
        // précédente : 1 commande 100 €, 2 pages vues, rebond 50 % -> 100 %
        $this->event('order_completed', '2026-10-30 10:00:00', ['order_id' => 'C', 'amount' => 100], [], 'BUSINESS', 'v9');
        $this->event('page_view', '2026-10-30 10:01:00');
        $this->event('page_view', '2026-10-30 10:02:00');
        $this->event('page_duration', '2026-10-30 10:03:00', ['active_duration' => 10, 'is_bounce' => true]);

        $k = $this->metrics()['kpiSummary']['kpis'];

        $this->assertEquals(300.0, $k['total_revenue']['current']);
        $this->assertEquals(200.0, $k['total_revenue']['change_pct']);
        $this->assertSame('improving', $k['total_revenue']['trend']);
        // visiteurs v1, v2 (vues) : les 2 ont acheté -> 100 % ; jamais > 100 %
        $this->assertEquals(100.0, $k['conversion_rate']['current']);
        $this->assertEquals(4.0, (float) $k['page_views']['current']);
        $this->assertEquals(100.0, $k['page_views']['change_pct']);
        $this->assertEquals(1.0, (float) $k['leads']['current']);
        $this->assertSame('unavailable', $k['leads']['trend']); // précédent = 0
        $this->assertEquals(30.0, (float) $k['engagement']['current']);
        // rebond : 50 % vs 100 % -> en baisse = amélioration
        $this->assertEquals(50.0, (float) $k['bounce_rate']['current']);
        $this->assertEquals(-50.0, $k['bounce_rate']['change_pct']);
        $this->assertSame('improving', $k['bounce_rate']['trend']);
    }

    public function test_all_instances_view_works_for_a_tenant_user(): void
    {
        $this->event('order_completed', '2026-11-05 10:00:00', ['order_id' => 'A', 'amount' => 70], [], 'BUSINESS');

        $r = $this->getJson('/api/v1/metrics?instanceId=all&startDate=2026-11-04&endDate=2026-11-10')->assertOk();

        $this->assertEquals(70.0, $r->json('0.kpiSummary.kpis.total_revenue.current'));
        $this->getJson('/api/v1/metrics?startDate=2026-11-04&endDate=2026-11-10')->assertOk();
    }

    public function test_no_data_gives_null_not_invented_values(): void
    {
        $m = $this->metrics();
        $k = $m['kpiSummary']['kpis'];

        $this->assertNull($k['bounce_rate']['current']);
        $this->assertNull($k['engagement']['current']);
        $this->assertNull($k['conversion_rate']['current']);
        $this->assertSame('unavailable', $k['bounce_rate']['trend']);
        $this->assertEquals(0, $m['kpis']['avg_active_duration']);
        $this->assertEquals(0, (int) $m['kpis']['bounce_rate']);
    }

    public function test_conversion_never_exceeds_100_percent_with_unattributed_orders(): void
    {
        $this->event('page_view', '2026-11-05 10:00:00', [], [], 'NAVIGATION', 'v1');
        foreach (['A', 'B', 'C'] as $id) {
            $this->event('order_completed', '2026-11-05 11:00:00', ['order_id' => $id, 'amount' => 10], [], 'BUSINESS'); // sans visitor_id
        }

        $this->assertEquals(0.0, $this->metrics()['kpiSummary']['kpis']['conversion_rate']['current']);
    }

    public function test_business_events_without_amount_are_not_orders(): void
    {
        $this->event('cart_abandoned', '2026-11-05 10:00:00', ['cart_id' => 'C1'], [], 'BUSINESS');
        $this->event('order_completed', '2026-11-05 11:00:00', ['order_id' => 'A', 'amount' => 20], [], 'BUSINESS');
        $this->event('invoice_paid', '2026-11-05 12:00:00', ['amount' => 30], [], 'BUSINESS'); // événement métier avec montant

        $m = $this->metrics();

        $this->assertEquals(50.0, $m['kpiSummary']['kpis']['total_revenue']['current']);
        $this->assertSame(2, $m['financialKpis']['total_transactions']);
    }

    public function test_replayed_order_is_counted_once(): void
    {
        foreach ([1, 2, 3] as $i) {
            $this->event('order_completed', '2026-11-05 10:0'.$i.':00', ['order_id' => 'SAME', 'amount' => 50], [], 'BUSINESS');
        }
        $m = $this->metrics();

        $this->assertEquals(50.0, $m['kpiSummary']['kpis']['total_revenue']['current']);
        $this->assertEquals(1, $m['financialKpis']['total_transactions']);
    }

    public function test_filters_by_category_region_device_and_top_categories(): void
    {
        $this->event('order_completed', '2026-11-05 10:00:00', ['order_id' => '1', 'amount' => 100, 'category' => 'shoes', 'region' => 'EU'], [], 'BUSINESS');
        $this->event('order_completed', '2026-11-05 11:00:00', ['order_id' => '2', 'amount' => 300, 'category' => 'bags', 'region' => 'US'], [], 'BUSINESS');
        $this->event('page_view', '2026-11-05 12:00:00', ['category' => 'shoes'], ['device_type' => 'MOBILE']);
        $this->event('page_view', '2026-11-05 12:01:00', ['category' => 'bags'], ['device_type' => 'DESKTOP']);

        $all = $this->metrics();
        $this->assertEquals(400.0, $all['kpiSummary']['kpis']['total_revenue']['current']);
        $this->assertSame('bags', $all['topCategories'][0]['label']);
        $this->assertEquals(75.0, (float) $all['topCategories'][0]['percentage']);

        $shoes = $this->metrics('&category=shoes');
        $this->assertEquals(100.0, $shoes['kpiSummary']['kpis']['total_revenue']['current']);
        $this->assertEquals(1.0, (float) $shoes['kpiSummary']['kpis']['page_views']['current']);

        $multi = $this->metrics('&category[]=shoes&category[]=bags');
        $this->assertEquals(400.0, $multi['kpiSummary']['kpis']['total_revenue']['current']);

        $combo = $this->metrics('&category=shoes&region=US');
        $this->assertEquals(0.0, $combo['kpiSummary']['kpis']['total_revenue']['current']);

        $mobile = $this->metrics('&device=mobile');
        $this->assertEquals(1.0, (float) $mobile['kpiSummary']['kpis']['page_views']['current']);
        $this->assertSame(['category' => [], 'region' => [], 'device' => ['MOBILE']], array_intersect_key($mobile['activeFilters'], ['category' => 1, 'region' => 1, 'device' => 1]));
    }

    public function test_filter_options_only_list_values_with_data_in_period(): void
    {
        $this->event('order_completed', '2026-11-05 10:00:00', ['category' => 'shoes', 'region' => 'EU'], [], 'BUSINESS');
        $this->event('order_completed', '2026-09-01 10:00:00', ['category' => 'obsolete'], [], 'BUSINESS'); // hors période
        $this->event('page_view', '2026-11-05 10:00:00', [], ['device_type' => 'MOBILE']);

        $r = $this->getJson("/api/v1/metrics/filter-options?instanceId={$this->instance->id}&startDate=2026-11-04&endDate=2026-11-10")->assertOk();

        $this->assertSame(['shoes'], array_column($r->json('categories'), 'value'));
        $this->assertSame(['EU'], array_column($r->json('regions'), 'value'));
        $this->assertSame(['MOBILE'], array_column($r->json('devices'), 'value'));
    }

    public function test_filter_options_denied_for_foreign_instance(): void
    {
        $other = TenantModel::create(['id' => (string) Str::uuid(), 'name' => 'o', 'slug' => 'o']);
        $inst = InstanceModel::create([
            'id' => (string) Str::uuid(), 'tenant_id' => $other->id, 'name' => 'x', 'domain' => 'x.test',
            'environment' => 'DEVELOPMENT', 'status' => 'ACTIVE',
        ]);

        $this->getJson("/api/v1/metrics/filter-options?instanceId={$inst->id}")->assertStatus(404);
    }
}
