<?php

declare(strict_types=1);

namespace App\Domains\Ingestion\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class RawEventModel extends Model
{
    protected $table = 'raw_events';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'instance_id',
        'event_type',
        'event_name',
        'visitor_id',
        'session_id',
        'payload',
        'context',
        'occurred_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'context' => 'array',
        'occurred_at' => 'datetime',
    ];
}
