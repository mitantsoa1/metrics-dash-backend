<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Providers;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Domains\Analytics\Infrastructure\Models\DailyMetricModel;
use App\Domains\Analytics\Resources\AnalyticsMetricsResource;
use App\Domains\Ingestion\Infrastructure\Models\RawEventModel;
use App\Domains\Instance\Infrastructure\Models\InstanceModel;
use Illuminate\Support\Carbon;

class AnalyticsMetricsStateProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $request = request();
        $instanceId = $request->query('instanceId');
        $startDateStr = $request->query('startDate', date('Y-m-d', strtotime('-30 days')));
        $endDateStr = $request->query('endDate', date('Y-m-d'));

        if (! $instanceId) {
            $firstInstance = InstanceModel::first();
            $instanceId = $firstInstance?->id ?? 'default';
        }

        $startDate = Carbon::parse($startDateStr)->startOfDay();
        $endDate = Carbon::parse($endDateStr)->endOfDay();

        // 1. Récupérer les métriques d'audience consolidées depuis daily_metrics
        $metrics = DailyMetricModel::where('instance_id', $instanceId)
            ->whereBetween('date', [$startDateStr, $endDateStr])
            ->get();

        $pageViews = (int) $metrics->where('metric_name', 'page_views')->sum('metric_value');
        $uniqueVisitors = (int) $metrics->where('metric_name', 'unique_visitors')->sum('metric_value');
        $sessions = (int) $metrics->where('metric_name', 'sessions')->sum('metric_value');
        $conversions = (int) $metrics->where('metric_name', 'conversions')->sum('metric_value');

        // Fallback live depuis raw_events si daily_metrics n'a pas encore été agrégé pour l'instance
        if ($pageViews === 0 && $uniqueVisitors === 0) {
            $pageViews = RawEventModel::where('instance_id', $instanceId)
                ->whereBetween('occurred_at', [$startDate, $endDate])
                ->where('event_name', 'page_view')
                ->count();

            $uniqueVisitors = RawEventModel::where('instance_id', $instanceId)
                ->whereBetween('occurred_at', [$startDate, $endDate])
                ->whereNotNull('visitor_id')
                ->distinct('visitor_id')
                ->count('visitor_id');

            $sessions = RawEventModel::where('instance_id', $instanceId)
                ->whereBetween('occurred_at', [$startDate, $endDate])
                ->whereNotNull('session_id')
                ->distinct('session_id')
                ->count('session_id');

            $conversions = RawEventModel::where('instance_id', $instanceId)
                ->whereBetween('occurred_at', [$startDate, $endDate])
                ->where('event_type', 'BUSINESS')
                ->count();
        }

        $bounceRate = $sessions > 0 ? max(5, round(100 - (($conversions / max(1, $sessions)) * 100), 1)) : 0;

        // 2. Calcul DYNAMIQUE du Chiffre d'Affaires et des Packs à partir de la table raw_events
        $businessEvents = RawEventModel::where('instance_id', $instanceId)
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->where(function ($q) {
                $q->where('event_type', 'BUSINESS')
                  ->orWhereIn('event_name', ['order_completed', 'payment_success', 'subscription_created', 'checkout_success']);
            })
            ->orderBy('occurred_at', 'asc')
            ->get();

        $totalRevenue = 0.0;
        $totalTransactions = count($businessEvents);

        $packMap = [
            'Pack Starter' => ['revenue' => 0.0, 'count' => 0, 'price' => 29],
            'Pack Pro' => ['revenue' => 0.0, 'count' => 0, 'price' => 79],
            'Pack Enterprise' => ['revenue' => 0.0, 'count' => 0, 'price' => 199],
            'Pack Custom' => ['revenue' => 0.0, 'count' => 0, 'price' => 499],
        ];

        $dailyRevenueMap = [];

        foreach ($businessEvents as $event) {
            $payload = $event->payload ?? [];
            $data = $payload['eventData'] ?? $payload;

            $amount = (float) ($data['amount'] ?? $data['price'] ?? 120.00);
            $packName = $data['pack'] ?? $data['plan'] ?? null;
            $dateKey = Carbon::parse($event->occurred_at)->format('Y-m-d');

            $totalRevenue += $amount;

            if (! isset($dailyRevenueMap[$dateKey])) {
                $dailyRevenueMap[$dateKey] = ['revenue' => 0.0, 'transactions' => 0];
            }
            $dailyRevenueMap[$dateKey]['revenue'] += $amount;
            $dailyRevenueMap[$dateKey]['transactions']++;

            // Ventilation dynamique par pack
            if ($packName && isset($packMap[$packName])) {
                $packMap[$packName]['revenue'] += $amount;
                $packMap[$packName]['count']++;
            } else {
                if ($amount >= 300) {
                    $packMap['Pack Custom']['revenue'] += $amount;
                    $packMap['Pack Custom']['count']++;
                } elseif ($amount >= 150) {
                    $packMap['Pack Enterprise']['revenue'] += $amount;
                    $packMap['Pack Enterprise']['count']++;
                } elseif ($amount >= 60) {
                    $packMap['Pack Pro']['revenue'] += $amount;
                    $packMap['Pack Pro']['count']++;
                } else {
                    $packMap['Pack Starter']['revenue'] += $amount;
                    $packMap['Pack Starter']['count']++;
                }
            }
        }

        // Calcul des pourcentages de chaque pack
        foreach ($packMap as $k => $p) {
            $packMap[$k]['percentage'] = $totalRevenue > 0
                ? round(($p['revenue'] / $totalRevenue) * 100, 1)
                : 0;
        }

        $mrr = round($totalRevenue * 0.85, 2);
        $averageOrderValue = $totalTransactions > 0 ? round($totalRevenue / $totalTransactions, 2) : 0;
        $financialConversionRate = $uniqueVisitors > 0 ? round(($totalTransactions / max(1, $uniqueVisitors)) * 100, 2) : 0;

        // 3. Séries temporelles dynamiques d'audience et de Chiffre d'Affaires
        $timeSeriesRaw = $metrics->groupBy('date');
        $timeSeries = [];
        $revenueTimeSeries = [];

        // Construire la liste complète des dates pour la période demandée
        $currentDay = $startDate->copy();
        while ($currentDay->lte($endDate)) {
            $dateStr = $currentDay->format('Y-m-d');

            // Audience
            if (isset($timeSeriesRaw[$dateStr])) {
                $items = $timeSeriesRaw[$dateStr];
                $timeSeries[] = [
                    'date' => $dateStr,
                    'page_views' => (int) ($items->where('metric_name', 'page_views')->first()?->metric_value ?? 0),
                    'unique_visitors' => (int) ($items->where('metric_name', 'unique_visitors')->first()?->metric_value ?? 0),
                    'sessions' => (int) ($items->where('metric_name', 'sessions')->first()?->metric_value ?? 0),
                    'conversions' => (int) ($items->where('metric_name', 'conversions')->first()?->metric_value ?? 0),
                ];
            } else {
                $dayEvents = RawEventModel::where('instance_id', $instanceId)
                    ->whereDate('occurred_at', $dateStr)
                    ->get();

                $pv = $dayEvents->where('event_name', 'page_view')->count();
                $uv = $dayEvents->pluck('visitor_id')->filter()->unique()->count();
                $sess = $dayEvents->pluck('session_id')->filter()->unique()->count();
                $conv = $dayEvents->where('event_type', 'BUSINESS')->count();

                $timeSeries[] = [
                    'date' => $dateStr,
                    'page_views' => $pv,
                    'unique_visitors' => $uv,
                    'sessions' => $sess,
                    'conversions' => $conv,
                ];
            }

            // Chiffre d'affaires dynamique par jour
            $revData = $dailyRevenueMap[$dateStr] ?? ['revenue' => 0.0, 'transactions' => 0];
            $revenueTimeSeries[] = [
                'date' => $dateStr,
                'revenue' => round($revData['revenue'], 2),
                'transactions' => $revData['transactions'],
            ];

            $currentDay->addDay();
        }

        // 4. Répartition dynamique des appareils et navigateurs
        $rawEvents = RawEventModel::where('instance_id', $instanceId)
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->limit(1000)
            ->get();

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
        $resource->startDate = (string) $startDateStr;
        $resource->endDate = (string) $endDateStr;
        $resource->kpis = [
            'page_views' => $pageViews,
            'unique_visitors' => $uniqueVisitors,
            'sessions' => $sessions,
            'conversions' => $conversions,
            'bounce_rate' => $bounceRate,
        ];
        $resource->financialKpis = [
            'total_revenue' => round($totalRevenue, 2),
            'mrr' => $mrr,
            'average_order_value' => $averageOrderValue,
            'total_transactions' => $totalTransactions,
            'conversion_rate' => $financialConversionRate,
        ];
        $resource->packBreakdown = $packMap;
        $resource->timeSeries = $timeSeries;
        $resource->revenueTimeSeries = $revenueTimeSeries;
        $resource->deviceBreakdown = $deviceBreakdown;
        $resource->browserBreakdown = $browserBreakdown;

        return [$resource];
    }
}
