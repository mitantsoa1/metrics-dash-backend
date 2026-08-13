<?php

declare(strict_types=1);

namespace App\Domains\Ingestion\Resources;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Domains\Ingestion\Processors\IngestPushEventProcessor;
use App\Domains\Ingestion\Processors\IngestSdkEventProcessor;

#[ApiResource(
    shortName: 'Ingestion',
    description: 'Endpoints haute performance pour la collecte de télémétrie Web et événements métier',
    operations: [
        new Post(
            uriTemplate: '/api/v1/telemetry/sdk',
            processor: IngestSdkEventProcessor::class,
            inputFormats: [
                'json' => ['application/json', 'text/plain', 'text/plain;charset=UTF-8', 'application/x-www-form-urlencoded', 'multipart/form-data'],
            ],
            description: 'Ingère un événement de navigation Web depuis le SDK client (avec clé pk_live_...).'
        ),
        new Post(
            uriTemplate: '/api/v1/collect/sdk',
            processor: IngestSdkEventProcessor::class,
            inputFormats: [
                'json' => ['application/json', 'text/plain', 'text/plain;charset=UTF-8', 'application/x-www-form-urlencoded', 'multipart/form-data'],
            ],
            description: 'Alias d\'ingestion SDK client Web.'
        ),
        new Post(
            uriTemplate: '/api/v1/telemetry/push',
            processor: IngestPushEventProcessor::class,
            description: 'Ingère un événement métier serveur-à-serveur depuis une API backend (avec clé sk_live_...).'
        ),
        new Post(
            uriTemplate: '/api/v1/collect/push',
            processor: IngestPushEventProcessor::class,
            description: 'Alias d\'ingestion API Push privée.'
        ),
    ]
)]
class IngestionResource
{
    #[ApiProperty(description: 'Identifiant UUID de l\'événement généré')]
    public ?string $eventId = null;

    #[ApiProperty(description: 'Identifiant UUID de l\'instance associée')]
    public ?string $instanceId = null;

    #[ApiProperty(description: 'Nom de l\'événement en format snake_case (ex: page_view, order_completed)')]
    public ?string $eventName = null;

    #[ApiProperty(description: 'Clé d\'API publique SDK (pk_live_...)')]
    public ?string $publicSdkKey = null;

    #[ApiProperty(description: 'Clé d\'API privée Push (sk_live_...)')]
    public ?string $privatePushKey = null;

    #[ApiProperty(description: 'Identifiant visiteur anonyme client')]
    public ?string $visitorId = null;

    #[ApiProperty(description: 'Identifiant de la session de navigation')]
    public ?string $sessionId = null;

    #[ApiProperty(description: 'Métadonnées contextuelles de la requête (URL, titre, etc.)')]
    public ?array $payload = null;

    #[ApiProperty(description: 'Contexte enrichi (IP anonymisée, type d\'appareil, OS, navigateur)')]
    public ?array $context = null;

    #[ApiProperty(description: 'Données métier spécifiques à l\'événement Push (commandes, montants)')]
    public ?array $eventData = null;

    #[ApiProperty(description: 'Statut du traitement (ACCEPTED)')]
    public ?string $status = null;

    #[ApiProperty(description: 'Date et heure ISO de survenance de l\'événement')]
    public ?string $occurredAt = null;
}
