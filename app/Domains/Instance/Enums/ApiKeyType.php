<?php

declare(strict_types=1);

namespace App\Domains\Instance\Enums;

enum ApiKeyType: string
{
    case PUBLIC_SDK = 'PUBLIC_SDK';
    case PRIVATE_PUSH = 'PRIVATE_PUSH';

    public function getPrefix(): string
    {
        return match ($this) {
            self::PUBLIC_SDK => 'pk_live_',
            self::PRIVATE_PUSH => 'sk_live_',
        };
    }
}
