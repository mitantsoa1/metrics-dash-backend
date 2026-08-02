<?php

declare(strict_types=1);

namespace App\Domains\Instance\Events;

use App\Domains\Instance\Enums\ApiKeyType;
use App\Domains\Instance\ValueObjects\InstanceId;

final readonly class ApiKeyRotatedEvent
{
    public function __construct(
        public InstanceId $instanceId,
        public ApiKeyType $type
    ) {}
}
