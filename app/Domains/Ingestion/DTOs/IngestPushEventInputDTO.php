<?php

declare(strict_types=1);

namespace App\Domains\Ingestion\DTOs;

final readonly class IngestPushEventInputDTO
{
    public function __construct(
        public string $privatePushKey,
        public string $eventName,
        public array $eventData = [],
        public ?string $visitorId = null,
        public ?string $sessionId = null,
        public ?string $ipAddress = null
    ) {}
}
