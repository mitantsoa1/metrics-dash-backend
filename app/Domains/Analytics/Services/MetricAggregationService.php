<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Services;

use App\Domains\Analytics\Infrastructure\Models\DailyMetricModel;
use App\Domains\Ingestion\Infrastructure\Models\RawEventModel;
use App\Domains\Instance\Infrastructure\Models\InstanceModel;
use Illuminate\Support\Str;

class MetricAggregationService
{
    /**
     * Exécute l'agrégation des événements bruts vers les métriques quotidiennes consolidées.
     *
     * @return array<string, mixed>
     */
    public function aggregateForDate(?string $dateString = null): array
    {
        $targetDate = $dateString ?? date('Y-m-d');
        $instances = InstanceModel::all();
        $summary = [
            'date' => $targetDate,
            'instances_processed' => 0,
            'metrics_upserted' => 0,
        ];

        foreach ($instances as $instance) {
            $instanceId = $instance->id;

            // 1. Nombre total de vues de page (page_views)
            $pageViews = RawEventModel::where('instance_id', $instanceId)
                ->whereDate('occurred_at', $targetDate)
                ->where('event_name', 'page_view')
                ->count();

            // 2. Visiteurs uniques (unique_visitors)
            $uniqueVisitors = RawEventModel::where('instance_id', $instanceId)
                ->whereDate('occurred_at', $targetDate)
                ->whereNotNull('visitor_id')
                ->distinct('visitor_id')
                ->count('visitor_id');

            // 3. Sessions (sessions)
            $sessions = RawEventModel::where('instance_id', $instanceId)
                ->whereDate('occurred_at', $targetDate)
                ->whereNotNull('session_id')
                ->distinct('session_id')
                ->count('session_id');

            // 4. Conversions métier (conversions)
            $conversions = RawEventModel::where('instance_id', $instanceId)
                ->whereDate('occurred_at', $targetDate)
                ->where('event_type', 'BUSINESS')
                ->count();

            $metricsToSave = [
                'page_views' => $pageViews,
                'unique_visitors' => $uniqueVisitors,
                'sessions' => $sessions,
                'conversions' => $conversions,
            ];

            foreach ($metricsToSave as $metricName => $value) {
                DailyMetricModel::updateOrCreate(
                    [
                        'instance_id' => $instanceId,
                        'date' => $targetDate,
                        'metric_name' => $metricName,
                    ],
                    [
                        'id' => (string) Str::uuid(),
                        'metric_value' => $value,
                    ]
                );
                $summary['metrics_upserted']++;
            }

            $summary['instances_processed']++;
        }

        return $summary;
    }
}
