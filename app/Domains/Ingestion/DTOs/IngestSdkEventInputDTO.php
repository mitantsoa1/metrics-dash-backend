<?php

declare(strict_types=1);

namespace App\Domains\Ingestion\DTOs;

final readonly class IngestSdkEventInputDTO
{
    public function __construct(
        public string $publicSdkKey,
        public string $eventName,
        public ?string $visitorId = null,
        public ?string $sessionId = null,
        public array $payload = [],
        public array $context = [],
        public ?string $ipAddress = null,
        public ?string $userAgent = null
    ) {}
}
