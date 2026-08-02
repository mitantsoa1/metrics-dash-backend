<?php

declare(strict_types=1);

namespace App\Domains\Instance\DTOs;

final readonly class InstanceOutputDTO
{
    public function __construct(
        public string $id,
        public string $tenantId,
        public string $name,
        public string $domain,
        public string $environment,
        public string $status,
        public string $publicSdkPrefix,
        public string $privatePushPrefix,
        public string $createdAt
    ) {}
}
