<?php

declare(strict_types=1);

namespace App\Domains\IAM\Enums;

enum TenantStatus: string
{
    case ACTIVE = 'ACTIVE';
    case PENDING_VERIFICATION = 'PENDING_VERIFICATION';
    case SUSPENDED = 'SUSPENDED';
    case ARCHIVED = 'ARCHIVED';
}
