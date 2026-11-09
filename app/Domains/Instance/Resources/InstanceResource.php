<?php

declare(strict_types=1);

namespace App\Domains\Instance\Resources;

use ApiPlatform\Metadata\ApiProperty;
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
    description: 'Gestion des instances (Sites/Applications) et clés d\'accès SDK/Push',
    operations: [
        new Post(
            uriTemplate: '/api/v1/instances',
            middleware: ['auth:sanctum'],
            processor: CreateInstanceStateProcessor::class,
            description: 'Crée une nouvelle instance client et génère les paires de clés SDK et Push.'
        ),
        new GetCollection(
            uriTemplate: '/api/v1/instances',
            middleware: ['auth:sanctum'],
            provider: InstanceCollectionStateProvider::class,
            description: 'Liste l\'ensemble des instances configurées sur la plateforme (accès Superadmin/Admin).'
        ),
        new GetCollection(
            uriTemplate: '/api/v1/tenants/{tenantId}/instances',
            middleware: ['auth:sanctum'],
            provider: InstanceCollectionStateProvider::class,
            description: 'Liste toutes les instances configurées pour une organisation.'
        ),
        new Get(
            uriTemplate: '/api/v1/instances/{id}',
            middleware: ['auth:sanctum'],
            provider: InstanceStateProvider::class,
            description: 'Récupère les détails et l\'état d\'une instance spécifique.'
        ),
        new \ApiPlatform\Metadata\Patch(
            uriTemplate: '/api/v1/instances/{id}',
            middleware: ['auth:sanctum'],
            read: false,
            inputFormats: ['json' => ['application/json', 'application/merge-patch+json']],
            processor: \App\Domains\Instance\Processors\UpdateInstanceStateProcessor::class,
            description: 'Modifie le nom, le domaine FQDN ou l\'environnement d\'une instance.'
        ),
        new \ApiPlatform\Metadata\Put(
            uriTemplate: '/api/v1/instances/{id}',
            middleware: ['auth:sanctum'],
            read: false,
            processor: \App\Domains\Instance\Processors\UpdateInstanceStateProcessor::class,
            description: 'Met à jour le nom, le domaine FQDN ou l\'environnement d\'une instance.'
        ),
        new \ApiPlatform\Metadata\Delete(
            uriTemplate: '/api/v1/instances/{id}',
            middleware: ['auth:sanctum'],
            read: false,
            processor: \App\Domains\Instance\Processors\DeleteInstanceStateProcessor::class,
            description: 'Supprime définitivement une instance et révoque ses clés associées.'
        ),

        new Post(
            uriTemplate: '/api/v1/instances/{id}/rotate-keys',
            middleware: ['auth:sanctum'],
            processor: RotateApiKeysProcessor::class,
            description: 'Révoque immédiatement une clé d\'API (SDK ou Push) et génère une nouvelle clé brute.'
        ),
    ]
)]

class InstanceResource
{
    /** La clé privée n'est jamais renvoyée en clair après sa création/rotation. */
    public static function maskSecret(?string $key): string
    {
        return ($key === null || strlen($key) < 12) ? 'sk_live_••••' : substr($key, 0, 8).'••••••••'.substr($key, -4);
    }

    #[ApiProperty(description: 'Identifiant UUID unique de l\'instance')]
    public ?string $id = null;

    #[ApiProperty(description: 'Identifiant UUID de l\'organisation propriétaire')]
    public ?string $tenantId = null;

    #[ApiProperty(description: 'Nom d\'affichage de l\'instance (ex: Application Web E-Commerce)')]
    public ?string $name = null;

    #[ApiProperty(description: 'Nom de domaine FQDN valide (ex: shop.client.com)')]
    public ?string $domain = null;

    #[ApiProperty(description: 'Environnement d\'exécution (PRODUCTION, STAGING, DEVELOPMENT)')]
    public ?string $environment = null;

    #[ApiProperty(description: 'Statut de l\'instance (ACTIVE, DISABLED)')]
    public ?string $status = null;

    #[ApiProperty(description: 'Clé publique SDK en clair (pk_live_... - affichée uniquement à la création)')]
    public ?string $publicSdkKey = null;

    #[ApiProperty(description: 'Clé privée Push en clair (sk_live_... - affichée uniquement à la création)')]
    public ?string $privatePushKey = null;

    #[ApiProperty(description: 'Type de clé à révoquer/régénérer (PUBLIC_SDK ou PRIVATE_PUSH)')]
    public ?string $rotatedKeyType = null;

    #[ApiProperty(description: 'Nouvelle clé brute régénérée lors de la rotation')]
    public ?string $newPlainKey = null;

    #[ApiProperty(description: 'Date de création de l\'instance')]
    public ?string $createdAt = null;
}
