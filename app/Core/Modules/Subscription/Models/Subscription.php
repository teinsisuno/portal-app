<?php

namespace App\Core\Modules\Subscription\Models;

use App\Core\Modules\Apps\Models\AppModel;
use App\Core\Modules\Payment\Models\Payment;
use App\Core\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    protected $fillable = [
        'tenant_id',
        'app_id',
        'plan',
        'status',
        'trial_ends_at',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'plan' => 'string',
        'status' => 'string',
        'trial_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(AppModel::class, 'app_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
