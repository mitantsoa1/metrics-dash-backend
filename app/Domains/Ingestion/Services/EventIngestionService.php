<?php

declare(strict_types=1);

namespace App\Domains\Ingestion\Services;

use App\Domains\Ingestion\DTOs\IngestEventOutputDTO;
use App\Domains\Ingestion\DTOs\IngestPushEventInputDTO;
use App\Domains\Ingestion\DTOs\IngestSdkEventInputDTO;
use App\Domains\Ingestion\Entities\RawEvent;
use App\Domains\Ingestion\Enums\EventType;
use App\Domains\Ingestion\Events\EventIngestedEvent;
use App\Domains\Ingestion\Exceptions\InvalidApiKeyException;
use App\Domains\Ingestion\Repositories\RawEventRepositoryInterface;
use App\Domains\Ingestion\ValueObjects\EventName;
use App\Domains\Ingestion\ValueObjects\SessionId;
use App\Domains\Ingestion\ValueObjects\VisitorId;
use App\Domains\Instance\Repositories\ApiKeyRepositoryInterface;
use Illuminate\Support\Facades\Event;

class EventIngestionService
{
    public function __construct(
        private readonly ApiKeyRepositoryInterface $apiKeyRepository,
        private readonly RawEventRepositoryInterface $rawEventRepository,
        private readonly EventAnonymizationService $anonymizationService,
        private readonly UserAgentParserService $uaParserService
    ) {}

    public function ingestSdkEvent(IngestSdkEventInputDTO $dto): IngestEventOutputDTO
    {
        $keyHash = hash('sha256', $dto->publicSdkKey);
        $apiKey = $this->apiKeyRepository->findByKeyHash($keyHash);

        if (! $apiKey || ! str_starts_with($dto->publicSdkKey, 'pk_live_')) {
            throw InvalidApiKeyException::forType('PUBLIC_SDK', $dto->publicSdkKey);
        }

        $ip = $dto->ipAddress ?? '127.0.0.1';
        $ua = $dto->userAgent ?? '';

        $anonymizedIp = $this->anonymizationService->anonymizeIp($ip);
        $uaMetadata = $this->uaParserService->parse($ua);
        $anonymizedVisitorId = $this->anonymizationService->generateAnonymousVisitorId(
            $dto->visitorId,
            $ip,
            $ua
        );

        $context = array_merge($dto->context, [
            'anonymized_ip' => $anonymizedIp,
            'device_type' => $uaMetadata['device_type'],
            'browser' => $uaMetadata['browser'],
            'os' => $uaMetadata['os'],
            'user_agent' => $ua,
            'source' => 'SDK_WEB',
        ]);

        $rawEvent = RawEvent::create(
            instanceId: $apiKey->getInstanceId(),
            eventType: EventType::NAVIGATION,
            eventName: EventName::fromString($dto->eventName),
            visitorId: VisitorId::fromString($anonymizedVisitorId),
            sessionId: $dto->sessionId ? SessionId::fromString($dto->sessionId) : null,
            payload: $dto->payload,
            context: $context
        );

        $this->rawEventRepository->save($rawEvent);

        Event::dispatch(new EventIngestedEvent(
            $rawEvent->getId(),
            $rawEvent->getInstanceId(),
            $rawEvent->getEventName()->toString()
        ));

        return new IngestEventOutputDTO(
            eventId: $rawEvent->getId()->toString(),
            instanceId: $rawEvent->getInstanceId()->toString(),
            eventName: $rawEvent->getEventName()->toString(),
            status: 'ACCEPTED',
            occurredAt: $rawEvent->getOccurredAt()->format('Y-m-d H:i:s')
        );
    }

    public function ingestPushEvent(IngestPushEventInputDTO $dto): IngestEventOutputDTO
    {
        $keyHash = hash('sha256', $dto->privatePushKey);
        $apiKey = $this->apiKeyRepository->findByKeyHash($keyHash);

        if (! $apiKey || ! str_starts_with($dto->privatePushKey, 'sk_live_')) {
            throw InvalidApiKeyException::forType('PRIVATE_PUSH');
        }

        $ip = $dto->ipAddress ?? '127.0.0.1';
        $anonymizedIp = $this->anonymizationService->anonymizeIp($ip);

        $context = [
            'anonymized_ip' => $anonymizedIp,
            'source' => 'API_PUSH_SERVER',
        ];

        $rawEvent = RawEvent::create(
            instanceId: $apiKey->getInstanceId(),
            eventType: EventType::BUSINESS,
            eventName: EventName::fromString($dto->eventName),
            visitorId: $dto->visitorId ? VisitorId::fromString($dto->visitorId) : null,
            sessionId: $dto->sessionId ? SessionId::fromString($dto->sessionId) : null,
            payload: $dto->eventData,
            context: $context
        );

        $this->rawEventRepository->save($rawEvent);

        Event::dispatch(new EventIngestedEvent(
            $rawEvent->getId(),
            $rawEvent->getInstanceId(),
            $rawEvent->getEventName()->toString()
        ));

        return new IngestEventOutputDTO(
            eventId: $rawEvent->getId()->toString(),
            instanceId: $rawEvent->getInstanceId()->toString(),
            eventName: $rawEvent->getEventName()->toString(),
            status: 'ACCEPTED',
            occurredAt: $rawEvent->getOccurredAt()->format('Y-m-d H:i:s')
        );
    }
}

