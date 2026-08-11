<?php

namespace App\Core\Modules\Tenant\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantUser extends Model
{
    protected $table = 'tenant_user';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'role',
    ];

    protected $casts = [
        'role' => 'string',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
