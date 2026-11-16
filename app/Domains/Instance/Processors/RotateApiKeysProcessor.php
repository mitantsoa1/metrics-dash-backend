<?php

declare(strict_types=1);

namespace App\Domains\Instance\Processors;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domains\Instance\Actions\RotateApiKeysAction;
use App\Domains\Instance\DTOs\RotateKeyInputDTO;
use App\Domains\Instance\Resources\InstanceResource;

class RotateApiKeysProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly RotateApiKeysAction $rotateApiKeysAction
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): InstanceResource
    {
        $instanceId = $uriVariables['id'] ?? $data->id ?? '';
        $keyType = $data->rotatedKeyType ?? 'PUBLIC_SDK';

        $instance = \App\Domains\Instance\Infrastructure\Models\InstanceModel::findOrFail($instanceId);
        abort_unless(app(\App\Domains\Instance\Policies\InstancePolicy::class)->rotateKeys(auth()->user(), $instance), 403, 'Action non autorisée sur cette instance.');

        $inputDto = new RotateKeyInputDTO(
            instanceId: $instanceId,
            keyType: $keyType
        );

        $newPlainKey = $this->rotateApiKeysAction->execute($inputDto);

        $resource = new InstanceResource;
        $resource->id = $instanceId;
        $resource->rotatedKeyType = $keyType;
        $resource->newPlainKey = $newPlainKey;

        return $resource;
    }
}
