<?php

namespace App\Core\Modules\Apps\Models;

use App\Core\Modules\Subscription\Models\Subscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppModel extends Model
{
    protected $table = 'apps';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'price_monthly',
        'status',
        'logo',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'status' => 'string',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'app_id');
    }
}
