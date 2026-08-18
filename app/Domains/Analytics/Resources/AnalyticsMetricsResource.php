<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Resources;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Domains\Analytics\Providers\AnalyticsMetricsStateProvider;

#[ApiResource(
    shortName: 'Metrics',
    description: 'Consultation des métriques financières (Chiffre d\'Affaires, Paiements par Packs) et KPIs d\'audience',
    operations: [
        new GetCollection(
            uriTemplate: '/api/v1/metrics',
            provider: AnalyticsMetricsStateProvider::class,
            description: 'Récupère les KPIs financiers (Chiffre d\'Affaires, MRR, Panier Moyen, Paiements par Packs) et les séries temporelles.'
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

    #[ApiProperty(description: 'KPIs d\'audience de base (page_views, unique_visitors, sessions, conversions, bounce_rate)')]
    public array $kpis = [];

    #[ApiProperty(description: 'KPIs financiers et chiffre d\'affaires (total_revenue, mrr, average_order_value, total_transactions, conversion_rate)')]
    public array $financialKpis = [];

    #[ApiProperty(description: 'Répartition du chiffre d\'affaires et transactions par Packs (Starter, Pro, Enterprise, Custom)')]
    public array $packBreakdown = [];

    #[ApiProperty(description: 'Séries temporelles d\'audience quotidiennes sur la période')]
    public array $timeSeries = [];

    #[ApiProperty(description: 'Séries temporelles du Chiffre d\'Affaires quotidien en Euros')]
    public array $revenueTimeSeries = [];

    #[ApiProperty(description: 'Ventilation du trafic par type d\'appareil (DESKTOP, MOBILE, TABLET, BOT)')]
    public array $deviceBreakdown = [];

    #[ApiProperty(description: 'Ventilation du trafic par navigateur (Chrome, Safari, Firefox, Edge)')]
    public array $browserBreakdown = [];

    #[ApiProperty(description: 'Dernières transactions enregistrées avec détails acheteurs et packs')]
    public array $recentTransactions = [];

    #[ApiProperty(description: 'Pages les plus visitées avec nombre de vues et durée moyenne')]
    public array $topPages = [];

    #[ApiProperty(description: 'Éléments les plus cliqués avec sélecteur CSS, libellé et nombre de clics')]
    public array $topClicks = [];

    #[ApiProperty(description: 'Formulaires les plus soumis avec identifiant, nom, chemin et nombre de soumissions')]
    public array $topForms = [];

    #[ApiProperty(description: 'Journal en temps réel des derniers événements bruts enregistrés (raw_events)')]
    public array $recentRawEvents = [];
}


