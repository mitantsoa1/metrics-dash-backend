<?php

declare(strict_types=1);

namespace App\Domains\Ingestion\Resources;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Domains\Ingestion\Processors\IngestPushEventProcessor;
use App\Domains\Ingestion\Processors\IngestSdkEventProcessor;

#[ApiResource(
    shortName: 'Ingestion',
    operations: [
        new Post(
            uriTemplate: '/api/v1/telemetry/sdk',
            processor: IngestSdkEventProcessor::class
        ),
        new Post(
            uriTemplate: '/api/v1/collect/sdk',
            processor: IngestSdkEventProcessor::class
        ),
        new Post(
            uriTemplate: '/api/v1/telemetry/push',
            processor: IngestPushEventProcessor::class
        ),
        new Post(
            uriTemplate: '/api/v1/collect/push',
            processor: IngestPushEventProcessor::class
        ),
    ]
)]
class IngestionResource
{
    public ?string $eventId = null;
    public ?string $instanceId = null;
    public ?string $eventName = null;
    public ?string $publicSdkKey = null;
    public ?string $privatePushKey = null;
    public ?string $visitorId = null;
    public ?string $sessionId = null;
    public ?array $payload = null;
    public ?array $context = null;
    public ?array $eventData = null;
    public ?string $status = null;
    public ?string $occurredAt = null;
}
