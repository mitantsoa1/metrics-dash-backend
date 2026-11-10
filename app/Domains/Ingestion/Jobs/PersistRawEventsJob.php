<?php

declare(strict_types=1);

namespace App\Domains\Ingestion\Jobs;

use App\Domains\Ingestion\Entities\RawEvent;
use App\Domains\Ingestion\Repositories\RawEventRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Écriture d'un tampon d'événements (PU-51).
 *
 * File persistante (table jobs) : la réponse 202 part dès que le message est enregistré, un worker
 * l'écrit ensuite en base. Reprises à délai progressif ; passé le dernier essai, le message va dans
 * failed_jobs (dead-letter) sans bloquer les suivants.
 * L'écriture est idempotente (clé = identifiant de l'événement) : rejouer un lot ne crée aucun doublon.
 */
class PersistRawEventsJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public int $tries = 5;

    /** @var list<int> secondes entre les essais */
    public array $backoff = [5, 30, 120, 600];

    /** @param array<int, RawEvent> $events */
    public function __construct(private readonly array $events) {}

    public function handle(RawEventRepositoryInterface $repository): void
    {
        $failed = 0;

        foreach ($this->events as $event) {
            try {
                $repository->save($event);
            } catch (Throwable $e) {
                $failed++;
                Log::error('ingestion.persist_failed', [
                    'event_id' => $event->getId()->toString(),
                    'instance_id' => $event->getInstanceId()->toString(),
                    'attempt' => $this->attempts(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Les événements déjà écrits ne le seront pas deux fois au prochain essai.
        if ($failed > 0) {
            throw new RuntimeException("{$failed} événement(s) non écrit(s) sur ".count($this->events));
        }
    }

    public function failed(Throwable $e): void
    {
        Log::critical('ingestion.dead_letter', [
            'events' => count($this->events),
            'instances' => array_values(array_unique(array_map(fn (RawEvent $ev) => $ev->getInstanceId()->toString(), $this->events))),
            'error' => $e->getMessage(),
        ]);
    }

    /** Nombre d'événements du lot (supervision). */
    public function count(): int
    {
        return count($this->events);
    }
}
