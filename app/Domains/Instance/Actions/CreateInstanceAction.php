<?php

declare(strict_types=1);

namespace App\Domains\Instance\Actions;

use App\Domains\Instance\DTOs\CreateInstanceInputDTO;
use App\Domains\Instance\DTOs\CreateInstanceOutputDTO;
use App\Domains\Instance\Services\InstanceProvisioningService;

class CreateInstanceAction
{
    public function __construct(
        private readonly InstanceProvisioningService $provisioningService
    ) {}

    public function execute(CreateInstanceInputDTO $dto): CreateInstanceOutputDTO
    {
        return $this->provisioningService->createInstance($dto);
    }
}
