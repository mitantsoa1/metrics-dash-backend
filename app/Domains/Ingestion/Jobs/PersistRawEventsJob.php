<?php

declare(strict_types=1);

namespace App\Domains\Ingestion\Jobs;

use App\Domains\Ingestion\Entities\RawEvent;
use App\Domains\Ingestion\Repositories\RawEventRepositoryInterface;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

/**
 * Écriture différée d'un tampon d'événements (PU-51).
 * Volontairement non ShouldQueue : lancé via ->afterResponse(), donc sans worker requis.
 * Un événement en échec est journalisé sans bloquer les suivants.
 */
class PersistRawEventsJob
{
    use Dispatchable;

    /** @param array<int, RawEvent> $events */
    public function __construct(private readonly array $events) {}

    public function handle(RawEventRepositoryInterface $repository): void
    {
        foreach ($this->events as $event) {
            try {
                $repository->save($event);
            } catch (\Throwable $e) {
                Log::error('ingestion.persist_failed', [
                    'event_id' => $event->getId()->toString(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
