<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Jobs;

use App\Domains\Analytics\Services\MetricAggregationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAutomatedReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $tenantId,
        public readonly string $recipientEmail
    ) {
        $this->onQueue('exports-reports');
    }

    public function handle(MetricAggregationService $aggregationService): void
    {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $summary = $aggregationService->aggregateForDate($yesterday);

        Log::info("Rapport de performance mensuel/hebdomadaire généré avec succès pour le tenant {$this->tenantId}", [
            'recipient' => $this->recipientEmail,
            'summary' => $summary,
        ]);
    }
}
