<?php

declare(strict_types=1);

namespace App\Domains\IAM\Processors;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domains\IAM\Actions\AuthenticateUserAction;
use App\Domains\IAM\DTOs\LoginInputDTO;
use App\Domains\IAM\Resources\AuthResource;

class LoginStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly AuthenticateUserAction $authenticateUserAction
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AuthResource
    {
        $req = request()?->json()?->all() ?? request()?->all() ?? [];

        $email = ! empty($data?->email) ? $data->email
            : (! empty($req['email']) ? $req['email']
            : '');

        $password = ! empty($data?->password) ? $data->password
            : (! empty($req['password']) ? $req['password']
            : '');

        $inputDto = new LoginInputDTO(
            email: trim((string) $email),
            password: (string) $password
        );

        $outputDto = $this->authenticateUserAction->execute($inputDto);

        $resource = new AuthResource;
        $resource->token = $outputDto->token;
        $resource->tokenType = $outputDto->tokenType;
        $resource->userId = $outputDto->userId;
        $resource->tenantId = $outputDto->tenantId;
        $resource->role = $outputDto->role;
        $resource->email = $outputDto->email;

        return $resource;
    }
}
