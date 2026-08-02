<?php

declare(strict_types=1);

namespace App\Domains\IAM\Enums;

enum UserRole: string
{
    case OWNER = 'OWNER';
    case ADMIN = 'ADMIN';
    case ANALYST = 'ANALYST';
    case VIEWER = 'VIEWER';

    public function canManageMembers(): bool
    {
        return match ($this) {
            self::OWNER, self::ADMIN => true,
            self::ANALYST, self::VIEWER => false,
        };
    }

    public function canManageInstance(): bool
    {
        return match ($this) {
            self::OWNER, self::ADMIN => true,
            self::ANALYST, self::VIEWER => false,
        };
    }

    public function canRotateKeys(): bool
    {
        return $this === self::OWNER;
    }
}
