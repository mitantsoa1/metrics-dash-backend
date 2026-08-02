<?php

declare(strict_types=1);

namespace App\Domains\IAM\DTOs;

final readonly class AddMemberInputDTO
{
    public function __construct(
        public string $tenantId,
        public string $memberName,
        public string $memberEmail,
        public string $password,
        public string $role
    ) {}
}
