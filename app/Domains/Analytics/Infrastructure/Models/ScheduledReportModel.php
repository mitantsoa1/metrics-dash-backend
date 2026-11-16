<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Infrastructure\Models;

use App\Domains\Instance\Infrastructure\Models\InstanceModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduledReportModel extends Model
{
    protected $table = 'scheduled_reports';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'tenant_id',
        'instance_id',
        'name',
        'frequency',
        'day_of_week',
        'day_of_month',
        'time',
        'timezone',
        'formats',
        'recipients',
        'included_kpis',
        'is_active',
        'next_run_at',
        'last_run_at',
        'created_by',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'day_of_month' => 'integer',
        'recipients' => 'array',
        'included_kpis' => 'array',
        'is_active' => 'boolean',
        'next_run_at' => 'datetime',
        'last_run_at' => 'datetime',
    ];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(InstanceModel::class, 'instance_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ScheduledReportLogModel::class, 'scheduled_report_id')->orderBy('executed_at', 'desc');
    }

    /**
     * Calcule la date de prochaine exécution selon la fréquence, l'heure et le timezone.
     */
    public function calculateNextRunAt(?Carbon $fromDate = null): Carbon
    {
        $tz = $this->timezone ?: 'Europe/Paris';
        $now = $fromDate ? $fromDate->copy()->setTimezone($tz) : Carbon::now($tz);
        [$hour, $minute] = explode(':', $this->time ?: '09:00');
        $hour = (int) $hour;
        $minute = (int) $minute;

        $target = $now->copy()->setTime($hour, $minute, 0);

        switch (strtoupper($this->frequency)) {
            case 'DAILY':
                if ($target->lte($now)) {
                    $target->addDay();
                }
                break;

            case 'WEEKLY':
                $desiredDow = $this->day_of_week ?: 1; // 1 = Monday
                // Carbon: 1 = Monday ... 7 = Sunday
                $currentDow = $target->dayOfWeekIso;
                $daysToAdd = ($desiredDow - $currentDow + 7) % 7;
                if ($daysToAdd === 0 && $target->lte($now)) {
                    $daysToAdd = 7;
                }
                $target->addDays($daysToAdd);
                break;

            case 'MONTHLY':
                $desiredDom = $this->day_of_month ?: 1;
                $target->setDay(min($desiredDom, $target->daysInMonth));
                if ($target->lte($now)) {
                    $target->addMonthNoOverflow();
                    $target->setDay(min($desiredDom, $target->daysInMonth));
                }
                break;

            default:
                if ($target->lte($now)) {
                    $target->addDay();
                }
                break;
        }

        return $target->utc();
    }
}
