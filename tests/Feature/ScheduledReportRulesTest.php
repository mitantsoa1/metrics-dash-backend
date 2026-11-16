<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Analytics\Infrastructure\Models\ScheduledReportLogModel;
use App\Domains\Analytics\Infrastructure\Models\ScheduledReportModel;
use App\Domains\Analytics\Services\ScheduledReportRunnerService;
use App\Domains\IAM\Infrastructure\Models\TenantModel;
use App\Domains\IAM\Infrastructure\Models\UserModel;
use App\Domains\Instance\Infrastructure\Models\InstanceModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** PU-43 : pas de doublon, fuseau valide, échec signalé aux administrateurs. */
class ScheduledReportRulesTest extends TestCase
{
    use RefreshDatabase;

    private TenantModel $tenant;
    private InstanceModel $instance;
    private UserModel $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = TenantModel::create(['id' => (string) Str::uuid(), 'name' => 't', 'slug' => 't']);
        $this->instance = InstanceModel::create([
            'id' => (string) Str::uuid(), 'tenant_id' => $this->tenant->id, 'name' => 'i', 'domain' => 'i.test',
            'environment' => 'DEVELOPMENT', 'status' => 'ACTIVE',
        ]);
        $this->owner = UserModel::create([
            'id' => (string) Str::uuid(), 'tenant_id' => $this->tenant->id, 'name' => 'o', 'email' => 'owner@t.test',
            'password' => 'secret123', 'role' => 'OWNER',
        ]);
        Sanctum::actingAs($this->owner);
    }

    private function payload(array $over = []): array
    {
        return $over + [
            'name' => 'Rapport lundi', 'instance_id' => $this->instance->id, 'frequency' => 'WEEKLY', 'day_of_week' => 1,
            'time' => '09:00', 'timezone' => 'Europe/Paris', 'formats' => 'PDF', 'recipients' => ['a@t.test', 'b@t.test'],
        ];
    }

    public function test_identical_schedule_is_rejected_whatever_the_recipient_order_or_case(): void
    {
        $this->postJson('/api/v1/scheduled-reports', $this->payload())->assertStatus(201);

        $this->postJson('/api/v1/scheduled-reports', $this->payload(['name' => 'Autre nom', 'recipients' => ['B@t.test', 'a@t.test']]))
            ->assertStatus(422);
        $this->assertSame(1, ScheduledReportModel::count());
    }

    public function test_different_day_time_or_recipients_are_allowed(): void
    {
        $this->postJson('/api/v1/scheduled-reports', $this->payload())->assertStatus(201);
        $this->postJson('/api/v1/scheduled-reports', $this->payload(['day_of_week' => 2]))->assertStatus(201);
        $this->postJson('/api/v1/scheduled-reports', $this->payload(['time' => '10:00']))->assertStatus(201);
        $this->postJson('/api/v1/scheduled-reports', $this->payload(['recipients' => ['c@t.test']]))->assertStatus(201);
        $this->assertSame(4, ScheduledReportModel::count());
    }

    public function test_update_cannot_create_a_duplicate_but_can_keep_itself(): void
    {
        $a = $this->postJson('/api/v1/scheduled-reports', $this->payload())->json('id');
        $b = $this->postJson('/api/v1/scheduled-reports', $this->payload(['day_of_week' => 3]))->json('id');

        $this->putJson("/api/v1/scheduled-reports/{$a}", ['name' => 'renommé'])->assertOk();
        $this->putJson("/api/v1/scheduled-reports/{$b}", ['day_of_week' => 1])->assertStatus(422);
    }

    public function test_invalid_timezone_is_rejected(): void
    {
        $this->postJson('/api/v1/scheduled-reports', $this->payload(['timezone' => 'Mars/Olympus']))
            ->assertStatus(422)->assertJsonValidationErrors('timezone');
    }

    public function test_failed_run_is_logged_and_notified_to_tenant_admins(): void
    {
        Mail::fake();
        UserModel::create([
            'id' => (string) Str::uuid(), 'tenant_id' => $this->tenant->id, 'name' => 'v', 'email' => 'viewer@t.test',
            'password' => 'secret123', 'role' => 'VIEWER',
        ]);
        $report = ScheduledReportModel::create($this->payload(['recipients' => ['a@t.test']]) + [
            'id' => (string) Str::uuid(), 'tenant_id' => $this->tenant->id, 'created_by' => 'owner@t.test',
            'day_of_month' => 1, 'is_active' => true, 'next_run_at' => now()->subMinute(),
            'included_kpis' => ['audience'],
        ]);

        $runner = \Mockery::mock(ScheduledReportRunnerService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $runner->shouldReceive('executeReport')->andThrow(new \RuntimeException('SMTP indisponible'));

        $summary = $runner->runDueReports();

        $this->assertSame(1, $summary['failed']);
        $this->assertSame('FAILED', ScheduledReportLogModel::where('scheduled_report_id', $report->id)->value('status'));
        $this->assertTrue($report->fresh()->next_run_at->isFuture(), 'la prochaine exécution est replanifiée');

        Mail::assertSent(\App\Mail\ScheduledReportFailedMail::class, function ($mail) {
            return $mail->hasTo('owner@t.test') && ! $mail->hasTo('viewer@t.test') && $mail->errorMessage === 'SMTP indisponible';
        });
    }
}
