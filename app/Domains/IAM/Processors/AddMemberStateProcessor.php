<?php

declare(strict_types=1);

namespace App\Domains\IAM\Processors;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domains\IAM\Actions\AddMemberAction;
use App\Domains\IAM\DTOs\AddMemberInputDTO;
use App\Domains\IAM\Resources\MemberResource;

class AddMemberStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly AddMemberAction $addMemberAction
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): MemberResource
    {
        $req = request()?->json()?->all() ?? request()?->all() ?? [];

        $tenantId = $uriVariables['tenantId'] ?? $data?->tenantId ?? $req['tenantId'] ?? '';
        $name = ! empty($data?->name) ? $data->name : (! empty($req['name']) ? $req['name'] : '');
        $email = ! empty($data?->email) ? $data->email : (! empty($req['email']) ? $req['email'] : '');
        $password = ! empty($data?->password) ? $data->password : (! empty($req['password']) ? $req['password'] : '');
        $role = ! empty($data?->role) ? $data->role : (! empty($req['role']) ? $req['role'] : 'VIEWER');

        $inputDto = new AddMemberInputDTO(
            tenantId: (string) $tenantId,
            memberName: trim((string) $name),
            memberEmail: trim((string) $email),
            password: (string) $password,
            role: (string) $role
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
