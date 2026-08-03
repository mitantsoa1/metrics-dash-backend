<?php

namespace App\Console\Commands;

use App\Domains\Analytics\Services\MetricAggregationService;
use Illuminate\Console\Command;

class AggregateMetricsCommand extends Command
{
    protected $signature = 'metrics:aggregate {--date= : Date au format YYYY-MM-DD (défaut: aujourd\'hui)}';

    protected $description = 'Consolide les événements bruts (raw_events) vers les métriques quotidiennes (daily_metrics)';

    public function handle(MetricAggregationService $aggregationService): int
    {
        $date = $this->option('date');
        $this->info("Début de l'agrégation des métriques pour la date : " . ($date ?? 'aujourd\'hui'));

        $summary = $aggregationService->aggregateForDate($date);

        $this->info("Agrégation terminée avec succès !");
        $this->table(
            ['Paramètre', 'Valeur'],
            [
                ['Date ciblée', $summary['date']],
                ['Instances traitées', $summary['instances_processed']],
                ['Métriques enregistrées/mises à jour', $summary['metrics_upserted']],
            ]
        );

        return self::SUCCESS;
    }
}
