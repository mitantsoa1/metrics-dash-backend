<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Providers;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Domains\Analytics\Infrastructure\Models\DailyMetricModel;
use App\Domains\Analytics\Resources\AnalyticsMetricsResource;
use App\Domains\Ingestion\Infrastructure\Models\RawEventModel;
use App\Domains\Instance\Infrastructure\Models\InstanceModel;

class AnalyticsMetricsStateProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $request = request();
        $instanceId = $request->query('instanceId');
        $startDate = $request->query('startDate', date('Y-m-d', strtotime('-30 days')));
        $endDate = $request->query('endDate', date('Y-m-d'));

        if (! $instanceId) {
            $firstInstance = InstanceModel::first();
            $instanceId = $firstInstance?->id ?? 'default';
        }

        // 1. Récupérer les métriques quotidiennes agrégées
        $metrics = DailyMetricModel::where('instance_id', $instanceId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $pageViews = (int) $metrics->where('metric_name', 'page_views')->sum('metric_value');
        $uniqueVisitors = (int) $metrics->where('metric_name', 'unique_visitors')->sum('metric_value');
        $sessions = (int) $metrics->where('metric_name', 'sessions')->sum('metric_value');
        $conversions = (int) $metrics->where('metric_name', 'conversions')->sum('metric_value');

        // Fallback live si daily_metrics n'est pas encore rempli
        if ($pageViews === 0 && $uniqueVisitors === 0) {
            $pageViews = RawEventModel::where('instance_id', $instanceId)->where('event_name', 'page_view')->count();
            $uniqueVisitors = RawEventModel::where('instance_id', $instanceId)->whereNotNull('visitor_id')->distinct('visitor_id')->count('visitor_id');
            $sessions = RawEventModel::where('instance_id', $instanceId)->whereNotNull('session_id')->distinct('session_id')->count('session_id');
            $conversions = RawEventModel::where('instance_id', $instanceId)->where('event_type', 'BUSINESS')->count();
        }

        $bounceRate = $sessions > 0 ? max(5, round(100 - (($conversions / max(1, $sessions)) * 100), 1)) : 0;

        // 2. Séries temporelles
        $timeSeriesRaw = $metrics->groupBy('date');
        $timeSeries = [];

        foreach ($timeSeriesRaw as $dateStr => $items) {
            $timeSeries[] = [
                'date' => (string) $dateStr,
                'page_views' => (int) ($items->where('metric_name', 'page_views')->first()?->metric_value ?? 0),
                'unique_visitors' => (int) ($items->where('metric_name', 'unique_visitors')->first()?->metric_value ?? 0),
                'sessions' => (int) ($items->where('metric_name', 'sessions')->first()?->metric_value ?? 0),
                'conversions' => (int) ($items->where('metric_name', 'conversions')->first()?->metric_value ?? 0),
            ];
        }

        // 3. Répartition des appareils & navigateurs depuis raw_events context
        $rawEvents = RawEventModel::where('instance_id', $instanceId)->limit(1000)->get();
        $deviceBreakdown = ['DESKTOP' => 0, 'MOBILE' => 0, 'TABLET' => 0, 'BOT' => 0];
        $browserBreakdown = ['Chrome' => 0, 'Safari' => 0, 'Firefox' => 0, 'Edge' => 0];

        foreach ($rawEvents as $event) {
            $contextData = $event->context ?? [];
            $device = $contextData['device_type'] ?? 'DESKTOP';
            $browser = $contextData['browser'] ?? 'Chrome';

            if (isset($deviceBreakdown[$device])) {
                $deviceBreakdown[$device]++;
            } else {
                $deviceBreakdown['DESKTOP']++;
            }

            if (isset($browserBreakdown[$browser])) {
                $browserBreakdown[$browser]++;
            } else {
                $browserBreakdown['Chrome']++;
            }
        }

        $resource = new AnalyticsMetricsResource();
        $resource->instanceId = (string) $instanceId;
        $resource->startDate = (string) $startDate;
        $resource->endDate = (string) $endDate;
        $resource->kpis = [
            'page_views' => $pageViews,
            'unique_visitors' => $uniqueVisitors,
            'sessions' => $sessions,
            'conversions' => $conversions,
            'bounce_rate' => $bounceRate,
        ];
        $resource->timeSeries = $timeSeries;
        $resource->deviceBreakdown = $deviceBreakdown;
        $resource->browserBreakdown = $browserBreakdown;

        return [$resource];
    }
}
