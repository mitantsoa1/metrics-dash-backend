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
use Illuminate\Support\Facades\Http;

use App\Domains\Analytics\Services\EventFilterService;
use App\Domains\Analytics\Services\KpiSummaryService;
use App\Domains\IAM\Enums\UserRole;

class AnalyticsMetricsStateProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $request = request();
        $user = auth('api')->user();
        $instanceId = $request->query('instanceId');
        $startDateStr = $request->query('startDate', date('Y-m-d', strtotime('-30 days')));
        $endDateStr = $request->query('endDate', date('Y-m-d'));

        $isSuperAdminOrAdmin = $user && in_array($user->role, [UserRole::SUPERADMIN->value, UserRole::ADMIN->value], true);

        // Si une instance spécifique est demandée, vérifier l'existence et la sécurité tenant
        if ($instanceId && $instanceId !== 'all') {
            $instance = InstanceModel::find($instanceId);
            if (! $instance) {
                return [];
            }

            // Sécurité et isolation Multi-Tenant (si non superadmin/admin)
            if (! $isSuperAdminOrAdmin) {
                $tenantHeader = $request->header('X-Tenant-Id') ?? $user?->tenant_id;
                if ($tenantHeader && $instance->tenant_id !== $tenantHeader) {
                    return [];
                }
            }
        }

        // Closure d'application du filtre d'instance selon le rôle et le paramètre instanceId
        $applyInstanceFilter = function ($query) use ($instanceId, $isSuperAdminOrAdmin, $user) {
            if ($instanceId && $instanceId !== 'all') {
                $query->where('instance_id', $instanceId);
            } elseif (! $isSuperAdminOrAdmin && $user?->tenant_id) {
                $tenantInstanceIds = InstanceModel::where('tenant_id', $user->tenant_id)->pluck('id')->all();
                $query->whereIn('instance_id', $tenantInstanceIds);
            }
        };
        // Segmentation (PU-37/38/39) : les filtres s'appliquent à tous les calculs sur raw_events
        $filterService = app(EventFilterService::class);
        $filters = $filterService->fromRequest($request);
        $filtersActive = $filterService->isActive($filters);
        $rawScope = function ($query) use ($applyInstanceFilter, $filterService, $filters) {
            $applyInstanceFilter($query);
            $filterService->apply($query, $filters);
        };

        $remoteData = null;
        if ($instance && ! empty($instance->domain) && ! $filtersActive) {
            $domain = rtrim((string) $instance->domain, '/');
            if (! str_starts_with($domain, 'http')) {
                $domain = 'http://' . $domain;
            }
            try {
                $response = Http::timeout(3)->post("{$domain}/metrics-dash/metrics", [
                    'startDate' => $startDateStr,
                    'endDate' => $endDateStr,
                ]);

                if (! $response->successful()) {
                    $response = Http::timeout(3)->post("{$domain}/metrics-dash/api/metrics", [
                        'startDate' => $startDateStr,
                        'endDate' => $endDateStr,
                    ]);
                }

                if ($response->successful()) {
                    $remoteData = $response->json();
                }
            } catch (\Throwable $e) {
                // En cas d'instance injoignable, basculer sur le calcul local
            }
        }

        $startDate = Carbon::parse($startDateStr)->startOfDay();
        $endDate = Carbon::parse($endDateStr)->endOfDay();



        // 1. Récupérer les métriques d'audience consolidées depuis daily_metrics
        // daily_metrics n'est pas segmentable : avec un filtre actif, on lit raw_events
        $metrics = $filtersActive
            ? collect()
            : DailyMetricModel::where($applyInstanceFilter)
                ->whereBetween('date', [$startDateStr, $endDateStr])
                ->get();

        $pageViews = (int) $metrics->where('metric_name', 'page_views')->sum('metric_value');
        $uniqueVisitors = (int) $metrics->where('metric_name', 'unique_visitors')->sum('metric_value');
        $sessions = (int) $metrics->where('metric_name', 'sessions')->sum('metric_value');
        $conversions = (int) $metrics->where('metric_name', 'conversions')->sum('metric_value');

        // Fallback live depuis raw_events si daily_metrics n'a pas encore été agrégé pour l'instance
        if ($pageViews === 0 && $uniqueVisitors === 0) {
            $pageViews = RawEventModel::where($rawScope)
                ->whereBetween('occurred_at', [$startDate, $endDate])
                ->where('event_name', 'page_view')
                ->count();

            $uniqueVisitors = RawEventModel::where($rawScope)
                ->whereBetween('occurred_at', [$startDate, $endDate])
                ->whereNotNull('visitor_id')
                ->distinct('visitor_id')
                ->count('visitor_id');

            $sessions = RawEventModel::where($rawScope)
                ->whereBetween('occurred_at', [$startDate, $endDate])
                ->whereNotNull('session_id')
                ->distinct('session_id')
                ->count('session_id');

            $conversions = RawEventModel::where($rawScope)
                ->whereBetween('occurred_at', [$startDate, $endDate])
                ->where('event_type', 'BUSINESS')
                ->count();
        }

        $bounceRate = 0;

        // 2. Calcul DYNAMIQUE du Chiffre d'Affaires et des Packs à partir de la table raw_events
        $businessEvents = RawEventModel::where($rawScope)
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->where(function ($q) {
                $q->where('event_type', 'BUSINESS')
                    ->orWhereIn('event_name', ['order_completed', 'payment_success', 'subscription_created', 'checkout_success']);
            })
            ->whereNotIn('event_name', KpiSummaryService::LEAD_EVENTS)
            ->orderBy('occurred_at', 'asc')
            ->get();

        $totalRevenue = 0.0;
        $mrr = 0.0;
        $seenOrders = [];
        $categoryMap = [];
        $regionMap = [];
        $totalTransactions = 0;
        $packMap = [];
        $dailyRevenueMap = [];
        $recentTransactions = [];

        foreach ($businessEvents as $event) {
            $payload = $event->payload ?? [];
            $data = $payload['eventData'] ?? $payload;

            // Une commande rejouée (même order_id) n'est comptée qu'une fois
            $orderKey = $data['order_id'] ?? $data['orderId'] ?? null;
            if ($orderKey !== null) {
                if (isset($seenOrders[$orderKey])) {
                    continue;
                }
                $seenOrders[$orderKey] = true;
            }
            $totalTransactions++;

            $amount = (float) ($data['amount'] ?? $data['price'] ?? 0.0);
            if ($event->event_name === 'subscription_created') {
                $mrr += $amount;
            }
            $category = trim((string) ($data['category'] ?? '')) ?: 'Non catégorisé';
            $region = trim((string) ($data['region'] ?? '')) ?: 'Non renseignée';
            $categoryMap[$category] ??= ['revenue' => 0.0, 'count' => 0];
            $categoryMap[$category]['revenue'] += $amount;
            $categoryMap[$category]['count']++;
            $regionMap[$region] ??= ['revenue' => 0.0, 'count' => 0];
            $regionMap[$region]['revenue'] += $amount;
            $regionMap[$region]['count']++;
            $packName = trim((string) ($data['pack'] ?? $data['plan'] ?? $data['pack_name'] ?? $data['name'] ?? 'Formule Standard'));
            $dateKey = Carbon::parse($event->occurred_at)->format('Y-m-d');

            $totalRevenue += $amount;

            if (! isset($dailyRevenueMap[$dateKey])) {
                $dailyRevenueMap[$dateKey] = ['revenue' => 0.0, 'transactions' => 0];
            }
            $dailyRevenueMap[$dateKey]['revenue'] += $amount;
            $dailyRevenueMap[$dateKey]['transactions']++;

            // Ventilation dynamique par vrais packs de la BDD du client
            if ($packName !== '') {
                if (! isset($packMap[$packName])) {
                    $packMap[$packName] = [
                        'revenue' => 0.0,
                        'count' => 0,
                        'price' => $amount,
                    ];
                }
                $packMap[$packName]['revenue'] += $amount;
                $packMap[$packName]['count']++;
            }

            // Conserver les détails de la transaction pour la table analytique
            $recentTransactions[] = [
                'orderId' => (string) ($data['orderId'] ?? 'PAY-' . rand(1000, 9999)),
                'userName' => (string) ($data['userName'] ?? $data['user_name'] ?? 'Client Anonyme'),
                'userEmail' => (string) ($data['userEmail'] ?? $data['user_email'] ?? 'client@domaine.com'),
                'pack' => $packName,
                'amount' => $amount,
                'currency' => (string) ($data['currency'] ?? 'EUR'),
                'date' => (string) ($data['date'] ?? Carbon::parse($event->occurred_at)->format('Y-m-d H:i')),
                'status' => (string) ($data['status'] ?? 'VALIDATED'),
            ];
        }

        // Trier par date décroissante pour afficher les transactions récentes
        usort($recentTransactions, function ($a, $b) {
            return strcmp($b['date'], $a['date']);
        });
        $recentTransactions = array_slice($recentTransactions, 0, 50);

        // Calcul des pourcentages de chaque pack réel
        foreach ($packMap as $k => $p) {
            $packMap[$k]['percentage'] = $totalRevenue > 0
                ? round(($p['revenue'] / $totalRevenue) * 100, 1)
                : 0;
            $packMap[$k]['revenue'] = round($packMap[$k]['revenue'], 2);
        }

        $mrr = round($mrr, 2);
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
                $dayEvents = RawEventModel::where($rawScope)
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

        // 4. Répartition dynamique des appareils, navigateurs, Top Pages et Top Clics
        $rawEvents = RawEventModel::where($rawScope)
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->limit(2000)
            ->get();

        $deviceBreakdown = ['DESKTOP' => 0, 'MOBILE' => 0, 'TABLET' => 0, 'BOT' => 0];
        $browserBreakdown = ['Chrome' => 0, 'Safari' => 0, 'Firefox' => 0, 'Edge' => 0];

        $pageMap = [];
        $clickMap = [];
        $formMap = [];
        $totalFormSubmissions = 0;
        $totalActiveSeconds = 0;
        $totalPageSeconds = 0;
        $durationEventCount = 0;
        $bounceSessionsCount = 0;

        foreach ($rawEvents as $event) {
            $contextData = $event->context ?? [];
            $payloadData = $event->payload ?? [];
            $eventName = (string) ($event->event_name ?? '');

            // Appareils & Navigateurs
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

            // Top Pages (page_view)
            if ($eventName === 'page_view') {
                $path = (string) ($payloadData['path'] ?? $contextData['path'] ?? '/');
                if ($path !== '') {
                    if (! isset($pageMap[$path])) {
                        $pageMap[$path] = ['path' => $path, 'views' => 0, 'totalDuration' => 0];
                    }
                    $pageMap[$path]['views']++;
                }
            }

            // Durées & Rebond (page_duration)
            if ($eventName === 'page_duration') {
                $pDuration = (int) ($payloadData['page_duration'] ?? 0);
                $aDuration = (int) ($payloadData['active_duration'] ?? $pDuration);
                $isBounce = (bool) ($payloadData['is_bounce'] ?? false);
                $path = (string) ($payloadData['path'] ?? '/');

                $totalPageSeconds += $pDuration;
                $totalActiveSeconds += $aDuration;
                $durationEventCount++;

                if ($isBounce) {
                    $bounceSessionsCount++;
                }

                if (isset($pageMap[$path])) {
                    $pageMap[$path]['totalDuration'] += $aDuration;
                }
            }

            // Top Clics (click_interaction)
            if ($eventName === 'click_interaction') {
                $selector = (string) ($payloadData['selector'] ?? 'button');
                $label = (string) ($payloadData['label'] ?? 'Clic');
                $elementType = (string) ($payloadData['element_type'] ?? 'button');
                $key = $selector . '|' . $label;

                if (! isset($clickMap[$key])) {
                    $clickMap[$key] = [
                        'selector' => $selector,
                        'label' => $label,
                        'elementType' => $elementType,
                        'count' => 0,
                    ];
                }
                $clickMap[$key]['count']++;
            }

            // Top Formulaires Soumis (form_submit)
            if ($eventName === 'form_submit') {
                $formId = (string) ($payloadData['form_id'] ?? $payloadData['form_name'] ?? 'unnamed_form');
                $formName = (string) ($payloadData['form_name'] ?? '');
                $path = (string) ($payloadData['path'] ?? $contextData['path'] ?? '/');
                $fieldCount = (int) ($payloadData['field_count'] ?? 0);
                $status = (string) ($payloadData['status'] ?? 'submitted');
                $key = $formId . '|' . $path;

                if (! isset($formMap[$key])) {
                    $formMap[$key] = [
                        'form_id' => $formId,
                        'form_name' => $formName,
                        'path' => $path,
                        'field_count' => $fieldCount,
                        'status' => $status,
                        'count' => 0,
                    ];
                }
                $formMap[$key]['count']++;
                $totalFormSubmissions++;
            }
        }

        // Formater Top Pages (trier par vues décroissantes, limiter à 10)
        usort($pageMap, function ($a, $b) {
            return $b['views'] <=> $a['views'];
        });
        $topPages = array_slice(array_map(function ($p) {
            return [
                'path' => $p['path'],
                'views' => $p['views'],
                'avgDuration' => $p['views'] > 0 ? (int) round($p['totalDuration'] / $p['views']) : 0,
            ];
        }, $pageMap), 0, 10);

        // Formater Top Clics (trier par count décroissant, limiter à 10)
        usort($clickMap, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });
        $topClicks = array_values(array_slice($clickMap, 0, 10));

        // Formater Top Formulaires (trier par count décroissant, limiter à 10)
        usort($formMap, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });
        $topForms = array_values(array_slice($formMap, 0, 10));

        $avgActiveDuration = $durationEventCount > 0 ? (int) round($totalActiveSeconds / $durationEventCount) : 0;
        $avgSessionDuration = $durationEventCount > 0 ? (int) round($totalPageSeconds / $durationEventCount) : 0;
        if ($durationEventCount > 0 && $sessions > 0) {
            $bounceRate = round(($bounceSessionsCount / max(1, $durationEventCount)) * 100, 1);
        }

        $recentRawEvents = [];
        foreach ($rawEvents->take(25) as $e) {
            $pData = $e->payload ?? [];
            $cData = $e->context ?? [];
            $recentRawEvents[] = [
                'id' => (string) $e->id,
                'eventName' => (string) ($e->event_name ?? ''),
                'eventType' => (string) ($e->event_type ?? 'NAVIGATION'),
                'visitorId' => (string) ($e->visitor_id ?? 'vis_anonyme'),
                'sessionId' => (string) ($e->session_id ?? ''),
                'path' => (string) ($pData['path'] ?? $cData['path'] ?? '/'),
                'label' => (string) ($pData['label'] ?? $pData['selector'] ?? $pData['title'] ?? ''),
                'device' => (string) ($cData['device_type'] ?? 'DESKTOP'),
                'browser' => (string) ($cData['browser'] ?? 'Chrome'),
                'occurredAt' => $e->occurred_at ? Carbon::parse($e->occurred_at)->format('Y-m-d H:i:s') : '',
            ];
        }

        $rankBreakdown = function (array $map) use ($totalRevenue): array {
            uasort($map, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);
            $rows = [];
            foreach (array_slice($map, 0, 10, true) as $label => $row) {
                $rows[] = [
                    'label' => (string) $label,
                    'revenue' => round($row['revenue'], 2),
                    'count' => $row['count'],
                    'percentage' => $totalRevenue > 0 ? round($row['revenue'] / $totalRevenue * 100, 1) : 0,
                ];
            }

            return $rows;
        };

        $resource = new AnalyticsMetricsResource();
        $resource->instanceId = (string) $instanceId;
        $resource->startDate = (string) $startDateStr;
        $resource->endDate = (string) $endDateStr;
        $resource->kpis = [
            'page_views' => $pageViews,
            'unique_visitors' => $uniqueVisitors,
            'sessions' => $sessions,
            'conversions' => $conversions,
            'form_submissions' => $totalFormSubmissions,
            'bounce_rate' => $bounceRate,
            'avg_active_duration' => $avgActiveDuration,
            'avg_session_duration' => $avgSessionDuration,
        ];
        $resource->financialKpis = ($remoteData && ! empty($remoteData['financialKpis']))
            ? $remoteData['financialKpis']
            : [
                'total_revenue' => round($totalRevenue, 2),
                'mrr' => $mrr,
                'average_order_value' => $averageOrderValue,
                'total_transactions' => $totalTransactions,
                'conversion_rate' => $financialConversionRate,
            ];
        $resource->packBreakdown = ($remoteData && ! empty($remoteData['packBreakdown'])) ? $remoteData['packBreakdown'] : $packMap;
        $resource->timeSeries = $timeSeries;
        $resource->revenueTimeSeries = ($remoteData && ! empty($remoteData['revenueTimeSeries'])) ? $remoteData['revenueTimeSeries'] : $revenueTimeSeries;
        $resource->deviceBreakdown = $deviceBreakdown;
        $resource->browserBreakdown = $browserBreakdown;
        $resource->recentTransactions = ($remoteData && ! empty($remoteData['recentTransactions'])) ? $remoteData['recentTransactions'] : $recentTransactions;
        $resource->topPages = $topPages;
        $resource->topClicks = $topClicks;
        $resource->topForms = $topForms;
        $resource->recentRawEvents = $recentRawEvents;
        $resource->topCategories = $rankBreakdown($categoryMap);
        $resource->regionBreakdown = $rankBreakdown($regionMap);
        $resource->activeFilters = $filters;
        $resource->kpiSummary = app(KpiSummaryService::class)->build($rawScope, $startDate, $endDate, ['category' => [], 'region' => [], 'device' => []]);

        return [$resource];
    }
}

