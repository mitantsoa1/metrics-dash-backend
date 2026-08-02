<?php

declare(strict_types=1);

namespace App\Domains\IAM\Processors;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domains\IAM\Actions\AddMemberAction;
use App\Domains\IAM\DTOs\AddMemberInputDTO;
use App\Domains\IAM\Resources\MemberResource;
use InvalidArgumentException;

class AddMemberStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly AddMemberAction $addMemberAction
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): MemberResource
    {
        if (! $data instanceof MemberResource) {
            throw new InvalidArgumentException('Data attendue de type MemberResource.');
        }

        $tenantId = $uriVariables['tenantId'] ?? $data->tenantId ?? '';

        $inputDto = new AddMemberInputDTO(
            tenantId: $tenantId,
            memberName: $data->name ?? '',
            memberEmail: $data->email ?? '',
            password: $data->password ?? '',
            role: $data->role ?? 'VIEWER'
        );

        $outputDto = $this->addMemberAction->execute($inputDto);

        $resource = new MemberResource;
        $resource->userId = $outputDto->userId;
        $resource->tenantId = $outputDto->tenantId;
        $resource->name = $outputDto->name;
        $resource->email = $outputDto->email;
        $resource->role = $outputDto->role;
        $resource->createdAt = $outputDto->createdAt;

        return $resource;
    }
}
