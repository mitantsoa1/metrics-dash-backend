<?php

declare(strict_types=1);

namespace App\Domains\Instance\Infrastructure\Models;

use App\Domains\IAM\Infrastructure\Models\TenantModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstanceModel extends Model
{
    protected $table = 'instances';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'tenant_id',
        'name',
        'domain',
        'environment',
        'status',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKeyModel::class, 'instance_id');
    }
}
