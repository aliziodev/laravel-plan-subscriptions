<?php

namespace Aliziodev\PlanSubscription\Models;

use Aliziodev\PlanSubscription\Concerns\ManagesConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, MorphTo};
use Aliziodev\PlanSubscription\Exceptions\UsageException;

class Subscription extends Model
{
    use SoftDeletes, ManagesConnection;

    protected $fillable = [
        'subscribable_id',
        'plan_id',
        'plan_limits',
        'plan_modules',
        'period_months',


        'price',
        'original_price',
        'period_discount',
        'status',
        'start_date',
        'end_date',
        'trial_end',
        'canceled_at',
        'grace_ends_at',
        'grace_days',
        'previous_sub_id',
        'prorated_credit'
    ];

    protected $casts = [
        'plan_limits' => 'array',
        'plan_modules' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'trial_end' => 'date',
        'canceled_at' => 'date',
        'grace_ends_at' => 'date'
    ];

    // Relationships
    public function subscribable(): BelongsTo
    {
        return $this->belongsTo(config('plan-subscription.subscribable_model'), 'subscribable_id');
    }


    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(Usage::class);
    }

    public function previousSubscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'previous_sub_id');
    }

    public function renewalHistory(): HasMany
    {
        return $this->hasMany(Subscription::class, 'previous_sub_id');
    }

    // Status Methods
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->end_date > now();
    }

    public function isTrialing(): bool
    {
        return $this->status === 'trialing' && $this->trial_end > now();
    }

    public function isCanceled(): bool
    {
        return $this->status === 'canceled';
    }

    public function hasExpired(): bool
    {
        return $this->end_date <= now() && !$this->isInGracePeriod();
    }

    public function isInGracePeriod(): bool
    {
        return $this->grace_ends_at && $this->grace_ends_at > now();
    }

    public function hasGracePeriodExpired(): bool
    {
        return $this->grace_ends_at && $this->grace_ends_at <= now();
    }

    public function getRenewalPriceAttribute(): array
    {
        return app('subscription')->getRenewalPrice($this);
    }

    public function shouldAutoRenew(): bool
    {
        return $this->status === 'active' 
            && $this->end_date->subDays(7) <= now()
            && $this->end_date > now();
    }

    public function canRenew(): bool
    {
        return in_array($this->status, ['active', 'canceled'])
            && ($this->end_date > now() || $this->isInGracePeriod());
    }

    public function getFeatureLimit(string $feature): int
    {
        if (!isset($this->plan_limits[$feature])) {
            throw UsageException::metricNotFound($feature);
        }

        return $this->plan_limits[$feature];
    }

    public function hasModule(string $module): bool
    {
        return in_array($module, $this->plan_modules ?? []);
    }

    public function remainingDays(): int
    {
        return max(0, now()->diffInDays($this->end_date, false));
    }

    public function daysLeftInGracePeriod(): int
    {
        if (!$this->grace_ends_at) {
            return 0;
        }

        return max(0, now()->diffInDays($this->grace_ends_at, false));
    }
}
