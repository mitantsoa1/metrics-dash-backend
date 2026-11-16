<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Ingestion;

use App\Domains\Ingestion\Entities\RawEvent;
use App\Domains\Ingestion\Enums\EventType;
use App\Domains\Ingestion\Services\EventAnonymizationService;
use App\Domains\Ingestion\Services\EventIngestionService;
use App\Domains\Ingestion\Services\PushEventValidator;
use App\Domains\Ingestion\ValueObjects\EventName;
use App\Domains\Ingestion\ValueObjects\SessionId;
use App\Domains\Ingestion\ValueObjects\VisitorId;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * POST /api/events (PU-54) : réception des événements métier poussés par le backend client.
 *
 * Enveloppe : { event_type, timestamp, payload: { order_id, amount, category, customer_id, ... } }
 *  - clé privée : Authorization: Bearer sk_live_... (ou X-Push-Secret-Key)
 *  - 202 Accepted immédiat : l'écriture a lieu après l'envoi de la réponse (tampon d'ingestion)
 *  - X-Correlation-Id : repris s'il est fourni, sinon généré ; horodatage de réception côté serveur
 *  - Idempotency-Key (ou event_id) : un rejeu dans les 24 h renvoie le même event_id sans doublon
 *  - 400 erreurs de schéma structurées, 413 corps > 256 Ko, 429 au-delà de 1000 événements/minute
 */
class PushEventController extends Controller
{
    private const MAX_BODY_BYTES = 262144;
    private const IDEMPOTENCY_TTL_SECONDS = 86400;

    public function __invoke(
        Request $request,
        EventIngestionService $ingestion,
        PushEventValidator $validator,
        EventAnonymizationService $anonymizer
    ): JsonResponse {
        $correlationId = $this->correlationId($request);
        $headers = ['X-Correlation-Id' => $correlationId];

        $apiKey = $ingestion->resolvePushKey((string) ($request->bearerToken() ?: $request->header('X-Push-Secret-Key', '')), $request->ip());

        if (strlen($request->getContent()) > self::MAX_BODY_BYTES) {
            return response()->json(['error' => ['field' => 'body', 'code' => 'too_large', 'message' => 'Corps de requête supérieur à 256 Ko.'], 'correlation_id' => $correlationId], 413, $headers);
        }

        $body = $request->json()->all();
        $valid = $validator->validate(
            is_string($body['event_type'] ?? null) ? $body['event_type'] : null,
            $body['payload'] ?? null,
            $body['timestamp'] ?? null
        );

        $idempotencyKey = $request->header('Idempotency-Key') ?: ($body['event_id'] ?? null);
        $eventId = (string) Str::uuid();
        if (is_string($idempotencyKey) && $idempotencyKey !== '') {
            $cacheKey = 'push-idem:'.$apiKey->getInstanceId()->toString().':'.hash('sha256', $idempotencyKey);
            $stored = Cache::add($cacheKey, $eventId, self::IDEMPOTENCY_TTL_SECONDS) ? null : Cache::get($cacheKey);
            if ($stored !== null) {
                return response()->json(['status' => 'accepted', 'event_id' => $stored, 'duplicate' => true, 'correlation_id' => $correlationId], 202, $headers);
            }
        }

        $rawEvent = RawEvent::create(
            instanceId: $apiKey->getInstanceId(),
            eventType: EventType::BUSINESS,
            eventName: EventName::fromString($valid['eventName']),
            visitorId: isset($body['visitor_id']) ? VisitorId::fromString((string) $body['visitor_id']) : null,
            sessionId: isset($body['session_id']) ? SessionId::fromString((string) $body['session_id']) : null,
            payload: $valid['eventData'],
            context: [
                'anonymized_ip' => $anonymizer->anonymizeIp($request->ip() ?? '127.0.0.1'),
                'source' => 'API_PUSH_SERVER',
                'correlation_id' => $correlationId,
                'received_at' => now()->toIso8601String(),
            ],
            occurredAt: $valid['occurredAt']
        );
        $eventId = $rawEvent->getId()->toString();
        if (isset($cacheKey)) {
            Cache::put($cacheKey, $eventId, self::IDEMPOTENCY_TTL_SECONDS);
        }

        $ingestion->bufferAndPersist([$rawEvent]);

        return response()->json([
            'status' => 'accepted',
            'event_id' => $eventId,
            'duplicate' => false,
            'correlation_id' => $correlationId,
        ], 202, $headers);
    }

    private function correlationId(Request $request): string
    {
        $provided = (string) $request->header('X-Correlation-Id', '');

        return preg_match('/^[A-Za-z0-9._-]{8,64}$/', $provided) ? $provided : (string) Str::uuid();
    }
}
