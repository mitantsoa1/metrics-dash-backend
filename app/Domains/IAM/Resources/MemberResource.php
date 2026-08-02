<?php

declare(strict_types=1);

namespace App\Domains\IAM\Resources;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Domains\IAM\Processors\AddMemberStateProcessor;
use App\Domains\IAM\Providers\MemberStateProvider;

#[ApiResource(
    shortName: 'Member',
    operations: [
        new Post(
            uriTemplate: '/v1/tenants/{tenantId}/members',
            middleware: ['auth:sanctum'],
            processor: AddMemberStateProcessor::class
        ),
        new GetCollection(
            uriTemplate: '/v1/tenants/{tenantId}/members',
            middleware: ['auth:sanctum'],
            provider: MemberStateProvider::class
        ),
    ]
)]
class MemberResource
{
    public ?string $userId = null;

    public ?string $tenantId = null;

    public ?string $name = null;

    public ?string $email = null;

    public ?string $password = null;

    public ?string $role = null;

    public ?string $createdAt = null;
}
