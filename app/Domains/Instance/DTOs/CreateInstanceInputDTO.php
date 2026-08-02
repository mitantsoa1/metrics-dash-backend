<?php

declare(strict_types=1);

namespace App\Domains\Instance\DTOs;

final readonly class CreateInstanceInputDTO
{
    public function __construct(
        public string $tenantId,
        public string $name,
        public string $domain,
        public string $environment = 'PRODUCTION'
    ) {}
}
