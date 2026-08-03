<?php

declare(strict_types=1);

namespace App\Domains\Ingestion\Entities;

use App\Domains\Ingestion\Enums\EventType;
use App\Domains\Ingestion\ValueObjects\EventId;
use App\Domains\Ingestion\ValueObjects\EventName;
use App\Domains\Ingestion\ValueObjects\SessionId;
use App\Domains\Ingestion\ValueObjects\VisitorId;
use App\Domains\Instance\ValueObjects\InstanceId;
use DateTimeImmutable;

class RawEvent
{
    public function __construct(
        private readonly EventId $id,
        private readonly InstanceId $instanceId,
        private readonly EventType $eventType,
        private readonly EventName $eventName,
        private readonly ?VisitorId $visitorId,
        private readonly ?SessionId $sessionId,
        private readonly array $payload,
        private readonly array $context,
        private readonly DateTimeImmutable $occurredAt
    ) {}

    public static function create(
        InstanceId $instanceId,
        EventType $eventType,
        EventName $eventName,
        ?VisitorId $visitorId = null,
        ?SessionId $sessionId = null,
        array $payload = [],
        array $context = [],
        ?DateTimeImmutable $occurredAt = null
    ): self {
        return new self(
            EventId::generate(),
            $instanceId,
            $eventType,
            $eventName,
            $visitorId,
            $sessionId,
            $payload,
            $context,
            $occurredAt ?? new DateTimeImmutable()
        );
    }

    public function getId(): EventId
    {
        return $this->id;
    }

    public function getInstanceId(): InstanceId
    {
        return $this->instanceId;
    }

    public function getEventType(): EventType
    {
        return $this->eventType;
    }

    public function getEventName(): EventName
    {
        return $this->eventName;
    }

    public function getVisitorId(): ?VisitorId
    {
        return $this->visitorId;
    }

    public function getSessionId(): ?SessionId
    {
        return $this->sessionId;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
