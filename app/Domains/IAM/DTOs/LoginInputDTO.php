<?php

declare(strict_types=1);

namespace App\Domains\IAM\DTOs;

final readonly class LoginInputDTO
{
    public function __construct(
        public string $email,
        public string $password
    ) {}
}
