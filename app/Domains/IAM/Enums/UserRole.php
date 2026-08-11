<?php

declare(strict_types=1);

namespace App\Domains\IAM\Enums;

enum UserRole: string
{
    case SUPERADMIN = 'SUPERADMIN';
    case OWNER = 'OWNER';
    case ADMIN = 'ADMIN';
    case ANALYST = 'ANALYST';
    case VIEWER = 'VIEWER';

    public function isSuperAdmin(): bool
    {
        return $this === self::SUPERADMIN;
    }

    public function isAdmin(): bool
    {
        return $this === self::SUPERADMIN || $this === self::ADMIN;
    }

    public function canManageMembers(): bool
    {
        return match ($this) {
            self::SUPERADMIN, self::OWNER, self::ADMIN => true,
            self::ANALYST, self::VIEWER => false,
        };
    }

    public function canManageInstance(): bool
    {
        return match ($this) {
            self::SUPERADMIN, self::OWNER, self::ADMIN => true,
            self::ANALYST, self::VIEWER => false,
        };
    }

    public function canRotateKeys(): bool
    {
        return match ($this) {
            self::SUPERADMIN, self::OWNER, self::ADMIN => true,
            self::ANALYST, self::VIEWER => false,
        };
    }
}
