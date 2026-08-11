<?php

declare(strict_types=1);

namespace App\Domains\Instance\Providers;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Domains\IAM\ValueObjects\TenantId;
use App\Domains\Instance\Resources\InstanceResource;
use App\Domains\Instance\Services\InstanceProvisioningService;

use App\Domains\IAM\Enums\UserRole;

class InstanceCollectionStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly InstanceProvisioningService $provisioningService
    ) {}

    /**
     * @return array<int, InstanceResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = auth()->user();
        $tenantIdStr = $uriVariables['tenantId'] ?? request()?->route('tenantId');

        $isSuperAdminOrAdmin = $user && in_array($user->role, [UserRole::SUPERADMIN->value, UserRole::ADMIN->value], true);

        if ($tenantIdStr === 'all' || ($isSuperAdminOrAdmin && ! $tenantIdStr)) {
            $dtos = $this->provisioningService->getAllInstances();
        } else {
            $tenantIdStr = $tenantIdStr ?? $user?->tenant_id;
            if (! $tenantIdStr) {
                return [];
            }
            $dtos = $this->provisioningService->getInstancesByTenant(TenantId::fromString($tenantIdStr));
        }

        return array_map(function ($dto) {
            $resource = new InstanceResource;
            $resource->id = $dto->id;
            $resource->tenantId = $dto->tenantId;
            $resource->name = $dto->name;
            $resource->domain = $dto->domain;
            $resource->environment = $dto->environment;
            $resource->status = $dto->status;
            $resource->publicSdkKey = $dto->publicSdkPrefix.'••••••••';
            $resource->privatePushKey = $dto->privatePushPrefix.'••••••••';
            $resource->createdAt = $dto->createdAt;

            return $resource;
        }, $dtos);
    }
}
