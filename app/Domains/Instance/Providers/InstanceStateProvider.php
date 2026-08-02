<?php

declare(strict_types=1);

namespace App\Domains\Instance\Providers;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Domains\Instance\Exceptions\InstanceNotFoundException;
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

        $resource = new InstanceResource;
        $resource->id = $instance->getId()->toString();
        $resource->tenantId = $instance->getTenantId()->toString();
        $resource->name = $instance->getName();
        $resource->domain = $instance->getDomain()->toString();
        $resource->environment = $instance->getEnvironment()->value;
        $resource->status = $instance->getStatus()->value;
        $resource->createdAt = $instance->getCreatedAt()->format(DATE_ATOM);

        return $resource;
    }
}
