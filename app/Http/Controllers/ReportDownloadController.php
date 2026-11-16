<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domains\Analytics\Infrastructure\Models\DailyMetricModel;
use App\Domains\Ingestion\Infrastructure\Models\RawEventModel;
use App\Support\AuthorizesTenantAccess;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportDownloadController extends Controller
{
    use AuthorizesTenantAccess;

    public function __invoke(Request $request): StreamedResponse
    {
        $instanceId = $request->query('instanceId');
        $this->assertInstanceAccessible($instanceId ? (string) $instanceId : null);
        $format = strtolower((string) $request->query('format', 'csv'));
        $startDate = (string) $request->query('startDate', date('Y-m-d', strtotime('-30 days')));
        $endDate = (string) $request->query('endDate', date('Y-m-d'));

        $filename = "metrics_report_" . ($instanceId ?: 'all') . "_{$startDate}_to_{$endDate}.csv";

        $response = new StreamedResponse(function () use ($instanceId, $startDate, $endDate) {
            $handle = fopen('php://output', 'w');

            // En-tête du Rapport
            fputcsv($handle, ['METRICSDASH ENTERPRISE - RAPPORT DE METRIQUES ET TELEMETRIE']);
            fputcsv($handle, ['Instance ID', $instanceId ?: 'Toutes les instances']);
            fputcsv($handle, ['Periode', "Du {$startDate} au {$endDate}"]);
            fputcsv($handle, ['Date d extraction', date('Y-m-d H:i:s')]);
            fputcsv($handle, []);

            // Section 1: Métriques Quotidiennes
            fputcsv($handle, ['--- METRIQUES QUOTIDIENNES ---']);
            fputcsv($handle, ['Date', 'Pages Vues', 'Visiteurs Uniques', 'Chiffre d Affaires (EUR)', 'Transactions']);

            $query = DailyMetricModel::query()->whereBetween('date', [$startDate, $endDate]);
            if ($instanceId && $instanceId !== 'default' && $instanceId !== 'system') {
                $query->where('instance_id', $instanceId);
            }
            $metrics = $query->orderBy('date', 'desc')->get();

            foreach ($metrics as $metric) {
                fputcsv($handle, [
                    $metric->date,
                    $metric->page_views,
                    $metric->unique_visitors,
                    $metric->total_revenue,
                    $metric->total_transactions,
                ]);
            }

            fputcsv($handle, []);

            // Section 2: Journal des Événements Bruts
            fputcsv($handle, ['--- JOURNAL DES EVENEMENTS BRUTS (RAW EVENTS) ---']);
            fputcsv($handle, ['ID', 'Nom Evénement', 'Visiteur ID', 'Session ID', 'Appareil', 'Navigateur', 'Chemin URL', 'Date & Heure']);

            $eventsQuery = RawEventModel::query()->orderBy('occurred_at', 'desc')->limit(500);
            if ($instanceId && $instanceId !== 'default' && $instanceId !== 'system') {
                $eventsQuery->where('instance_id', $instanceId);
            }
            $events = $eventsQuery->get();

            foreach ($events as $evt) {
                $path = $evt->payload['path'] ?? $evt->payload['url'] ?? '';
                $device = $evt->context['device'] ?? 'Desktop';
                $browser = $evt->context['browser'] ?? 'Browser';

                fputcsv($handle, [
                    $evt->id,
                    $evt->event_name,
                    $evt->visitor_id,
                    $evt->session_id,
                    $device,
                    $browser,
                    $path,
                    $evt->occurred_at,
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$filename}\"");

        return $response;
    }
}
