<?php

declare(strict_types=1);

namespace App\Domains\IAM\Processors;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domains\IAM\Resources\MemberResource;
use App\Domains\IAM\Services\MemberManagementService;
use App\Domains\IAM\ValueObjects\UserId;

class UpdateMemberStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly MemberManagementService $memberService
    ) {}

    /**
     * @param MemberResource $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): MemberResource
    {
        $userIdStr = $uriVariables['userId'] ?? $data->userId ?? null;
        if (! $userIdStr) {
            throw new \InvalidArgumentException("Identifiant utilisateur manquant.");
        }

        $updateData = [];
        if ($data->name !== null) {
            $updateData['name'] = $data->name;
        }
        if ($data->email !== null) {
            $updateData['email'] = $data->email;
        }
        if ($data->role !== null) {
            $updateData['role'] = $data->role;
        }
        if (! empty($data->password)) {
            $updateData['password'] = $data->password;
        }

        $dto = $this->memberService->updateMember(UserId::fromString($userIdStr), $updateData);

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
