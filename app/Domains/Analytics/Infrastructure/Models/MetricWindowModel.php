<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class MetricWindowModel extends Model
{
    protected $table = 'metric_windows';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'instance_id', 'window_start', 'metric_name', 'metric_value'];

    protected $casts = [
        'window_start' => 'datetime',
        'metric_value' => 'float',
    ];
}
