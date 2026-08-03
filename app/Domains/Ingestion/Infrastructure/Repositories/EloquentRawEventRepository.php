<?php

declare(strict_types=1);

namespace App\Domains\Ingestion\Infrastructure\Repositories;

use App\Domains\Ingestion\Entities\RawEvent;
use App\Domains\Ingestion\Enums\EventType;
use App\Domains\Ingestion\Infrastructure\Models\RawEventModel;
use App\Domains\Ingestion\Repositories\RawEventRepositoryInterface;
use App\Domains\Ingestion\ValueObjects\EventId;
use App\Domains\Ingestion\ValueObjects\EventName;
use App\Domains\Ingestion\ValueObjects\SessionId;
use App\Domains\Ingestion\ValueObjects\VisitorId;
use App\Domains\Instance\ValueObjects\InstanceId;
use DateTimeImmutable;

class EloquentRawEventRepository implements RawEventRepositoryInterface
{
    public function findById(EventId $id): ?RawEvent
    {
        $model = RawEventModel::find($id->toString());
        if (! $model) {
            return null;
        }

        return new RawEvent(
            id: EventId::fromString($model->id),
            instanceId: InstanceId::fromString($model->instance_id),
            eventType: EventType::from($model->event_type),
            eventName: EventName::fromString($model->event_name),
            visitorId: $model->visitor_id ? VisitorId::fromString($model->visitor_id) : null,
            sessionId: $model->session_id ? SessionId::fromString($model->session_id) : null,
            payload: $model->payload ?? [],
            context: $model->context ?? [],
            occurredAt: DateTimeImmutable::createFromMutable($model->occurred_at)
        );
    }

    public function save(RawEvent $rawEvent): void
    {
        RawEventModel::updateOrCreate(
            ['id' => $rawEvent->getId()->toString()],
            [
                'instance_id' => $rawEvent->getInstanceId()->toString(),
                'event_type' => $rawEvent->getEventType()->value,
                'event_name' => $rawEvent->getEventName()->toString(),
                'visitor_id' => $rawEvent->getVisitorId()?->toString(),
                'session_id' => $rawEvent->getSessionId()?->toString(),
                'payload' => $rawEvent->getPayload(),
                'context' => $rawEvent->getContext(),
                'occurred_at' => $rawEvent->getOccurredAt()->format('Y-m-d H:i:s'),
            ]
        );
    }

    public function countByInstanceId(string $instanceId): int
    {
        return RawEventModel::where('instance_id', $instanceId)->count();
    }
}
