<?php

declare(strict_types=1);

namespace App\Domains\IAM\Providers;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Domains\IAM\Resources\MemberResource;
use App\Domains\IAM\Services\MemberManagementService;
use App\Domains\IAM\ValueObjects\TenantId;

class MemberStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly MemberManagementService $memberService
    ) {}

    /**
     * @return array<int, MemberResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $tenantIdStr = $uriVariables['tenantId'] ?? null;
        if (! $tenantIdStr) {
            return [];
        }

        $dtos = $this->memberService->getMembers(TenantId::fromString($tenantIdStr));

        return array_map(function ($dto) {
            $resource = new MemberResource;
            $resource->userId = $dto->userId;
            $resource->tenantId = $dto->tenantId;
            $resource->name = $dto->name;
            $resource->email = $dto->email;
            $resource->role = $dto->role;
            $resource->createdAt = $dto->createdAt;

            return $resource;
        }, $dtos);
    }
}
