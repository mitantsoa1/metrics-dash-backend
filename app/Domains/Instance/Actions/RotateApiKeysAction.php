<?php

declare(strict_types=1);

namespace App\Domains\Instance\Actions;

use App\Domains\Instance\DTOs\RotateKeyInputDTO;
use App\Domains\Instance\Enums\ApiKeyType;
use App\Domains\Instance\Services\InstanceProvisioningService;
use App\Domains\Instance\ValueObjects\InstanceId;

class RotateApiKeysAction
{
    public function __construct(
        private readonly InstanceProvisioningService $provisioningService
    ) {}

    public function execute(RotateKeyInputDTO $dto): string
    {
        $instanceId = InstanceId::fromString($dto->instanceId);
        $type = ApiKeyType::from($dto->keyType);

        return $this->provisioningService->rotateApiKey($instanceId, $type);
    }
}
