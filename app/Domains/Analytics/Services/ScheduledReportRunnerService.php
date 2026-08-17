<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Services;

use App\Domains\Analytics\Infrastructure\Models\ScheduledReportLogModel;
use App\Domains\Analytics\Infrastructure\Models\ScheduledReportModel;
use App\Mail\AutomatedKpiReportMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ScheduledReportRunnerService
{
    public function __construct(
        private readonly ReportGeneratorService $generatorService
    ) {}

    /**
     * Exécute tous les rapports automatiques arrivés à échéance.
     *
     * @return array<string, mixed>
     */
    public function runDueReports(): array
    {
        $now = Carbon::now();
        $dueReports = ScheduledReportModel::where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('next_run_at')
                  ->orWhere('next_run_at', '<=', $now);
            })
            ->get();

        $summary = [
            'total_due' => $dueReports->count(),
            'processed' => 0,
            'success' => 0,
            'failed' => 0,
            'skipped_locked' => 0,
        ];

        foreach ($dueReports as $report) {
            $lock = Cache::lock("scheduled_report_run_{$report->id}", 120);

            if (! $lock->get()) {
                $summary['skipped_locked']++;
                continue;
            }

            try {
                $startTime = microtime(true);
                $this->executeReport($report);
                $durationMs = (int) round((microtime(true) - $startTime) * 1000);

                ScheduledReportLogModel::create([
                    'id' => (string) Str::uuid(),
                    'scheduled_report_id' => $report->id,
                    'executed_at' => Carbon::now(),
                    'status' => 'SUCCESS',
                    'recipients_count' => count($report->recipients ?? []),
                    'recipients_sent' => $report->recipients,
                    'duration_ms' => $durationMs,
                ]);

                $summary['success']++;
            } catch (\Throwable $e) {
                Log::error("Erreur lors de l'exécution du rapport planifié {$report->id}: {$e->getMessage()}", [
                    'exception' => $e,
                ]);

                ScheduledReportLogModel::create([
                    'id' => (string) Str::uuid(),
                    'scheduled_report_id' => $report->id,
                    'executed_at' => Carbon::now(),
                    'status' => 'FAILED',
                    'recipients_count' => count($report->recipients ?? []),
                    'error_message' => $e->getMessage(),
                ]);

                $summary['failed']++;
            } finally {
                // Mettre à jour la date de prochaine exécution même en cas d'erreur pour éviter la boucle infinie
                $report->last_run_at = Carbon::now();
                $report->next_run_at = $report->calculateNextRunAt(Carbon::now());
                $report->save();

                $lock->release();
                $summary['processed']++;
            }
        }

        return $summary;
    }

    /**
     * Déclenche un envoi de test manuel immédiat pour un rapport planifié.
     */
    public function sendTestReport(ScheduledReportModel $report, ?string $overrideRecipient = null): array
    {
        $recipients = $overrideRecipient ? [$overrideRecipient] : ($report->recipients ?: []);
        if (empty($recipients)) {
            throw new \InvalidArgumentException("Aucun destinataire configuré pour ce rapport.");
        }

        $startTime = microtime(true);
        $this->executeReport($report, $recipients, isTest: true);
        $durationMs = (int) round((microtime(true) - $startTime) * 1000);

        ScheduledReportLogModel::create([
            'id' => (string) Str::uuid(),
            'scheduled_report_id' => $report->id,
            'executed_at' => Carbon::now(),
            'status' => 'SUCCESS',
            'recipients_count' => count($recipients),
            'recipients_sent' => $recipients,
            'duration_ms' => $durationMs,
        ]);

        return [
            'success' => true,
            'recipients' => $recipients,
            'message' => 'Email de test envoyé avec succès aux destinataires.',
        ];
    }

    /**
     * Génère et expédie le rapport par email.
     */
    private function executeReport(ScheduledReportModel $report, ?array $customRecipients = null, bool $isTest = false): void
    {
        $recipients = $customRecipients ?? ($report->recipients ?? []);
        if (empty($recipients)) {
            return;
        }

        // Calcul de la période couverte selon la fréquence ou le mode test
        if ($isTest) {
            $endDate = Carbon::now();
            $startDate = Carbon::now()->subDays(29);
            $freqLabel = 'Aperçu Test (30 derniers jours)';
        } else {
            $endDate = Carbon::now()->subDay();
            switch (strtoupper($report->frequency)) {
                case 'DAILY':
                    $startDate = $endDate->copy();
                    $freqLabel = 'Rapport Quotidien';
                    break;
                case 'MONTHLY':
                    $startDate = $endDate->copy()->startOfMonth();
                    $freqLabel = 'Rapport Mensuel';
                    break;
                case 'WEEKLY':
                default:
                    $startDate = $endDate->copy()->subDays(6);
                    $freqLabel = 'Rapport Hebdomadaire';
                    break;
            }
        }

        $reportTitle = ($isTest ? '[TEST] ' : '') . $report->name;
        $reportData = $this->generatorService->buildReportData(
            instanceId: $report->instance_id,
            startDateStr: $startDate->format('Y-m-d'),
            endDateStr: $endDate->format('Y-m-d'),
            reportTitle: $reportTitle,
            frequencyLabel: $freqLabel,
            includedKpis: $report->included_kpis ?? []
        );

        // Préparation des pièces jointes selon formats
        $attachments = [];
        $attachedFormatsList = [];

        // Préparation du résumé complet avec Top Pages & Top Clics pour l'historique
        $fullKpiSummary = array_merge($reportData['kpis'] ?? [], [
            'financials' => $reportData['financialKpis'] ?? [],
            'top_pages' => $reportData['topPages'] ?? [],
            'top_clicks' => $reportData['topClicks'] ?? [],
            'device_breakdown' => $reportData['deviceBreakdown'] ?? [],
            'browser_breakdown' => $reportData['browserBreakdown'] ?? [],
        ]);

        if (in_array(strtoupper($report->formats), ['EXCEL', 'BOTH', 'CSV'], true)) {
            $csvContent = $this->generatorService->generateCsvReport($reportData);
            $fileName = 'rapport-' . Str::slug($report->name) . '-' . $endDate->format('Y-m-d') . '.csv';
            $attachments[] = [
                'data' => $csvContent,
                'name' => $fileName,
                'mime' => 'text/csv',
            ];
            $attachedFormatsList[] = 'Tableau Excel / CSV';

            // Sauvegarder dans l'historique
            $this->generatorService->saveGeneratedReport(
                name: $report->name . ' (' . $freqLabel . ')',
                format: 'EXCEL',
                instanceId: $report->instance_id,
                periodStart: $startDate->format('Y-m-d'),
                periodEnd: $endDate->format('Y-m-d'),
                fileContent: $csvContent,
                kpiSummary: $fullKpiSummary,
                createdBy: 'Planification Automatique'
            );
        }

        if (in_array(strtoupper($report->formats), ['PDF', 'BOTH'], true)) {
            try {
                $pdfContent = $this->generatorService->generatePdfReport($reportData);
                $pdfFileName = 'rapport-' . Str::slug($report->name) . '-' . $endDate->format('Y-m-d') . '.pdf';
                $attachments[] = [
                    'data' => $pdfContent,
                    'name' => $pdfFileName,
                    'mime' => 'application/pdf',
                ];
                $attachedFormatsList[] = 'Synthèse PDF';

                // Sauvegarder dans l'historique
                $this->generatorService->saveGeneratedReport(
                    name: $report->name . ' (' . $freqLabel . ')',
                    format: 'PDF',
                    instanceId: $report->instance_id,
                    periodStart: $startDate->format('Y-m-d'),
                    periodEnd: $endDate->format('Y-m-d'),
                    fileContent: $pdfContent,
                    kpiSummary: $fullKpiSummary,
                    createdBy: 'Planification Automatique'
                );
            } catch (\Throwable $pdfError) {
                Log::error("Erreur lors de la génération PDF du rapport: " . $pdfError->getMessage());
            }
        }

        $reportData['attachedFormats'] = implode(' et ', $attachedFormatsList);

        // Envoi de l'email
        Mail::to($recipients)->send(new AutomatedKpiReportMail($reportData, $attachments));
    }
}
