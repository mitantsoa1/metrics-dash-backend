<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Resources;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Domains\Analytics\Providers\AnalyticsMetricsStateProvider;

#[ApiResource(
    shortName: 'Metrics',
    description: 'Consultation des métriques d\'audience et KPIs analytiques agrégés',
    operations: [
        new GetCollection(
            uriTemplate: '/api/v1/metrics',
            provider: AnalyticsMetricsStateProvider::class,
            description: 'Récupère les KPIs consolidés (Visiteurs uniques, Vues de page, Sessions, Conversions, Taux de rebond) et séries temporelles.'
        )
    ]
)]
class AnalyticsMetricsResource
{
    #[ApiProperty(description: 'Identifiant UUID de l\'instance analysée')]
    public ?string $instanceId = null;

    #[ApiProperty(description: 'Date de début du filtre (YYYY-MM-DD)')]
    public ?string $startDate = null;

    #[ApiProperty(description: 'Date de fin du filtre (YYYY-MM-DD)')]
    public ?string $endDate = null;

    #[ApiProperty(description: 'Totaux des KPIs (page_views, unique_visitors, sessions, conversions, bounce_rate)')]
    public array $kpis = [];

    #[ApiProperty(description: 'Séries temporelles quotidiennes sur la période sélectionnée')]
    public array $timeSeries = [];

    #[ApiProperty(description: 'Ventilation du trafic par type d\'appareil (DESKTOP, MOBILE, TABLET, BOT)')]
    public array $deviceBreakdown = [];

    #[ApiProperty(description: 'Ventilation du trafic par navigateur (Chrome, Safari, Firefox, Edge)')]
    public array $browserBreakdown = [];
}
