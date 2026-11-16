<?php

declare(strict_types=1);

namespace App\Domains\IAM\Resources;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Domains\IAM\Processors\AddMemberStateProcessor;
use App\Domains\IAM\Processors\DeleteMemberStateProcessor;
use App\Domains\IAM\Processors\UpdateMemberStateProcessor;
use App\Domains\IAM\Providers\MemberStateProvider;

#[ApiResource(
    shortName: 'Member',
    description: 'Gestion des membres/utilisateurs et contrôle d\'accès RBAC',
    operations: [
        new Post(
            uriTemplate: '/api/v1/tenants/{tenantId}/members',
            middleware: ['auth:sanctum'],
            processor: AddMemberStateProcessor::class,
            description: 'Ajoute un nouveau membre dans l\'organisation et lui attribue un rôle RBAC.'
        ),
        new GetCollection(
            uriTemplate: '/api/v1/members',
            middleware: ['auth:sanctum'],
            provider: MemberStateProvider::class,
            description: 'Liste l\'ensemble des utilisateurs inscrits (accès Superadmin/Admin).'
        ),
        new GetCollection(
            uriTemplate: '/api/v1/tenants/{tenantId}/members',
            middleware: ['auth:sanctum'],
            provider: MemberStateProvider::class,
            description: 'Liste tous les membres d\'une organisation avec leurs rôles d\'accès.'
        ),
        new Get(
            uriTemplate: '/api/v1/members/{userId}',
            middleware: ['auth:sanctum'],
            provider: MemberStateProvider::class,
            description: 'Récupère les détails d\'un utilisateur spécifique.'
        ),
        new Put(
            uriTemplate: '/api/v1/members/{userId}',
            middleware: ['auth:sanctum'],
            read: false,
            processor: UpdateMemberStateProcessor::class,
            description: 'Met à jour les informations d\'un utilisateur (nom, email, rôle, mot de passe).'
        ),
        new Patch(
            uriTemplate: '/api/v1/members/{userId}',
            middleware: ['auth:sanctum'],
            read: false,
            inputFormats: ['json' => ['application/json', 'application/merge-patch+json']],
            processor: UpdateMemberStateProcessor::class,
            description: 'Modifie partiellement un utilisateur.'
        ),
        new Delete(
            uriTemplate: '/api/v1/members/{userId}',
            middleware: ['auth:sanctum'],
            read: false,
            processor: DeleteMemberStateProcessor::class,
            description: 'Supprime un utilisateur.'
        ),
    ]
)]
class MemberResource
{
    #[ApiProperty(description: 'Identifiant UUID du membre')]
    public ?string $userId = null;

    #[ApiProperty(description: 'Identifiant UUID de l\'organisation rattachée')]
    public ?string $tenantId = null;

    #[ApiProperty(description: 'Nom complet du membre')]
    public ?string $name = null;

    #[ApiProperty(description: 'Adresse email du membre')]
    public ?string $email = null;

    #[ApiProperty(description: 'Mot de passe initial du membre (optionnel à la création)')]
    public ?string $password = null;

    #[ApiProperty(description: 'Rôle d\'accès RBAC (OWNER, ADMIN, ANALYST, VIEWER)')]
    public ?string $role = null;

    #[ApiProperty(description: 'Date de création de l\'invitation')]
    public ?string $createdAt = null;
}
