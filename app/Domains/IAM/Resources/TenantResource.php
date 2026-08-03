<?php

declare(strict_types=1);

namespace App\Domains\IAM\Resources;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\Domains\IAM\Processors\RegisterTenantStateProcessor;
use App\Domains\IAM\Providers\TenantStateProvider;

#[ApiResource(
    shortName: 'Tenant',
    description: 'Gestion des organisations et inscription multi-tenant enterprise',
    operations: [
        new Post(
            uriTemplate: '/api/v1/tenants/register',
            processor: RegisterTenantStateProcessor::class,
            output: TenantResource::class,
            description: 'Inscrit une nouvelle organisation client et son utilisateur administrateur principal (Owner).'
        ),
        new Get(
            uriTemplate: '/api/v1/tenants/{id}',
            provider: TenantStateProvider::class,
            middleware: ['auth:sanctum'],
            description: 'Récupère les détails d\'une organisation par son identifiant unique.'
        ),
    ]
)]
class TenantResource
{
    #[ApiProperty(description: 'Identifiant UUID unique du Tenant')]
    public ?string $id = null;

    #[ApiProperty(description: 'Nom officiel de l\'organisation entreprise')]
    public ?string $organizationName = null;

    #[ApiProperty(description: 'Slug URL unique généré automatiquement')]
    public ?string $slug = null;

    #[ApiProperty(description: 'Nom complet du propriétaire/administrateur')]
    public ?string $ownerName = null;

    #[ApiProperty(description: 'Adresse e-mail professionnelle du propriétaire')]
    public ?string $ownerEmail = null;

    #[ApiProperty(description: 'Nom du compte')]
    public ?string $name = null;

    #[ApiProperty(description: 'E-mail de connexion')]
    public ?string $email = null;

    #[ApiProperty(description: 'Mot de passe sécurisé (minimum 8 caractères)')]
    public ?string $password = null;

    #[ApiProperty(description: 'UUID du compte utilisateur propriétaire')]
    public ?string $ownerUserId = null;

    #[ApiProperty(description: 'Statut du tenant (ACTIVE, SUSPENDED, CANCELLED)')]
    public ?string $status = null;

    #[ApiProperty(description: 'Jeton d\'authentification Sanctum Bearer généré')]
    public ?string $token = null;
}
