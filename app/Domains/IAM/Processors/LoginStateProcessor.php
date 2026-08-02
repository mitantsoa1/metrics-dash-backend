<?php

declare(strict_types=1);

namespace App\Domains\IAM\Processors;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domains\IAM\Actions\AuthenticateUserAction;
use App\Domains\IAM\DTOs\LoginInputDTO;
use App\Domains\IAM\Resources\AuthResource;
use InvalidArgumentException;

class LoginStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly AuthenticateUserAction $authenticateUserAction
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AuthResource
    {
        if (! $data instanceof AuthResource) {
            throw new InvalidArgumentException('Data attendue de type AuthResource.');
        }

        $inputDto = new LoginInputDTO(
            email: $data->email ?? '',
            password: $data->password ?? ''
        );

        $outputDto = $this->authenticateUserAction->execute($inputDto);

        $resource = new AuthResource;
        $resource->token = $outputDto->token;
        $resource->tokenType = $outputDto->tokenType;
        $resource->userId = $outputDto->userId;
        $resource->tenantId = $outputDto->tenantId;
        $resource->role = $outputDto->role;
        $resource->email = $data->email;

        return $resource;
    }
}
