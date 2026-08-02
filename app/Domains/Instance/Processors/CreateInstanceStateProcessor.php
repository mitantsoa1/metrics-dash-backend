<?php

declare(strict_types=1);

namespace App\Domains\Instance\Processors;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domains\Instance\Actions\CreateInstanceAction;
use App\Domains\Instance\DTOs\CreateInstanceInputDTO;
use App\Domains\Instance\Resources\InstanceResource;
use InvalidArgumentException;

class CreateInstanceStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly CreateInstanceAction $createInstanceAction
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): InstanceResource
    {
        if (! $data instanceof InstanceResource) {
            throw new InvalidArgumentException('Data attendue de type InstanceResource.');
        }

        $request = $context['request'] ?? null;
        $user = $request?->user();
        $tenantId = $data->tenantId ?? $user?->tenant_id ?? '';

        $inputDto = new CreateInstanceInputDTO(
            tenantId: $tenantId,
            name: $data->name ?? '',
            domain: $data->domain ?? '',
            environment: $data->environment ?? 'PRODUCTION'
        );

        $outputDto = $this->createInstanceAction->execute($inputDto);

        $resource = new InstanceResource;
        $resource->id = $outputDto->id;
        $resource->tenantId = $outputDto->tenantId;
        $resource->name = $outputDto->name;
        $resource->domain = $outputDto->domain;
        $resource->environment = $outputDto->environment;
        $resource->status = $outputDto->status;
        $resource->publicSdkKey = $outputDto->apiKeys->publicSdkKey;
        $resource->privatePushKey = $outputDto->apiKeys->privatePushKey;
        $resource->createdAt = $outputDto->createdAt;

        return $resource;
    }
}
