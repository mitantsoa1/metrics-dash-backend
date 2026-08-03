<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class DailyMetricModel extends Model
{
    protected $table = 'daily_metrics';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'instance_id',
        'date',
        'metric_name',
        'metric_value',
        'dimensions',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'metric_value' => 'integer',
        'dimensions' => 'array',
    ];
}
