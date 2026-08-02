<?php

declare(strict_types=1);

namespace App\Domains\Instance\DTOs;

final readonly class RotateKeyInputDTO
{
    public function __construct(
        public string $instanceId,
        public string $keyType // PUBLIC_SDK ou PRIVATE_PUSH
    ) {}
}
