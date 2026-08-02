<?php

declare(strict_types=1);

namespace App\Domains\IAM\Processors;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domains\IAM\Actions\RegisterTenantAction;
use App\Domains\IAM\DTOs\RegisterTenantInputDTO;
use App\Domains\IAM\Resources\TenantResource;

class RegisterTenantStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly RegisterTenantAction $registerAction
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): TenantResource
    {
        $req = request()?->json()?->all() ?? request()?->all() ?? [];

        $ownerEmail = ! empty($data?->ownerEmail) ? $data->ownerEmail
            : (! empty($data?->email) ? $data->email
            : (! empty($req['ownerEmail']) ? $req['ownerEmail']
            : (! empty($req['email']) ? $req['email']
            : '')));

        $ownerName = ! empty($data?->ownerName) ? $data->ownerName
            : (! empty($data?->name) ? $data->name
            : (! empty($req['ownerName']) ? $req['ownerName']
            : (! empty($req['name']) ? $req['name']
            : '')));

        $organizationName = ! empty($data?->organizationName) ? $data->organizationName
            : (! empty($data?->organization) ? $data->organization
            : (! empty($req['organizationName']) ? $req['organizationName']
            : (! empty($req['organization']) ? $req['organization']
            : '')));

        $password = ! empty($data?->password) ? $data->password
            : (! empty($req['password']) ? $req['password']
            : '');

        $inputDto = new RegisterTenantInputDTO(
            organizationName: trim((string) $organizationName),
            ownerName: trim((string) $ownerName),
            ownerEmail: trim((string) $ownerEmail),
            password: (string) $password
        );

        $outputDto = $this->registerAction->execute($inputDto);

        $resource = new TenantResource;
        $resource->id = $outputDto->tenantId;
        $resource->organizationName = $outputDto->organizationName;
        $resource->slug = $outputDto->slug;
        $resource->ownerUserId = $outputDto->ownerUserId;
        $resource->ownerEmail = $outputDto->ownerEmail;
        $resource->email = $outputDto->ownerEmail;
        $resource->ownerName = $outputDto->ownerName;
        $resource->name = $outputDto->ownerName;
        $resource->status = 'ACTIVE';
        $resource->token = $outputDto->token;

        return $resource;
    }
}
