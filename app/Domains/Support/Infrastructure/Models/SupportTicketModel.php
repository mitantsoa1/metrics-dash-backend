<?php

declare(strict_types=1);

namespace App\Domains\Support\Infrastructure\Models;

use App\Domains\IAM\Infrastructure\Models\TenantModel;
use App\Domains\IAM\Infrastructure\Models\UserModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTicketModel extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'support_tickets';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'tenant_id',
        'user_id',
        'name',
        'email',
        'role',
        'subject',
        'message',
        'status',
        'priority',
        'admin_notes',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
