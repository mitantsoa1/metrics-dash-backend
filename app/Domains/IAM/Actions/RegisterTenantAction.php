<?php

declare(strict_types=1);

namespace App\Domains\IAM\Actions;

use App\Domains\IAM\DTOs\RegisterTenantInputDTO;
use App\Domains\IAM\DTOs\RegisterTenantOutputDTO;
use App\Domains\IAM\Services\TenantRegistrationService;

class RegisterTenantAction
{
    public function __construct(
        private readonly TenantRegistrationService $registrationService
    ) {}

    public function execute(RegisterTenantInputDTO $dto): RegisterTenantOutputDTO
    {
        return $this->registrationService->register($dto);
    }
}
