<?php

namespace Aliziodev\PlanSubscription\Models;

use Aliziodev\PlanSubscription\Concerns\ManagesConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Usage extends Model
{
    use ManagesConnection;

    protected $table = 'subscription_usages';

    protected $fillable = [
        'subscription_id',
        'metric',
        'used',
        'reset_date'
    ];

    protected $casts = [
        'reset_date' => 'datetime'
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function scopeForMetric($query, string $metric)
    {
        return $query->where('metric', $metric);
    }
}