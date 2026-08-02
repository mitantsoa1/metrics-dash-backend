<?php

declare(strict_types=1);

namespace App\Domains\Instance\Events;

use App\Domains\IAM\ValueObjects\TenantId;
use App\Domains\Instance\ValueObjects\InstanceId;

final readonly class InstanceCreatedEvent
{
    public function __construct(
        public InstanceId $instanceId,
        public TenantId $tenantId
    ) {}
}
