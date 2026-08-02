<?php

declare(strict_types=1);

namespace App\Domains\IAM\Events;

use App\Domains\IAM\ValueObjects\TenantId;
use App\Domains\IAM\ValueObjects\UserId;

final readonly class TenantCreatedEvent
{
    public function __construct(
        public TenantId $tenantId,
        public UserId $ownerUserId
    ) {}
}
