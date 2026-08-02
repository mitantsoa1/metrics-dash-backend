<?php

declare(strict_types=1);

namespace App\Domains\Instance\Processors;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domains\Instance\Actions\CreateInstanceAction;
use App\Domains\Instance\DTOs\CreateInstanceInputDTO;
use App\Domains\Instance\Resources\InstanceResource;

class CreateInstanceStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly CreateInstanceAction $createInstanceAction
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): InstanceResource
    {
        $req = request()?->json()?->all() ?? request()?->all() ?? [];
        $request = $context['request'] ?? request();
        $user = $request?->user() ?? auth()->user();

        $tenantId = ! empty($data?->tenantId) ? $data->tenantId
            : (! empty($req['tenantId']) ? $req['tenantId']
            : ($user?->tenant_id ?? ''));

        $name = ! empty($data?->name) ? $data->name : (! empty($req['name']) ? $req['name'] : '');
        $domain = ! empty($data?->domain) ? $data->domain : (! empty($req['domain']) ? $req['domain'] : '');
        $environment = ! empty($data?->environment) ? $data->environment : (! empty($req['environment']) ? $req['environment'] : 'PRODUCTION');

        $inputDto = new CreateInstanceInputDTO(
            tenantId: (string) $tenantId,
            name: trim((string) $name),
            domain: trim((string) $domain),
            environment: (string) $environment
        );

        $outputDto = $this->createInstanceAction->execute($inputDto);

        $resource = new InstanceResource;
        $resource->id = $outputDto->id;
        $resource->tenantId = $outputDto->tenantId;
        $resource->name = $outputDto->name;
        $resource->domain = $outputDto->domain;
        $resource->environment = $outputDto->environment;
        $resource->status = $outputDto->status;
        $resource->publicSdkKey = $outputDto->apiKeys->publicSdkKey;
        $resource->privatePushKey = $outputDto->apiKeys->privatePushKey;
        $resource->createdAt = $outputDto->createdAt;

        return $resource;
    }
}
