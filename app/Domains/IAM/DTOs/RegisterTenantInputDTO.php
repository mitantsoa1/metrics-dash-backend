<?php

declare(strict_types=1);

namespace App\Domains\IAM\DTOs;

final readonly class RegisterTenantInputDTO
{
    public function __construct(
        public string $organizationName,
        public string $ownerName,
        public string $ownerEmail,
        public string $password,
        public ?string $role = null
    ) {}
}
