<?php

declare(strict_types=1);

namespace App\Domains\Ingestion\Processors;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domains\Ingestion\Actions\IngestSdkEventAction;
use App\Domains\Ingestion\DTOs\IngestSdkEventInputDTO;
use App\Domains\Ingestion\Resources\IngestionResource;

class IngestSdkEventProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly IngestSdkEventAction $ingestSdkEventAction
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): IngestionResource
    {
        $request = $context['request'] ?? request();
        $reqData = $request?->json()?->all() ?? $request?->all() ?? [];

        $publicSdkKey = $request?->header('X-SDK-Key')
            ?: ($data?->publicSdkKey ?? $reqData['publicSdkKey'] ?? '');

        $eventName = $data?->eventName ?? $reqData['eventName'] ?? $reqData['event'] ?? 'page_view';
        $visitorId = $data?->visitorId ?? $reqData['visitorId'] ?? null;
        $sessionId = $data?->sessionId ?? $reqData['sessionId'] ?? null;
        $payload = $data?->payload ?? $reqData['payload'] ?? $reqData['data'] ?? [];
        $contextData = $data?->context ?? $reqData['context'] ?? [];

        $inputDto = new IngestSdkEventInputDTO(
            publicSdkKey: trim((string) $publicSdkKey),
            eventName: trim((string) $eventName),
            visitorId: $visitorId ? (string) $visitorId : null,
            sessionId: $sessionId ? (string) $sessionId : null,
            payload: is_array($payload) ? $payload : [],
            context: is_array($contextData) ? $contextData : [],
            ipAddress: $request?->ip(),
            userAgent: $request?->header('User-Agent')
        );

        $outputDto = $this->ingestSdkEventAction->execute($inputDto);

        $resource = new IngestionResource();
        $resource->eventId = $outputDto->eventId;
        $resource->instanceId = $outputDto->instanceId;
        $resource->eventName = $outputDto->eventName;
        $resource->status = $outputDto->status;
        $resource->occurredAt = $outputDto->occurredAt;

        return $resource;
    }
}
