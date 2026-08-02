<?php

declare(strict_types=1);

namespace App\Domains\Instance\DTOs;

final readonly class ApiKeyPairDTO
{
    public function __construct(
        public string $publicSdkKey,
        public string $privatePushKey
    ) {}
}
