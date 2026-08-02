<?php

declare(strict_types=1);

namespace App\Domains\Instance\Enums;

enum Environment: string
{
    case PRODUCTION = 'PRODUCTION';
    case STAGING = 'STAGING';
    case DEVELOPMENT = 'DEVELOPMENT';
}
