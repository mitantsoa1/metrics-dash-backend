<?php

declare(strict_types=1);

namespace App\Domains\IAM\DTOs;

final readonly class AuthTokenOutputDTO
{
    public function __construct(
        public string $token,
        public string $tokenType,
        public string $userId,
        public string $tenantId,
        public string $role
    ) {}
}
