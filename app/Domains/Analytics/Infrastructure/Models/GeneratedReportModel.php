<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Infrastructure\Models;

use App\Domains\Instance\Infrastructure\Models\InstanceModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedReportModel extends Model
{
    protected $table = 'generated_reports';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'tenant_id',
        'instance_id',
        'name',
        'format',
        'period_start',
        'period_end',
        'file_path',
        'file_data',
        'file_size',
        'records_count',
        'kpi_summary',
        'created_by',
    ];

    protected $casts = [
        'period_start' => 'date:Y-m-d',
        'period_end' => 'date:Y-m-d',
        'file_size' => 'integer',
        'records_count' => 'integer',
        'kpi_summary' => 'array',
    ];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(InstanceModel::class, 'instance_id');
    }
}
