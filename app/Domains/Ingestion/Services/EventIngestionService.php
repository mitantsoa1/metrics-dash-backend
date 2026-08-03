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
        private readonly RawEventRepositoryInterface $rawEventRepository
    ) {}

    public function ingestSdkEvent(IngestSdkEventInputDTO $dto): IngestEventOutputDTO
    {
        $keyHash = hash('sha256', $dto->publicSdkKey);
        $apiKey = $this->apiKeyRepository->findByKeyHash($keyHash);

        if (! $apiKey || ! str_starts_with($dto->publicSdkKey, 'pk_live_')) {
            throw InvalidApiKeyException::forType('PUBLIC_SDK');
        }

        $anonymizedIp = $this->anonymizeIp($dto->ipAddress);
        $context = array_merge($dto->context, [
            'anonymizedIp' => $anonymizedIp,
            'userAgent' => $dto->userAgent,
            'source' => 'SDK_WEB',
        ]);

        $rawEvent = RawEvent::create(
            instanceId: $apiKey->getInstanceId(),
            eventType: EventType::NAVIGATION,
            eventName: EventName::fromString($dto->eventName),
            visitorId: $dto->visitorId ? VisitorId::fromString($dto->visitorId) : null,
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

        $anonymizedIp = $this->anonymizeIp($dto->ipAddress);
        $context = [
            'anonymizedIp' => $anonymizedIp,
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

    private function anonymizeIp(?string $ip): string
    {
        if (! $ip) {
            return '0.0.0.0';
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[3] = '0';
            return implode('.', $parts);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            return implode(':', array_slice($parts, 0, 3)).'::';
        }

        return '0.0.0.0';
    }
}
