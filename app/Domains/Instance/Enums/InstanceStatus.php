<?php

declare(strict_types=1);

namespace App\Domains\Instance\Enums;

enum InstanceStatus: string
{
    case ACTIVE = 'ACTIVE';
    case DISABLED = 'DISABLED';
}
