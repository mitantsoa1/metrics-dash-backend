<?php

declare(strict_types=1);

namespace App\Domains\Instance\Resources;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Domains\Instance\Processors\CreateInstanceStateProcessor;
use App\Domains\Instance\Processors\RotateApiKeysProcessor;
use App\Domains\Instance\Providers\InstanceCollectionStateProvider;
use App\Domains\Instance\Providers\InstanceStateProvider;

#[ApiResource(
    shortName: 'Instance',
    operations: [
        new Post(
            uriTemplate: '/v1/instances',
            middleware: ['auth:sanctum'],
            processor: CreateInstanceStateProcessor::class
        ),
        new GetCollection(
            uriTemplate: '/v1/tenants/{tenantId}/instances',
            middleware: ['auth:sanctum'],
            provider: InstanceCollectionStateProvider::class
        ),
        new Get(
            uriTemplate: '/v1/instances/{id}',
            middleware: ['auth:sanctum'],
            provider: InstanceStateProvider::class
        ),
        new Post(
            uriTemplate: '/v1/instances/{id}/rotate-keys',
            middleware: ['auth:sanctum'],
            processor: RotateApiKeysProcessor::class
        ),
    ]
)]
class InstanceResource
{
    public ?string $id = null;

    public ?string $tenantId = null;

    public ?string $name = null;

    public ?string $domain = null;

    public ?string $environment = null;

    public ?string $status = null;

    public ?string $publicSdkKey = null;

    public ?string $privatePushKey = null;

    public ?string $rotatedKeyType = null;

    public ?string $newPlainKey = null;

    public ?string $createdAt = null;
}
