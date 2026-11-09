<?php

declare(strict_types=1);

namespace App\Domains\Ingestion\Processors;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domains\Ingestion\Actions\IngestPushEventAction;
use App\Domains\Ingestion\DTOs\IngestPushEventInputDTO;
use App\Domains\Ingestion\Resources\IngestionResource;

class IngestPushEventProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly IngestPushEventAction $ingestPushEventAction
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): IngestionResource
    {
        $request = $context['request'] ?? request();
        $reqData = $request?->json()?->all() ?? $request?->all() ?? [];

        $privatePushKey = $request?->header('X-Push-Secret-Key')
            ?: ($request?->header('X-Push-Key')
            ?: ($data?->privatePushKey ?? $reqData['privatePushKey'] ?? ''));

        $eventName = $reqData['event_type'] ?? $data?->eventName ?? $reqData['eventName'] ?? $reqData['event'] ?? null;
        $eventData = $data?->eventData ?? $reqData['eventData'] ?? $reqData['payload'] ?? $reqData['data'] ?? [];
        $visitorId = $data?->visitorId ?? $reqData['visitorId'] ?? null;
        $sessionId = $data?->sessionId ?? $reqData['sessionId'] ?? null;

        $inputDto = new IngestPushEventInputDTO(
            privatePushKey: trim((string) $privatePushKey),
            eventName: is_string($eventName) ? $eventName : null,
            eventData: $eventData,
            visitorId: $visitorId ? (string) $visitorId : null,
            sessionId: $sessionId ? (string) $sessionId : null,
            ipAddress: $request?->ip(),
            timestamp: $reqData['timestamp'] ?? $reqData['occurredAt'] ?? null
        );

        $outputDto = $this->ingestPushEventAction->execute($inputDto);

        $resource = new IngestionResource();
        $resource->eventId = $outputDto->eventId;
        $resource->instanceId = $outputDto->instanceId;
        $resource->eventName = $outputDto->eventName;
        $resource->status = $outputDto->status;
        $resource->occurredAt = $outputDto->occurredAt;

        return $resource;
    }
}
