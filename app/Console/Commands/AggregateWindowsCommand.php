<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Analytics\Services\WindowAggregationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AggregateWindowsCommand extends Command
{
    protected $signature = 'metrics:aggregate-windows {--at= : Instant ISO 8601 dont la fenêtre est recalculée (défaut: maintenant)}';

    protected $description = 'Agrège raw_events par fenêtre de 10 minutes (fenêtre courante et précédente, pour absorber les retards)';

    public function handle(WindowAggregationService $service): int
    {
        $at = $this->option('at') ? Carbon::parse((string) $this->option('at')) : now();
        $current = $service->windowStart($at);

        foreach ([$current->copy()->subMinutes(WindowAggregationService::WINDOW_MINUTES), $current] as $window) {
            $summary = $service->aggregateWindow($window);
            $this->info("Fenêtre {$summary['window_start']} : {$summary['instances']} instance(s), {$summary['metrics']} métrique(s)");
        }

        return self::SUCCESS;
    }
}
