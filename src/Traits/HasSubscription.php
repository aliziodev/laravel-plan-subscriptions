<?php

namespace Aliziodev\PlanSubscription\Traits;

use Aliziodev\PlanSubscription\Models\{Plan, Subscription};
use Illuminate\Database\Eloquent\{Collection, Relations\HasMany};
use Aliziodev\PlanSubscription\Services\SubscriptionService;
use Aliziodev\PlanSubscription\Exceptions\SubscriptionException;
use Illuminate\Support\Facades\Cache;


trait HasSubscription
{
    // Relationship Methods
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'subscribable_id');
    }

    /**
     * Get cache instance
     */
    protected function cache()
    {
        return Cache::store(config('plan-subscription.cache.store', 'file'));
    }

    /**
     * Get cache key prefix for subscription
     */
    protected function getSubscriptionCacheKey(string $key): string
    {
        return "subscription_{$key}_{$this->getMorphClass()}_{$this->id}";
    }

    /**
     * Clear all subscription cache for this model
     */
    protected function clearSubscriptionCache(): void
    {
        $this->cache()->forget($this->getSubscriptionCacheKey('active'));
        $this->cache()->forget($this->getSubscriptionCacheKey('history'));
    }

    // Core Subscription Methods
    public function subscribe(string $planSlug, int $period = 1): Subscription
    {
        $subscription = app(SubscriptionService::class)->subscribe($this, $planSlug, $period);
        $this->clearSubscriptionCache();
        return $subscription;
    }

    public function cancelSubscription(): bool
    {
        $result = app(SubscriptionService::class)->cancel($this->activeSubscription());
        $this->clearSubscriptionCache();
        return $result;
    }

    public function upgradePlan(string $newPlanSlug): Subscription
    {
        $subscription = app(SubscriptionService::class)->upgrade($this->activeSubscription(), $newPlanSlug);
        $this->clearSubscriptionCache();
        return $subscription;
    }

    public function renewSubscription(?int $newPeriod = null): Subscription
    {
        $subscription = app(SubscriptionService::class)->renew($this->activeSubscription(), $newPeriod);
        $this->clearSubscriptionCache();
        return $subscription;
    }

    public function startFreeTrial(string $trialPlanSlug = 'trial'): Subscription
    {
        return app(SubscriptionService::class)->startFreeTrial($this, $trialPlanSlug);
    }

    // Subscription Status Methods
    public function activeSubscription(): ?Subscription
    {
        $ttl = config('plan-subscription.cache.ttl.active', 5);

        return $this->cache()->remember(
            $this->getSubscriptionCacheKey('active'),
            now()->addMinutes($ttl),
            fn() => $this->subscriptions()
                ->whereIn('status', ['active', 'trialing'])
                ->where('end_date', '>', now())
                ->latest()
                ->first()
        );
    }

    public function subscribed(): bool
    {
        return (bool) $this->activeSubscription();
    }

    public function onTrial(): bool
    {
        $subscription = $this->activeSubscription();
        return $subscription && $subscription->isTrialing();
    }

    public function canceled(): bool
    {
        $subscription = $this->activeSubscription();
        return $subscription && $subscription->isCanceled();
    }

    public function canceledAndExpired(): bool
    {
        $subscription = $this->activeSubscription();
        return !$subscription || ($subscription->isCanceled() && $subscription->hasExpired());
    }

    public function canceledButActive(): bool
    {
        $subscription = $this->activeSubscription();
        return $subscription && $subscription->isCanceled() && !$subscription->hasExpired();
    }

    // Usage Management Methods
    public function increaseUsage(string $metric, int $amount = 1): bool
    {
        $subscription = $this->activeSubscription();
        if (!$subscription) {
            throw SubscriptionException::notSubscribed();
        }

        return app(SubscriptionService::class)->increaseUsage($subscription, $metric, $amount);
    }

    public function decreaseUsage(string $metric, int $amount = 1): bool
    {
        $subscription = $this->activeSubscription();
        if (!$subscription) {
            throw SubscriptionException::notSubscribed();
        }

        return app(SubscriptionService::class)->decreaseUsage($subscription, $metric, $amount);
    }

    public function resetUsage(?string $metric = null): bool
    {
        $subscription = $this->activeSubscription();
        if (!$subscription) {
            throw SubscriptionException::notSubscribed();
        }

        return app(SubscriptionService::class)->resetUsage($subscription, $metric);
    }

    /**
     * Get all usage metrics for active subscription
     */
    public function getAllUsage(): array
    {
        $subscription = $this->activeSubscription();
        if (!$subscription) {
            throw SubscriptionException::notSubscribed();
        }

        $usages = [];
        foreach ($subscription->plan_limits as $metric => $limit) {
            $usages[$metric] = app(SubscriptionService::class)->checkUsage($subscription, $metric);
        }

        return $usages;
    }

    public function getUsage(string $metric): array
    {
        $subscription = $this->activeSubscription();
        if (!$subscription) {
            throw SubscriptionException::notSubscribed();
        }

        return app(SubscriptionService::class)->checkUsage($subscription, $metric);
    }

    // Feature & Module Access Methods
    public function canUseMetric(string $metric): bool
    {
        $subscription = $this->activeSubscription();
        return $subscription && app(SubscriptionService::class)->hasFeatureAccess($subscription, $metric);
    }

    public function canAccessModule(string $module): bool
    {
        $subscription = $this->activeSubscription();
        return $subscription && app(SubscriptionService::class)->hasModuleAccess($subscription, $module);
    }

    public function getMetricLimit(string $metric): int
    {
        $subscription = $this->activeSubscription();
        if (!$subscription) {

            throw SubscriptionException::notSubscribed();
        }

        return app(SubscriptionService::class)->getMetricLimit($subscription, $metric);
    }

    // Plan Information Methods
    public function currentPlan(): ?Plan
    {
        $subscription = $this->activeSubscription();
        return $subscription ? $subscription->plan : null;
    }

    public function onPlan(string $planSlug): bool
    {
        $subscription = $this->activeSubscription();
        return $subscription && $subscription->plan->slug === $planSlug;
    }

    // Subscription Details Methods
    public function remainingDays(): int
    {
        $subscription = $this->activeSubscription();
        return $subscription ? $subscription->remainingDays() : 0;
    }

    public function renewalDate(): ?string
    {
        $subscription = $this->activeSubscription();
        return $subscription ? $subscription->end_date->format('Y-m-d') : null;
    }

    public function trialDaysLeft(): int
    {
        $subscription = $this->activeSubscription();
        return $subscription ? $subscription->trialDaysLeft() : 0;
    }

    public function getRenewalPrice(?int $newPeriod = null): array
    {
        $subscription = $this->activeSubscription();
        if (!$subscription) {
            throw SubscriptionException::notSubscribed();
        }

        return app(SubscriptionService::class)->getRenewalPrice($subscription, $newPeriod);
    }

    // Grace Period Methods
    public function isInGracePeriod(): bool
    {
        $subscription = $this->activeSubscription();
        return $subscription && $subscription->isInGracePeriod();
    }

    public function gracePeriodEndsAt(): ?string
    {
        $subscription = $this->activeSubscription();
        return $subscription && $subscription->grace_ends_at
            ? $subscription->grace_ends_at->format('Y-m-d')
            : null;
    }

    public function daysLeftInGracePeriod(): int
    {
        $subscription = $this->activeSubscription();
        return $subscription ? $subscription->daysLeftInGracePeriod() : 0;
    }

    // History Methods
    public function getSubscriptionHistory(): Collection
    {
        $ttl = config('plan-subscription.cache.ttl.history', 60);

        return $this->cache()->remember(
            $this->getSubscriptionCacheKey('history'),
            now()->addMinutes($ttl),
            fn() => $this->subscriptions()
                ->with('plan')
                ->latest()
                ->get()
        );
    }
}
