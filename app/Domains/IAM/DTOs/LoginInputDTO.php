<?php

declare(strict_types=1);

namespace App\Domains\IAM\DTOs;

final readonly class LoginInputDTO
{
    public function __construct(
        public string $email,
        public string $password,
        public bool $rememberMe = false,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {}
}
