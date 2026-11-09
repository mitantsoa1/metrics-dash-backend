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
use App\Domains\Instance\Entities\ApiKey;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class EventIngestionService
{
    public function __construct(
        private readonly ApiKeyRepositoryInterface $apiKeyRepository,
        private readonly RawEventRepositoryInterface $rawEventRepository,
        private readonly EventAnonymizationService $anonymizationService,
        private readonly UserAgentParserService $uaParserService,
        private readonly PushEventValidator $pushValidator
    ) {}

    public function ingestSdkEvent(IngestSdkEventInputDTO $dto): IngestEventOutputDTO
    {
        $apiKey = $this->resolveKey($dto->publicSdkKey, 'pk_live_', 'PUBLIC_SDK', $dto->ipAddress);

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
        $apiKey = $this->resolvePushKey($dto->privatePushKey, $dto->ipAddress);

        $valid = $this->pushValidator->validate($dto->eventName, $dto->eventData, $dto->timestamp);

        $ip = $dto->ipAddress ?? '127.0.0.1';
        $anonymizedIp = $this->anonymizationService->anonymizeIp($ip);

        $context = [
            'anonymized_ip' => $anonymizedIp,
            'source' => 'API_PUSH_SERVER',
        ];

        $rawEvent = RawEvent::create(
            instanceId: $apiKey->getInstanceId(),
            eventType: EventType::BUSINESS,
            eventName: EventName::fromString($valid['eventName']),
            visitorId: $dto->visitorId ? VisitorId::fromString($dto->visitorId) : null,
            sessionId: $dto->sessionId ? SessionId::fromString($dto->sessionId) : null,
            payload: $valid['eventData'],
            context: $context,
            occurredAt: $valid['occurredAt']
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

    /** Clé publique SDK (pk_live_) : vérification à l'initialisation du SDK (PU-26). */
    public function resolvePublicKey(string $plainKey, ?string $ip = null): ApiKey
    {
        return $this->resolveKey($plainKey, 'pk_live_', 'PUBLIC_SDK', $ip);
    }

    /** Clé privée Push (sk_live_) : 401 si absente/invalide, tentative refusée journalisée. */
    public function resolvePushKey(string $plainKey, ?string $ip = null): ApiKey
    {
        return $this->resolveKey($plainKey, 'sk_live_', 'PRIVATE_PUSH', $ip);
    }

    private function resolveKey(string $plainKey, string $prefix, string $type, ?string $ip): ApiKey
    {
        $apiKey = $plainKey !== '' && str_starts_with($plainKey, $prefix)
            ? $this->apiKeyRepository->findByKeyHash(hash('sha256', $plainKey))
            : null;

        if (! $apiKey) {
            // Journal des refus (supervision) : jamais la clé complète
            Log::warning('ingestion.key_refused', [
                'type' => $type,
                'ip' => $ip,
                'key_prefix' => substr($plainKey, 0, 12),
            ]);

            throw InvalidApiKeyException::forType($type, $plainKey);
        }

        return $apiKey;
    }

    /** Correspondance des événements émis par le SDK navigateur vers les noms métier du dashboard. */
    private const SDK_EVENT_NAMES = [
        'pageview' => 'page_view',
        'click' => 'click_interaction',
        'form_submit' => 'form_submit',
        'session_ping' => 'page_duration',
    ];

    public const MAX_SDK_BATCH = 100;

    /**
     * Lot d'événements du SDK (PU-31) : la clé est résolue une fois, les événements sont
     * mis en tampon puis écrits après l'envoi de la réponse (PU-51).
     *
     * @param  array<int, array<string, mixed>>  $events
     */
    public function ingestSdkBatch(
        string $publicSdkKey,
        array $events,
        ?string $visitorId,
        ?string $sessionId,
        ?string $ip,
        ?string $userAgent
    ): int {
        $apiKey = $this->resolveKey($publicSdkKey, 'pk_live_', 'PUBLIC_SDK', $ip);

        $ip ??= '127.0.0.1';
        $ua = $userAgent ?? '';
        $uaMeta = $this->uaParserService->parse($ua);
        $anonymizedVisitorId = $this->anonymizationService->generateAnonymousVisitorId($visitorId, $ip, $ua);

        $rawEvents = [];
        foreach (array_slice($events, 0, self::MAX_SDK_BATCH) as $item) {
            if (! is_array($item) || empty($item['event_type']) || ! is_string($item['event_type'])) {
                continue;
            }

            $type = $item['event_type'];
            $name = self::SDK_EVENT_NAMES[$type] ?? strtolower(preg_replace('/[^A-Za-z0-9_]+/', '_', $type));
            $timestamp = $item['timestamp'] ?? null;
            unset($item['event_type'], $item['timestamp']);

            if ($type === 'session_ping') {
                $item['page_duration'] = (int) ($item['total_duration_s'] ?? 0);
                $item['active_duration'] = (int) ($item['engaged_duration_s'] ?? 0);
            }

            try {
                $occurredAt = is_string($timestamp) ? new \DateTimeImmutable($timestamp) : null;
            } catch (\Throwable) {
                $occurredAt = null;
            }

            $rawEvents[] = RawEvent::create(
                instanceId: $apiKey->getInstanceId(),
                eventType: EventType::NAVIGATION,
                eventName: EventName::fromString(substr($name !== '' ? $name : 'custom_event', 0, 64)),
                visitorId: VisitorId::fromString($anonymizedVisitorId),
                sessionId: $sessionId ? SessionId::fromString($sessionId) : null,
                payload: $item,
                context: [
                    'anonymized_ip' => $this->anonymizationService->anonymizeIp($ip),
                    'device_type' => $uaMeta['device_type'],
                    'browser' => $uaMeta['browser'],
                    'os' => $uaMeta['os'],
                    'source' => 'SDK_WEB',
                ],
                occurredAt: $occurredAt
            );
        }

        $this->bufferAndPersist($rawEvents);

        return count($rawEvents);
    }

    /**
     * Tampon d'ingestion (PU-51) : la réponse part sans attendre l'écriture en base.
     * Exécuté après l'envoi de la réponse, sans dépendre d'un worker de queue.
     *
     * @param  array<int, RawEvent>  $rawEvents
     */
    public function bufferAndPersist(array $rawEvents): void
    {
        if ($rawEvents === []) {
            return;
        }

        dispatch(new \App\Domains\Ingestion\Jobs\PersistRawEventsJob($rawEvents))->afterResponse();
    }
}

