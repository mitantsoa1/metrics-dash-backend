<?php

declare(strict_types=1);

namespace App\Domains\Ingestion\Enums;

enum DeviceType: string
{
    case DESKTOP = 'DESKTOP';
    case MOBILE = 'MOBILE';
    case TABLET = 'TABLET';
    case BOT = 'BOT';
    case UNKNOWN = 'UNKNOWN';
}
