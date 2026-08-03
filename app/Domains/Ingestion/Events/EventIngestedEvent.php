<?php

declare(strict_types=1);

namespace App\Domains\Ingestion\Events;

use App\Domains\Ingestion\ValueObjects\EventId;
use App\Domains\Instance\ValueObjects\InstanceId;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EventIngestedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly EventId $eventId,
        public readonly InstanceId $instanceId,
        public readonly string $eventName
    ) {}
}
