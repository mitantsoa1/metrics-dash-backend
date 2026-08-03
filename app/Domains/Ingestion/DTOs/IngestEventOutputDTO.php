<?php

declare(strict_types=1);

namespace App\Domains\Ingestion\DTOs;

final readonly class IngestEventOutputDTO
{
    public function __construct(
        public string $eventId,
        public string $instanceId,
        public string $eventName,
        public string $status,
        public string $occurredAt
    ) {}
}
