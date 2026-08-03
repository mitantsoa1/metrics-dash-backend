<?php

declare(strict_types=1);

namespace App\Domains\Ingestion\Enums;

enum EventType: string
{
    case NAVIGATION = 'NAVIGATION';
    case BUSINESS = 'BUSINESS';
    case CUSTOM = 'CUSTOM';
}
