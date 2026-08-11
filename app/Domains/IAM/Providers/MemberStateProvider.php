<?php

declare(strict_types=1);

namespace App\Domains\IAM\Providers;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Domains\IAM\Resources\MemberResource;
use App\Domains\IAM\Services\MemberManagementService;
use App\Domains\IAM\ValueObjects\TenantId;

use App\Domains\IAM\Enums\UserRole;
use App\Domains\IAM\ValueObjects\UserId;

class MemberStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly MemberManagementService $memberService
    ) {}

    /**
     * @return MemberResource|array<int, MemberResource>|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $userIdStr = $uriVariables['userId'] ?? null;
        if ($userIdStr) {
            $dto = $this->memberService->getMemberById(UserId::fromString($userIdStr));
            if (! $dto) {
                return null;
            }

            return $this->toResource($dto);
        }

        $user = auth()->user();
        $tenantIdStr = $uriVariables['tenantId'] ?? request()?->route('tenantId');
        $isSuperAdminOrAdmin = $user && in_array($user->role, [UserRole::SUPERADMIN->value, UserRole::ADMIN->value], true);

        if ($tenantIdStr === 'all' || ($isSuperAdminOrAdmin && ! $tenantIdStr)) {
            $dtos = $this->memberService->getAllMembers();
        } else {
            $tenantIdStr = $tenantIdStr ?? $user?->tenant_id;
            if (! $tenantIdStr) {
                return [];
            }
            $dtos = $this->memberService->getMembers(TenantId::fromString($tenantIdStr));
        }

        return array_map(fn ($dto) => $this->toResource($dto), $dtos);
    }

    private function toResource($dto): MemberResource
    {
        $resource = new MemberResource;
        $resource->userId = $dto->userId;
        $resource->tenantId = $dto->tenantId;
        $resource->name = $dto->name;
        $resource->email = $dto->email;
        $resource->role = $dto->role;
        $resource->createdAt = $dto->createdAt;

        return $resource;
    }
}
