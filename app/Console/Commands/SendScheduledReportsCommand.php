<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Analytics\Services\ScheduledReportRunnerService;
use Illuminate\Console\Command;

class SendScheduledReportsCommand extends Command
{
    protected $signature = 'reports:send-scheduled';

    protected $description = 'Exécute les rapports analytiques programmés et envoie les e-mails aux destinataires';

    public function handle(ScheduledReportRunnerService $runnerService): int
    {
        $this->info('Démarrage de la vérification des rapports programmés...');

        $summary = $runnerService->runDueReports();

        $this->info("Rapports traités : {$summary['processed']} (Succès: {$summary['success']}, Échecs: {$summary['failed']}, Verrouillés: {$summary['skipped_locked']})");

        return 0;
    }
}
