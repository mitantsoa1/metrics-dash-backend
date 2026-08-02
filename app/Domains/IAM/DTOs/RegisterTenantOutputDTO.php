<?php

declare(strict_types=1);

namespace App\Domains\IAM\DTOs;

final readonly class RegisterTenantOutputDTO
{
    public function __construct(
        public string $tenantId,
        public string $organizationName,
        public string $slug,
        public string $ownerUserId,
        public string $ownerEmail,
        public string $token
    ) {}
}
