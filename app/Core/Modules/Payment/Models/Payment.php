<?php

namespace App\Core\Modules\Payment\Models;

use App\Core\Modules\Subscription\Models\Subscription;
use App\Core\Modules\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'subscription_id',
        'tenant_id',
        'amount',
        'method',
        'status',
        'proof_image',
        'gateway_ref',
        'confirmed_by',
        'confirmed_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'method' => 'string',
        'status' => 'string',
        'confirmed_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
