<?php

declare(strict_types=1);

namespace App\Domains\IAM\DTOs;

final readonly class MemberOutputDTO
{
    public function __construct(
        public string $userId,
        public string $tenantId,
        public string $name,
        public string $email,
        public string $role,
        public string $createdAt
    ) {}
}
