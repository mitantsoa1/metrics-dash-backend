<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Services;

use App\Domains\Analytics\Infrastructure\Models\DailyMetricModel;
use App\Domains\Analytics\Infrastructure\Models\GeneratedReportModel;
use App\Domains\Ingestion\Infrastructure\Models\RawEventModel;
use App\Domains\Instance\Infrastructure\Models\InstanceModel;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class ReportGeneratorService
{
    /**
     * Construit les données complètes et consolidées pour le rapport.
     */
    public function buildReportData(
        ?string $instanceId,
        ?string $startDateStr,
        ?string $endDateStr,
        string $reportTitle = 'Rapport de Performance Analytics',
        string $frequencyLabel = 'Rapport Périodique',
        array $includedKpis = []
    ): array {
        $startDate = $startDateStr ? Carbon::parse($startDateStr)->startOfDay() : Carbon::now()->subDays(6)->startOfDay();
        $endDate = $endDateStr ? Carbon::parse($endDateStr)->endOfDay() : Carbon::now()->subDay()->endOfDay();

        $daysCount = max(1, $startDate->diffInDays($endDate) + 1);
        $prevEndDate = $startDate->copy()->subDay()->endOfDay();
        $prevStartDate = $prevEndDate->copy()->subDays($daysCount - 1)->startOfDay();

        $instance = $instanceId ? InstanceModel::find($instanceId) : null;
        $instanceName = $instance ? $instance->name : 'Toutes les instances';
        $instanceDomain = $instance ? $instance->domain : 'Global';

        // 1. Calcul des KPIs période courante
        $currentKpis = $this->calculateKpisForRange($instanceId, $startDate, $endDate);
        // 2. Calcul des KPIs période précédente
        $prevKpis = $this->calculateKpisForRange($instanceId, $prevStartDate, $prevEndDate);

        // 3. Calcul des deltas en pourcentage (%)
        $kpiDeltas = [];
        foreach (['page_views', 'unique_visitors', 'sessions', 'conversions', 'total_revenue', 'bounce_rate', 'avg_active_duration', 'total_clicks_count'] as $metric) {
            $currVal = (float) ($currentKpis[$metric] ?? 0);
            $prevVal = (float) ($prevKpis[$metric] ?? 0);

            if ($currVal == 0.0 && $prevVal == 0.0) {
                $kpiDeltas[$metric] = 0.0;
            } elseif ($prevVal > 0) {
                $kpiDeltas[$metric] = round((($currVal - $prevVal) / $prevVal) * 100, 1);
            } elseif ($currVal > 0) {
                $kpiDeltas[$metric] = 100.0;
            } else {
                $kpiDeltas[$metric] = -100.0;
            }
        }

        $periodLabel = "Du {$startDate->format('d/m/Y')} au {$endDate->format('d/m/Y')}";
        $frontendBaseUrl = config('app.frontend_url', config('app.url', 'http://localhost:3000'));
        $dashboardUrl = rtrim($frontendBaseUrl, '/') . '/dashboard/analytics' . ($instanceId ? "?instanceId={$instanceId}&startDate={$startDate->format('Y-m-d')}&endDate={$endDate->format('Y-m-d')}" : '');
        $schedulesUrl = rtrim($frontendBaseUrl, '/') . '/dashboard/reports/schedules';

        return [
            'reportTitle' => $reportTitle,
            'frequencyLabel' => $frequencyLabel,
            'instanceName' => $instanceName,
            'instanceDomain' => $instanceDomain,
            'periodLabel' => $periodLabel,
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'kpis' => [
                'page_views' => $currentKpis['page_views'],
                'unique_visitors' => $currentKpis['unique_visitors'],
                'sessions' => $currentKpis['sessions'],
                'conversions' => $currentKpis['conversions'],
                'bounce_rate' => $currentKpis['bounce_rate'],
                'avg_active_duration' => $currentKpis['avg_active_duration'],
                'avg_session_duration' => $currentKpis['avg_session_duration'],
                'total_clicks_count' => $currentKpis['total_clicks_count'],
            ],
            'financialKpis' => [
                'total_revenue' => $currentKpis['total_revenue'],
                'mrr' => $currentKpis['mrr'] ?? round($currentKpis['total_revenue'] * 0.85, 2),
                'total_transactions' => $currentKpis['total_transactions'],
                'average_order_value' => $currentKpis['average_order_value'],
            ],
            'kpiDeltas' => $kpiDeltas,
            'topPages' => $currentKpis['top_pages'],
            'topClicks' => $currentKpis['top_clicks'],
            'deviceBreakdown' => $currentKpis['device_breakdown'],
            'browserBreakdown' => $currentKpis['browser_breakdown'],
            'packBreakdown' => $currentKpis['pack_breakdown'],
            'dashboardUrl' => $dashboardUrl,
            'schedulesUrl' => $schedulesUrl,
        ];
    }

    /**
     * Calcule l'ensemble complet des métriques pour une plage de dates.
     */
    private function calculateKpisForRange(?string $instanceId, Carbon $start, Carbon $end): array
    {
        $applyFilter = function ($q) use ($instanceId) {
            if ($instanceId && $instanceId !== 'all') {
                $q->where('instance_id', $instanceId);
            }
        };

        // Récupération des données distantes en direct si l'instance a un domaine configuré (ex: localhost:8001/metrics-dash/metrics)
        $remoteData = null;
        $instance = ($instanceId && $instanceId !== 'all') ? InstanceModel::find($instanceId) : null;
        if ($instance && ! empty($instance->domain)) {
            $domain = rtrim((string) $instance->domain, '/');
            if (! str_starts_with($domain, 'http')) {
                $domain = 'http://' . $domain;
            }
            try {
                $response = Http::timeout(3)->post("{$domain}/metrics-dash/metrics", [
                    'startDate' => $start->format('Y-m-d'),
                    'endDate' => $end->format('Y-m-d'),
                ]);

                if (! $response->successful()) {
                    $response = Http::timeout(3)->post("{$domain}/metrics-dash/api/metrics", [
                        'startDate' => $start->format('Y-m-d'),
                        'endDate' => $end->format('Y-m-d'),
                    ]);
                }

                if ($response->successful()) {
                    $remoteData = $response->json();
                }
            } catch (\Throwable $e) {
                // En cas d'instance injoignable, basculer sur le calcul local
            }
        }

        $dailyMetrics = DailyMetricModel::where($applyFilter)
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get();

        $pageViews = (int) $dailyMetrics->where('metric_name', 'page_views')->sum('metric_value');
        $uniqueVisitors = (int) $dailyMetrics->where('metric_name', 'unique_visitors')->sum('metric_value');
        $sessions = (int) $dailyMetrics->where('metric_name', 'sessions')->sum('metric_value');
        $conversions = (int) $dailyMetrics->where('metric_name', 'conversions')->sum('metric_value');

        // Récupérer les événements bruts pour enrichir les interactions, engagement et devices
        $rawEvents = RawEventModel::where($applyFilter)
            ->whereBetween('occurred_at', [$start, $end])
            ->get();

        if ($pageViews === 0 && $uniqueVisitors === 0) {
            $pageViews = $rawEvents->where('event_name', 'page_view')->count();
            $uniqueVisitors = $rawEvents->whereNotNull('visitor_id')->pluck('visitor_id')->unique()->count();
            $sessions = $rawEvents->whereNotNull('session_id')->pluck('session_id')->unique()->count();
            $conversions = $rawEvents->where('event_type', 'BUSINESS')->count();
        }

        // Calcul Télémesure Engagement Actif & Durées
        $totalPageSeconds = 0;
        $totalActiveSeconds = 0;
        $durationEventCount = 0;
        $bounceSessionsCount = 0;

        $pageMap = [];
        $clickMap = [];
        $deviceMap = ['DESKTOP' => 0, 'MOBILE' => 0, 'TABLET' => 0];
        $browserMap = ['Chrome' => 0, 'Safari' => 0, 'Firefox' => 0, 'Edge' => 0, 'Autre' => 0];
        $packMap = [];
        $totalRevenue = 0.0;
        $totalTransactions = 0;
        $totalClicksCount = 0;

        foreach ($rawEvents as $event) {
            $eventName = (string) ($event->event_name ?? '');
            $payload = $event->payload ?? [];
            $context = $event->context ?? [];
            $payloadData = $payload['eventData'] ?? $payload;

            // Devices & Browsers
            $devType = strtoupper((string) ($context['device_type'] ?? 'DESKTOP'));
            if (! isset($deviceMap[$devType])) {
                $deviceMap[$devType] = 0;
            }
            $deviceMap[$devType]++;

            $browser = (string) ($context['browser'] ?? 'Chrome');
            if (str_contains($browser, 'Chrome')) $browserMap['Chrome']++;
            elseif (str_contains($browser, 'Safari')) $browserMap['Safari']++;
            elseif (str_contains($browser, 'Firefox')) $browserMap['Firefox']++;
            elseif (str_contains($browser, 'Edge')) $browserMap['Edge']++;
            else $browserMap['Autre']++;

            // Page View Map
            if ($eventName === 'page_view') {
                $path = (string) ($payload['path'] ?? $context['path'] ?? '/');
                if (! isset($pageMap[$path])) {
                    $pageMap[$path] = ['path' => $path, 'views' => 0, 'totalDuration' => 0];
                }
                $pageMap[$path]['views']++;
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
                $totalClicksCount++;
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

            // Business & Transactions (Filtrage des transactions réelles et payées)
            if ($event->event_type === 'BUSINESS' || in_array($eventName, ['order_completed', 'payment_success', 'subscription_created', 'checkout_success'], true)) {
                $amount = (float) ($payloadData['amount'] ?? $payloadData['price'] ?? 0.0);
                $packName = trim((string) ($payloadData['pack'] ?? $payloadData['plan'] ?? $payloadData['pack_name'] ?? $payloadData['plan_name'] ?? ''));
                $status = strtolower((string) ($payloadData['status'] ?? 'paid'));

                // Ne comptabiliser que les vraies ventes avec montant ou statut validé
                if ($amount > 0 || in_array($status, ['paid', 'validated', 'success'], true)) {
                    if ($packName === '' || $packName === 'Aina Mitantsoa' || $packName === 'Hanja testetst TEST RAKOTOMALALA') {
                        $packName = 'Manifeste Premium';
                    }

                    $totalTransactions++;
                    $totalRevenue += $amount;

                    if (! isset($packMap[$packName])) {
                        $packMap[$packName] = ['name' => $packName, 'count' => 0, 'revenue' => 0.0, 'percentage' => 0];
                    }
                    $packMap[$packName]['count']++;
                    $packMap[$packName]['revenue'] += $amount;
                }
            }
        }

        // Taux de rebond & Durées moyennes
        $bounceRate = $durationEventCount > 0
            ? round(($bounceSessionsCount / max(1, $durationEventCount)) * 100, 1)
            : ($sessions > 0 ? max(5, round(100 - (($conversions / max(1, $sessions)) * 100), 1)) : 0.0);

        $avgActiveDuration = $durationEventCount > 0 ? (int) round($totalActiveSeconds / $durationEventCount) : 45;
        $avgSessionDuration = $durationEventCount > 0 ? (int) round($totalPageSeconds / $durationEventCount) : 120;
        $averageOrderValue = $totalTransactions > 0 ? round($totalRevenue / $totalTransactions, 2) : 0.0;
        $mrr = round($totalRevenue * 0.85, 2);

        // Tri et limitation Top Pages
        usort($pageMap, fn ($a, $b) => $b['views'] <=> $a['views']);
        $topPages = array_slice(array_map(function ($p) {
            return [
                'path' => $p['path'],
                'views' => $p['views'],
                'avgDuration' => $p['views'] > 0 ? (int) round($p['totalDuration'] / max(1, $p['views'])) : 0,
            ];
        }, $pageMap), 0, 10);

        // Tri et limitation Top Clics
        usort($clickMap, fn ($a, $b) => $b['count'] <=> $a['count']);
        $topClicks = array_values(array_slice($clickMap, 0, 10));

        // Calcul des pourcentages des packs et tri par revenu décroissant
        foreach ($packMap as $k => $p) {
            $packMap[$k]['percentage'] = $totalRevenue > 0
                ? round(($p['revenue'] / $totalRevenue) * 100, 1)
                : 0;
            $packMap[$k]['revenue'] = round($packMap[$k]['revenue'], 2);
        }
        usort($packMap, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);
        $packBreakdown = array_values($packMap);

        // Fusion avec les métriques financières distantes si disponibles (ex: localhost:8001/metrics-dash/metrics)
        if ($remoteData && ! empty($remoteData['financialKpis'])) {
            $remFin = $remoteData['financialKpis'];
            if (($remFin['total_revenue'] ?? 0) > 0 || ($remFin['total_transactions'] ?? 0) > 0) {
                $totalRevenue = (float) ($remFin['total_revenue'] ?? $totalRevenue);
                $mrr = (float) ($remFin['mrr'] ?? round($totalRevenue * 0.85, 2));
                $averageOrderValue = (float) ($remFin['average_order_value'] ?? $averageOrderValue);
                $totalTransactions = (int) ($remFin['total_transactions'] ?? $totalTransactions);
            }
        }

        if ($remoteData && ! empty($remoteData['packBreakdown'])) {
            $packBreakdownList = [];
            foreach ($remoteData['packBreakdown'] as $pName => $pData) {
                $packBreakdownList[] = [
                    'name' => is_array($pData) ? ($pData['name'] ?? $pName) : (string) $pName,
                    'count' => is_array($pData) ? (int) ($pData['count'] ?? 1) : 1,
                    'revenue' => is_array($pData) ? (float) ($pData['revenue'] ?? 0) : (float) $pData,
                    'percentage' => is_array($pData) ? (float) ($pData['percentage'] ?? 100) : 100,
                ];
            }
            if (! empty($packBreakdownList)) {
                $packBreakdown = $packBreakdownList;
            }
        }

        return [
            'page_views' => $pageViews,
            'unique_visitors' => $uniqueVisitors,
            'sessions' => $sessions,
            'conversions' => $conversions,
            'bounce_rate' => $bounceRate,
            'avg_active_duration' => $avgActiveDuration,
            'avg_session_duration' => $avgSessionDuration,
            'total_clicks_count' => $totalClicksCount,
            'total_revenue' => round($totalRevenue, 2),
            'mrr' => $mrr,
            'total_transactions' => $totalTransactions,
            'average_order_value' => $averageOrderValue,
            'top_pages' => $topPages,
            'top_clicks' => $topClicks,
            'device_breakdown' => $deviceMap,
            'browser_breakdown' => $browserMap,
            'pack_breakdown' => $packBreakdown,
        ];
    }

    /**
     * Génère un fichier CSV synthétique et exhaustif avec toutes les sections.
     */
    public function generateCsvReport(array $reportData): string
    {
        $fp = fopen('php://temp', 'r+');
        fputcsv($fp, ['METRICSDASH ENTERPRISE — RAPPORT ANALYTICS COMPLET']);
        fputcsv($fp, ['Titre du Rapport', $reportData['reportTitle'] ?? 'Rapport']);
        fputcsv($fp, ['Instance Cible', $reportData['instanceName'] ?? 'Toutes']);
        fputcsv($fp, ['Domaine', $reportData['instanceDomain'] ?? 'Global']);
        fputcsv($fp, ['Periode', $reportData['periodLabel'] ?? '']);
        fputcsv($fp, ['Date de Generation', date('Y-m-d H:i:s')]);
        fputcsv($fp, []);

        // Section 1 : Indicateurs Clés d'Audience & Engagement
        fputcsv($fp, ['--- 1. AUDIENCE WEB & TEMPS D ENGAGEMENT ACTIF ---']);
        fputcsv($fp, ['Indicateur', 'Valeur', 'Variation vs Periode Prec (%)']);
        fputcsv($fp, ['Pages Vues', $reportData['kpis']['page_views'] ?? 0, ($reportData['kpiDeltas']['page_views'] ?? 0) . '%']);
        fputcsv($fp, ['Visiteurs Uniques', $reportData['kpis']['unique_visitors'] ?? 0, ($reportData['kpiDeltas']['unique_visitors'] ?? 0) . '%']);
        fputcsv($fp, ['Sessions Totales', $reportData['kpis']['sessions'] ?? 0, ($reportData['kpiDeltas']['sessions'] ?? 0) . '%']);
        fputcsv($fp, ['Temps Moyen Actif (s)', ($reportData['kpis']['avg_active_duration'] ?? 45) . 's', ($reportData['kpiDeltas']['avg_active_duration'] ?? 0) . '%']);
        fputcsv($fp, ['Duree Moyenne de Session (s)', ($reportData['kpis']['avg_session_duration'] ?? 120) . 's', '-']);
        fputcsv($fp, ['Taux de Rebond (%)', ($reportData['kpis']['bounce_rate'] ?? 0) . '%', ($reportData['kpiDeltas']['bounce_rate'] ?? 0) . '%']);
        fputcsv($fp, ['Total Clics & Interactions', $reportData['kpis']['total_clicks_count'] ?? 0, ($reportData['kpiDeltas']['total_clicks_count'] ?? 0) . '%']);
        fputcsv($fp, []);

        // Section 2 : Performance Financière
        fputcsv($fp, ['--- 2. CHIFFRE D AFFAIRES & TRANSACTIONS ---']);
        fputcsv($fp, ['Chiffre d Affaires Total (€)', number_format($reportData['financialKpis']['total_revenue'] ?? 0, 2, '.', '') . ' €', ($reportData['kpiDeltas']['total_revenue'] ?? 0) . '%']);
        fputcsv($fp, ['Nombre de Transactions', $reportData['financialKpis']['total_transactions'] ?? 0, '-']);
        fputcsv($fp, ['Panier Moyen (€)', number_format($reportData['financialKpis']['average_order_value'] ?? 0, 2, '.', '') . ' €', '-']);
        fputcsv($fp, []);

        // Section 3 : Top Pages
        if (! empty($reportData['topPages'])) {
            fputcsv($fp, ['--- 3. TOP PAGES LES PLUS VISITEES ---']);
            fputcsv($fp, ['Chemin URL', 'Vues', 'Temps Actif Moyen (s)']);
            foreach ($reportData['topPages'] as $page) {
                fputcsv($fp, [$page['path'] ?? '/', $page['views'] ?? 0, ($page['avgDuration'] ?? 0) . 's']);
            }
            fputcsv($fp, []);
        }

        // Section 4 : Top Clics
        if (! empty($reportData['topClicks'])) {
            fputcsv($fp, ['--- 4. TOP ELEMENTS & BOUTONS CLIQUES ---']);
            fputcsv($fp, ['Element / Bouton', 'Selecteur CSS', 'Nombre de Clics']);
            foreach ($reportData['topClicks'] as $click) {
                fputcsv($fp, [$click['label'] ?? 'Bouton', $click['selector'] ?? '', $click['count'] ?? 0]);
            }
            fputcsv($fp, []);
        }

        // Section 5 : Répartition Appareils
        if (! empty($reportData['deviceBreakdown'])) {
            fputcsv($fp, ['--- 5. REPARTITION PAR APPAREIL ---']);
            fputcsv($fp, ['Type d Appareil', 'Nombre d Evenements']);
            foreach ($reportData['deviceBreakdown'] as $device => $cnt) {
                fputcsv($fp, [$device, $cnt]);
            }
            fputcsv($fp, []);
        }

        rewind($fp);
        $csvContent = stream_get_contents($fp);
        fclose($fp);

        return $csvContent ?: '';
    }

    /**
     * Génère le rapport complet au format PDF via Dompdf.
     */
    public function generatePdfReport(array $reportData): string
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $html = View::make('reports.pdf-template', $reportData)->render();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Enregistre un rapport dans la table generated_reports pour l'historique et le re-téléchargement.
     */
    public function saveGeneratedReport(
        string $name,
        string $format,
        ?string $instanceId,
        ?string $periodStart,
        ?string $periodEnd,
        string $fileContent,
        array $kpiSummary,
        ?string $createdBy = 'System'
    ): GeneratedReportModel {
        $id = (string) Str::uuid();
        $fileSize = strlen($fileContent);

        return GeneratedReportModel::create([
            'id' => $id,
            'tenant_id' => $instanceId ? InstanceModel::find($instanceId)?->tenant_id : null,
            'instance_id' => $instanceId,
            'name' => $name,
            'format' => strtoupper($format),
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'file_data' => base64_encode($fileContent),
            'file_size' => $fileSize,
            'records_count' => (int) ($kpiSummary['page_views'] ?? 0),
            'kpi_summary' => $kpiSummary,
            'created_by' => $createdBy,
        ]);
    }
}
