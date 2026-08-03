<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Resources;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Domains\Analytics\Processors\ReportExportStateProcessor;

#[ApiResource(
    shortName: 'ReportExport',
    description: 'Exportation des métriques et rapports de performance',
    operations: [
        new Post(
            uriTemplate: '/api/v1/reports/export',
            processor: ReportExportStateProcessor::class,
            description: 'Génère un fichier d\'exportation des données de métriques (CSV/JSON) pour la période demandée.'
        )
    ]
)]
class ReportExportResource
{
    #[ApiProperty(description: 'Identifiant UUID de l\'instance à exporter')]
    public ?string $instanceId = null;

    #[ApiProperty(description: 'Format du fichier généré (csv ou json)')]
    public ?string $format = 'csv';

    #[ApiProperty(description: 'Date de début (YYYY-MM-DD)')]
    public ?string $startDate = null;

    #[ApiProperty(description: 'Date de fin (YYYY-MM-DD)')]
    public ?string $endDate = null;

    #[ApiProperty(description: 'URL de téléchargement direct du fichier généré')]
    public ?string $downloadUrl = null;

    #[ApiProperty(description: 'Nombre d\'enregistrements exportés')]
    public ?int $exportedRecords = null;
}
