<?php

declare(strict_types=1);

namespace App\Domains\IAM\DTOs;

final readonly class AuthTokenOutputDTO
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public string $tokenType,
        public string $accessTokenExpiresAt,
        public string $userId,
        public string $tenantId,
        public string $role,
        public string $email,
        public string $name,
        public ?string $emailVerifiedAt = null,
        public bool $twoFactorRequired = false,
        public ?string $challengeToken = null,
    ) {}
}
