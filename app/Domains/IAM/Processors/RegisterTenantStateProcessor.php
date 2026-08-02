<?php

declare(strict_types=1);

namespace App\Domains\IAM\Processors;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domains\IAM\Actions\RegisterTenantAction;
use App\Domains\IAM\DTOs\RegisterTenantInputDTO;
use App\Domains\IAM\Resources\TenantResource;
use InvalidArgumentException;

class RegisterTenantStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly RegisterTenantAction $registerAction
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): TenantResource
    {
        if (! $data instanceof TenantResource) {
            throw new InvalidArgumentException('Data attendue de type TenantResource.');
        }

        $ownerEmail = ! empty($data->ownerEmail) ? $data->ownerEmail : (! empty($data->email) ? $data->email : '');
        $ownerName = ! empty($data->ownerName) ? $data->ownerName : (! empty($data->name) ? $data->name : '');
        $organizationName = $data->organizationName ?? '';

        $inputDto = new RegisterTenantInputDTO(
            organizationName: trim($organizationName),
            ownerName: trim($ownerName),
            ownerEmail: trim($ownerEmail),
            password: $data->password ?? ''
        );

        $outputDto = $this->registerAction->execute($inputDto);

        $resource = new TenantResource;
        $resource->id = $outputDto->tenantId;
        $resource->organizationName = $outputDto->organizationName;
        $resource->slug = $outputDto->slug;
        $resource->ownerUserId = $outputDto->ownerUserId;
        $resource->ownerEmail = $outputDto->ownerEmail;
        $resource->email = $outputDto->ownerEmail;
        $resource->ownerName = $ownerName;
        $resource->name = $ownerName;
        $resource->status = 'ACTIVE';
        $resource->token = $outputDto->token;

        return $resource;
    }
}
