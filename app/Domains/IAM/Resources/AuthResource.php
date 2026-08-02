<?php

declare(strict_types=1);

namespace App\Domains\IAM\Resources;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Domains\IAM\Processors\LoginStateProcessor;

#[ApiResource(
    shortName: 'Auth',
    operations: [
        new Post(
            uriTemplate: '/v1/auth/login',
            processor: LoginStateProcessor::class
        ),
    ]
)]
class AuthResource
{
    public ?string $email = null;

    public ?string $password = null;

    public ?string $token = null;

    public ?string $tokenType = null;

    public ?string $userId = null;

    public ?string $tenantId = null;

    public ?string $role = null;
}
