<?php

declare(strict_types=1);

namespace App\Domains\IAM\Resources;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\Domains\IAM\Processors\RegisterTenantStateProcessor;
use App\Domains\IAM\Providers\TenantStateProvider;

#[ApiResource(
    shortName: 'Tenant',
    operations: [
        new Post(
            uriTemplate: '/v1/tenants/register',
            processor: RegisterTenantStateProcessor::class,
            output: TenantResource::class
        ),
        new Get(
            uriTemplate: '/v1/tenants/{id}',
            provider: TenantStateProvider::class,
            middleware: ['auth:sanctum']
        ),
    ]
)]
class TenantResource
{
    public ?string $id = null;

    public ?string $organizationName = null;

    public ?string $slug = null;

    public ?string $ownerName = null;

    public ?string $ownerEmail = null;

    public ?string $password = null;

    public ?string $ownerUserId = null;

    public ?string $status = null;

    public ?string $token = null;
}
