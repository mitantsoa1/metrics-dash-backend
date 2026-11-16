<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\IAM\Infrastructure\Models\TenantModel;
use App\Domains\Ingestion\Jobs\PersistRawEventsJob;
use App\Domains\Ingestion\Infrastructure\Models\RawEventModel;
use App\Domains\Ingestion\Repositories\RawEventRepositoryInterface;
use App\Domains\Instance\DTOs\CreateInstanceInputDTO;
use App\Domains\Instance\Services\InstanceProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

/** PU-51 : file persistante, reprises, dead-letter, supervision. */
class IngestionBufferTest extends TestCase
{
    use RefreshDatabase;

    private string $sk;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $tenant = TenantModel::create(['id' => (string) Str::uuid(), 'name' => 't', 'slug' => 't']);
        $out = app(InstanceProvisioningService::class)->createInstance(new CreateInstanceInputDTO($tenant->id, 'i', 'i.test', 'DEVELOPMENT'));
        $this->sk = $out->apiKeys->privatePushKey;
    }

    private function push(string $order)
    {
        return $this->postJson('/api/events', ['event_type' => 'order_completed', 'payload' => ['order_id' => $order, 'amount' => 10]], ['Authorization' => 'Bearer '.$this->sk]);
    }

    public function test_the_event_goes_to_the_queue_and_is_written_by_the_worker(): void
    {
        config(['ingestion.buffer' => 'queue']);
        Queue::fake();

        $this->push('Q1')->assertStatus(202);

        Queue::assertPushed(PersistRawEventsJob::class, 1);
        $this->assertSame(0, RawEventModel::count(), 'rien n\'est écrit avant le passage du worker');

        Queue::pushed(PersistRawEventsJob::class)->first()->handle(app(RawEventRepositoryInterface::class));
        $this->assertSame(1, RawEventModel::count());
    }

    public function test_replaying_a_batch_never_duplicates_events(): void
    {
        config(['ingestion.buffer' => 'queue']);
        Queue::fake();
        $this->push('Q2')->assertStatus(202);
        $job = Queue::pushed(PersistRawEventsJob::class)->first();

        $job->handle(app(RawEventRepositoryInterface::class));
        $job->handle(app(RawEventRepositoryInterface::class)); // reprise après un crash

        $this->assertSame(1, RawEventModel::count());
    }

    public function test_a_failing_write_makes_the_job_retry_then_dead_letter(): void
    {
        config(['ingestion.buffer' => 'queue']);
        Queue::fake();
        $this->push('Q3')->assertStatus(202);
        $job = Queue::pushed(PersistRawEventsJob::class)->first();

        $broken = new class implements RawEventRepositoryInterface {
            public function save(\App\Domains\Ingestion\Entities\RawEvent $rawEvent): void { throw new \RuntimeException('base indisponible'); }
            public function findById(\App\Domains\Ingestion\ValueObjects\EventId $id): ?\App\Domains\Ingestion\Entities\RawEvent { return null; }
            public function countByInstanceId(string $instanceId): int { return 0; }
        };

        $this->expectException(\RuntimeException::class); // l'exception déclenche la reprise par la file
        $job->handle($broken);
    }

    public function test_retries_are_progressive_and_bounded(): void
    {
        $job = new PersistRawEventsJob([]);

        $this->assertSame(5, $job->tries);
        $this->assertSame([5, 30, 120, 600], $job->backoff);
    }

    public function test_inline_mode_writes_without_a_worker(): void
    {
        config(['ingestion.buffer' => 'inline']);

        $this->push('Q4')->assertStatus(202);

        $this->assertSame(1, RawEventModel::count());
    }

    public function test_status_command_reports_pending_and_failed_batches(): void
    {
        config(['queue.default' => 'database', 'ingestion.buffer' => 'queue']);
        $payload = json_encode(['displayName' => PersistRawEventsJob::class, 'job' => 'Illuminate\Queue\CallQueuedHandler@call', 'data' => ['commandName' => PersistRawEventsJob::class]]);
        DB::table('jobs')->insert(['queue' => 'default', 'payload' => $payload, 'attempts' => 0, 'reserved_at' => null, 'available_at' => time(), 'created_at' => time() - 600]);
        DB::table('failed_jobs')->insert(['uuid' => (string) Str::uuid(), 'connection' => 'database', 'queue' => 'default', 'payload' => $payload, 'exception' => 'x', 'failed_at' => now()]);

        $this->artisan('ingestion:status')
            ->expectsOutputToContain('File en retard')
            ->expectsOutputToContain('1 lot(s) en échec')
            ->assertFailed();
    }

    public function test_status_command_is_healthy_when_the_queue_is_empty(): void
    {
        config(['queue.default' => 'database']);

        $this->artisan('ingestion:status')->assertSuccessful();
    }
}
