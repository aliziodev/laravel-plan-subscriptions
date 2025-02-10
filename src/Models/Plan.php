<?php

namespace Aliziodev\PlanSubscription\Models;

use Aliziodev\PlanSubscription\Concerns\ManagesConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Aliziodev\PlanSubscription\Exceptions\PlanException;
use Aliziodev\PlanSubscription\Exceptions\InvalidPeriodException;


class Plan extends Model
{
    use SoftDeletes, ManagesConnection;


    protected $fillable = [
        'name',
        'slug',
        'description',
        'limits',
        'modules',
        'periods',
        'grace_days',
        'is_popular',
        'has_trial',
        'trial_days',
        'is_active'
    ];

    protected $casts = [
        'limits' => 'array',
        'modules' => 'array',
        'periods' => 'array',
        'is_popular' => 'boolean',
        'has_trial' => 'boolean',
        'trial_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeWithTrial(Builder $query): Builder
    {
        return $query->where('has_trial', true);
    }

    public function scopeOrderByPrice(Builder $query, string $cycle = 'monthly'): Builder
    {
        $column = $cycle === 'yearly' ? 'yearly_price' : 'monthly_price';
        return $query->orderBy($column);
    }

    public function getMonthlyPriceFormattedAttribute(): string
    {
        return number_format($this->monthly_price, 0, ',', '.');
    }

    public function getYearlyPriceFormattedAttribute(): string
    {
        return number_format($this->yearly_price, 0, ',', '.');
    }

    public function getPriceForPeriod(int $period): ?float
    {
        if (!isset($this->periods[$period])) {
            throw InvalidPeriodException::periodNotAvailable($period);
        }

        return $this->periods[$period]['price'] ?? null;
    }

    public function getDiscountForPeriod(int $period): float
    {
        return $this->periods[$period]['discount'] ?? 0;
    }

    public function getAvailablePeriods(): array
    {
        return collect($this->periods)
            ->filter(fn($period) => $period['price'] > 0)
            ->toArray();
    }

    public function hasFeature(string $feature): bool
    {
        return isset($this->limits[$feature]);
    }

    public function getFeatureLimit(string $feature): int
    {
        if (!$this->hasFeature($feature)) {
            throw PlanException::invalidFeature($feature);
        }

        return $this->limits[$feature];
    }

    public function hasModule(string $module): bool
    {
        return in_array($module, $this->modules ?? []);
    }

    public function isUnlimited(string $feature): bool
    {
        return $this->getFeatureLimit($feature) === -1;
    }

    public function isPopular()
    {
        return $this->slug === config('subscription.popular_plan');
    }
}
