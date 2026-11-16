<?php

declare(strict_types=1);

namespace App\Domains\Instance\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKeyModel extends Model
{
    protected $table = 'api_keys';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'instance_id',
        'type',
        'key_hash',
        'prefix',
        'plain_key',
        'last_used_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(InstanceModel::class, 'instance_id');
    }
}
