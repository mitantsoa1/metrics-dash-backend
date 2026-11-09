<?php

declare(strict_types=1);

namespace App\Domains\Instance\Providers;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Domains\Instance\Exceptions\InstanceNotFoundException;
use App\Domains\Instance\Infrastructure\Models\ApiKeyModel;
use App\Domains\Instance\Repositories\InstanceRepositoryInterface;
use App\Domains\Instance\Resources\InstanceResource;
use App\Domains\Instance\ValueObjects\InstanceId;

class InstanceStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly InstanceRepositoryInterface $instanceRepository
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): InstanceResource
    {
        $id = $uriVariables['id'] ?? null;
        if (! $id) {
            throw new InstanceNotFoundException("Identifiant d'instance non fourni.");
        }

        $instance = $this->instanceRepository->findById(InstanceId::fromString($id));
        if (! $instance) {
            throw InstanceNotFoundException::forId($id);
        }

        $model = \App\Domains\Instance\Infrastructure\Models\InstanceModel::find($id);
        abort_unless($model && app(\App\Domains\Instance\Policies\InstancePolicy::class)->view(auth()->user(), $model), 404, 'Instance introuvable.');

        $publicApiKey = ApiKeyModel::where('instance_id', $id)
            ->where('type', 'PUBLIC_SDK')
            ->first();

        $privateApiKey = ApiKeyModel::where('instance_id', $id)
            ->where('type', 'PRIVATE_PUSH')
            ->first();

        $resource = new InstanceResource;
        $resource->id = $instance->getId()->toString();
        $resource->tenantId = $instance->getTenantId()->toString();
        $resource->name = $instance->getName();
        $resource->domain = $instance->getDomain()->toString();
        $resource->environment = $instance->getEnvironment()->value;
        $resource->status = $instance->getStatus()->value;
        $resource->publicSdkKey = $publicApiKey?->plain_key ?? $publicApiKey?->prefix ?? 'Indisponible';
        $resource->privatePushKey = InstanceResource::maskSecret($privateApiKey?->plain_key);
        $resource->createdAt = $instance->getCreatedAt()->format(DATE_ATOM);

        return $resource;
    }
}
