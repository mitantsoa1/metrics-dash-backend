<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledReportLogModel extends Model
{
    protected $table = 'scheduled_report_logs';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'scheduled_report_id',
        'executed_at',
        'status',
        'recipients_count',
        'recipients_sent',
        'error_message',
        'duration_ms',
    ];

    protected $casts = [
        'executed_at' => 'datetime',
        'recipients_count' => 'integer',
        'recipients_sent' => 'array',
        'duration_ms' => 'integer',
    ];

    public function scheduledReport(): BelongsTo
    {
        return $this->belongsTo(ScheduledReportModel::class, 'scheduled_report_id');
    }
}
