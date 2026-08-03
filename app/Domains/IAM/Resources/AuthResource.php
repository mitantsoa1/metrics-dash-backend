<?php

declare(strict_types=1);

namespace App\Domains\IAM\Resources;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Domains\IAM\Processors\LoginStateProcessor;

#[ApiResource(
    shortName: 'Auth',
    description: 'Authentification JWT et gestion des jetons Sanctum Bearer',
    operations: [
        new Post(
            uriTemplate: '/api/v1/auth/login',
            processor: LoginStateProcessor::class,
            description: 'Authentifie un utilisateur et retourne un jeton d\'accès Sanctum Bearer.'
        ),
    ]
)]
class AuthResource
{
    #[ApiProperty(description: 'Adresse email de connexion')]
    public ?string $email = null;

    #[ApiProperty(description: 'Mot de passe utilisateur')]
    public ?string $password = null;

    #[ApiProperty(description: 'Jeton d\'accès Bearer Sanctum généré')]
    public ?string $token = null;

    #[ApiProperty(description: 'Type de jeton (Bearer)')]
    public ?string $tokenType = null;

    #[ApiProperty(description: 'Identifiant UUID de l\'utilisateur authentifié')]
    public ?string $userId = null;

    #[ApiProperty(description: 'Identifiant UUID de l\'organisation rattachée')]
    public ?string $tenantId = null;

    #[ApiProperty(description: 'Rôle RBAC attribué à l\'utilisateur (OWNER, ADMIN, ANALYST, VIEWER)')]
    public ?string $role = null;
}
