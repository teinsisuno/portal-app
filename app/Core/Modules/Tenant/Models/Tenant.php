<?php

namespace App\Core\Modules\Tenant\Models;

use App\Core\Modules\Payment\Models\Payment;
use App\Core\Modules\Subscription\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'address',
        'status',
        'created_by',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_user')->withPivot('role');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
