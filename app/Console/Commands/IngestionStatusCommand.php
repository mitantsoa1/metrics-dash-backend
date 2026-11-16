<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Ingestion\Jobs\PersistRawEventsJob;
use App\Domains\Ingestion\Infrastructure\Models\RawEventModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Supervision du tampon d'ingestion (PU-51) : messages en attente, retard, échecs (dead-letter).
 * Code de sortie 1 si la file a du retard ou des messages en échec : utilisable par une sonde.
 */
class IngestionStatusCommand extends Command
{
    protected $signature = 'ingestion:status';

    protected $description = 'Affiche l\'état de la file d\'ingestion : en attente, retard, échecs, débit';

    public function handle(): int
    {
        $mode = (string) config('ingestion.buffer', 'queue');
        $this->line("Mode du tampon : {$mode}");

        if ($mode === 'inline') {
            $this->warn('Mode inline : pas de file, pas de reprise. Passez INGESTION_BUFFER=queue pour la persistance.');
        }

        $last10 = RawEventModel::where('created_at', '>=', now()->subMinutes(10))->count();
        $lastHour = RawEventModel::where('created_at', '>=', now()->subHour())->count();

        $rows = [
            ['Événements écrits (10 dernières minutes)', $last10],
            ['Événements écrits (dernière heure)', $lastHour],
        ];
        $problem = false;

        if (config('queue.default') !== 'database') {
            $this->warn('Détail de la file disponible seulement avec QUEUE_CONNECTION=database.');
        } elseif (Schema::hasTable('jobs')) {
            // Nom court de la classe, sans anti-slash : avec PostgreSQL, « \ » est le caractère d'échappement de LIKE.
            $needle = '%'.class_basename(PersistRawEventsJob::class).'%';
            $pending = DB::table('jobs')->where('payload', 'like', $needle);
            $count = (clone $pending)->count();
            $reserved = (clone $pending)->whereNotNull('reserved_at')->count();
            $oldest = (clone $pending)->min('created_at');
            $lag = $oldest === null ? 0 : max(0, time() - (int) $oldest);
            $failed = Schema::hasTable('failed_jobs')
                ? DB::table('failed_jobs')->where('payload', 'like', $needle)->count()
                : 0;

            $rows[] = ['Lots en attente', $count];
            $rows[] = ['dont en cours de traitement', $reserved];
            $rows[] = ['Retard du plus ancien (s)', $lag];
            $rows[] = ['Lots en échec définitif (dead-letter)', $failed];

            $problem = $lag > (int) config('ingestion.lag_alert_seconds', 120) || $failed > 0;
            if ($lag > (int) config('ingestion.lag_alert_seconds', 120)) {
                $this->error("File en retard de {$lag} s : le worker tourne-t-il ? (php artisan queue:work)");
            }
            if ($failed > 0) {
                $this->error("{$failed} lot(s) en échec : voir php artisan queue:failed, rejouer avec php artisan queue:retry all");
            }
        }

        $this->table(['Indicateur', 'Valeur'], $rows);

        return $problem ? self::FAILURE : self::SUCCESS;
    }
}
