<?php

declare(strict_types=1);

namespace App\Domains\IAM\Providers;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Domains\IAM\Exceptions\TenantNotFoundException;
use App\Domains\IAM\Repositories\TenantRepositoryInterface;
use App\Domains\IAM\Resources\TenantResource;
use App\Domains\IAM\ValueObjects\TenantId;

class TenantStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TenantResource
    {
        $id = $uriVariables['id'] ?? null;
        if (! $id) {
            throw new TenantNotFoundException('Aucun identifiant Tenant fourni.');
        }

        $tenant = $this->tenantRepository->findById(TenantId::fromString($id));
        if (! $tenant) {
            throw TenantNotFoundException::forId($id);
        }

        $resource = new TenantResource;
        $resource->id = $tenant->getId()->toString();
        $resource->organizationName = $tenant->getName();
        $resource->slug = $tenant->getSlug();
        $resource->status = $tenant->getStatus()->value;

        return $resource;
    }
}
