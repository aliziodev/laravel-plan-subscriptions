<?php

namespace Aliziodev\PlanSubscription\Services;

use Aliziodev\PlanSubscription\Contracts\SubscriptionInterface;
use Aliziodev\PlanSubscription\Models\Plan;
use Aliziodev\PlanSubscription\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;
use Aliziodev\PlanSubscription\Exceptions\SubscriptionException;
use Aliziodev\PlanSubscription\Exceptions\UsageException;
use Aliziodev\PlanSubscription\Events\Subscription\SubscriptionCreated;
use Aliziodev\PlanSubscription\Events\Subscription\SubscriptionCanceled;
use Aliziodev\PlanSubscription\Events\Subscription\SubscriptionUpgraded;
use Aliziodev\PlanSubscription\Events\Subscription\SubscriptionEnteredGracePeriod;
use Aliziodev\PlanSubscription\Exceptions\InvalidPeriodException;
use Aliziodev\PlanSubscription\Events\Subscription\SubscriptionRenewed;
use Aliziodev\PlanSubscription\Events\Subscription\AutoRenewalFailed;
use Aliziodev\PlanSubscription\Events\Usage\{
    UsageRecorded,
    UsageReset,
    UsageLimitReached
};
use Illuminate\Support\Facades\Cache;

class SubscriptionService implements SubscriptionInterface
{
    /**
     * Get cache instance
     */
    protected function cache()
    {
        return Cache::store(config('plan-subscription.cache.store', 'file'));
    }

    /**
     * Get cache key for usage
     */
    protected function getUsageCacheKey($subscribable, string $metric): string
    {
        return "usage_{$subscribable->getMorphClass()}_{$subscribable->id}_{$metric}";
    }

    /**
     * Clear subscriber cache
     */
    protected function clearSubscriberCache($subscribable): void
    {
        $cacheKey = "subscription_active_{$subscribable->getMorphClass()}_{$subscribable->id}";
        $this->cache()->forget($cacheKey);
    }

    /**
     * Clear usage cache
     */
    protected function clearUsageCache($subscribable, string $metric): void
    {
        $this->cache()->forget($this->getUsageCacheKey($subscribable, $metric));
    }

    /**
     * Clear all usage cache
     */
    protected function clearAllUsageCache($subscribable): void
    {
        // Clear semua cache yang terkait usage
        $metrics = $subscribable->activeSubscription()?->plan_limits ?? [];
        foreach ($metrics as $metric => $limit) {
            $this->clearUsageCache($subscribable, $metric);
        }
    }

    // Core Subscription Methods
    public function startFreeTrial($subscribable, string $trialPlanSlug = 'trial'): Subscription
    {
        $plan = Plan::where('slug', $trialPlanSlug)->active()->firstOrFail();
        
        if ($subscribable->activeSubscription()) {
            throw SubscriptionException::alreadySubscribed();
        }

        return DB::transaction(function () use ($subscribable, $plan) {
            $startDate = now();
            $endDate = $startDate->copy()->addDays($plan->trial_days);
            
            $subscription = $subscribable->subscriptions()->create([
                'plan_id' => $plan->id,
                'plan_limits' => $plan->limits,
                'plan_modules' => $plan->modules,
                'period_months' => 0, // trial tidak memiliki period
                'price' => 0,
                'original_price' => 0,
                'period_discount' => 0,
                'status' => 'trialing',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'trial_end' => $endDate,
                'grace_days' => 0, // trial tidak memiliki grace period
                'grace_ends_at' => null
            ]);

            $this->clearSubscriberCache($subscribable);

            event(new SubscriptionCreated($subscription));
            
            return $subscription;
        });
    }

    public function subscribe($subscribable, string $planSlug, int $period = 1): Subscription
    {
        $plan = Plan::where('slug', $planSlug)->active()->firstOrFail();
        
        if (!$plan->is_active) {
            throw SubscriptionException::planNotActive();
        }

        $originalPrice = $plan->getPriceForPeriod($period);
        if (!$originalPrice) {
            throw InvalidPeriodException::invalidPeriod($period);
        }

        $periodDiscount = $plan->getDiscountForPeriod($period);
        $finalPrice = $originalPrice * (1 - ($periodDiscount / 100));

        return DB::transaction(function () use ($subscribable, $plan, $period, $originalPrice, $finalPrice, $periodDiscount) {
            // Cancel trial subscription jika ada
            $currentSub = $subscribable->activeSubscription();
            if ($currentSub && $currentSub->status === 'trialing') {
                $this->cancel($currentSub);
            }
            
            $startDate = now();
            $endDate = $startDate->copy()->addMonthsNoOverflow($period);
            
            $subscription = $subscribable->subscriptions()->create([
                'plan_id' => $plan->id,
                'plan_limits' => $plan->limits,
                'plan_modules' => $plan->modules,
                'period_months' => $period,
                'price' => $finalPrice,
                'original_price' => $originalPrice,
                'period_discount' => $periodDiscount,
                'status' => 'active',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'trial_end' => null, // tidak ada trial untuk subscription aktif
                'grace_days' => $plan->grace_days,
                'grace_ends_at' => $endDate->copy()->addDays($plan->grace_days),
                'previous_sub_id' => $currentSub?->id
            ]);

            $this->clearSubscriberCache($subscribable);

            event(new SubscriptionCreated($subscription));
            
            return $subscription;
        });
    }

    public function cancel(Subscription $subscription): bool
    {
        if ($subscription->isCanceled()) {
            throw SubscriptionException::alreadyCanceled();
        }

        $subscription->update([
            'status' => 'canceled',
            'canceled_at' => now()
        ]);

        $this->clearSubscriberCache($subscription->subscribable);
        event(new SubscriptionCanceled($subscription));

        return true;
    }

    public function upgrade(Subscription $subscription, string $newPlanSlug): Subscription
    {
        $newPlan = Plan::where('slug', $newPlanSlug)->active()->firstOrFail();
        
        if ($subscription->plan_id === $newPlan->id) {
            throw SubscriptionException::invalidUpgrade();
        }

        return DB::transaction(function () use ($subscription, $newPlan) {
            $this->cancel($subscription);
            $proratedCredit = $this->calculateProratedCredit($subscription);

            $newSubscription = $this->subscribe(
                $subscription->subscribable,
                $newPlan->slug,
                $subscription->period_months
            );

            $newSubscription->update([
                'prorated_credit' => $proratedCredit,
                'previous_sub_id' => $subscription->id
            ]);

            $this->clearSubscriberCache($subscription->subscribable);
           
            event(new SubscriptionUpgraded($newSubscription, $subscription));

            return $newSubscription;
        });
    }

    public function renew(Subscription $subscription, ?int $newPeriod = null): Subscription
    {
        if (!$subscription->canRenew()) {
            throw SubscriptionException::cannotRenew();
        }

        $period = $newPeriod ?? $subscription->period_months;
        $plan = $subscription->plan;

        $originalPrice = $plan->getPriceForPeriod($period);
        if (!$originalPrice) {
            throw InvalidPeriodException::invalidPeriod($period);
        }

        $periodDiscount = $plan->getDiscountForPeriod($period);
        $finalPrice = $originalPrice * (1 - ($periodDiscount / 100));

        return DB::transaction(function () use ($subscription, $period, $originalPrice, $finalPrice, $periodDiscount) {
            $newSubscription = $subscription->subscribable->subscriptions()->create([
                'plan_id' => $subscription->plan_id,
                'plan_limits' => $subscription->plan_limits,
                'plan_modules' => $subscription->plan_modules,
                'period_months' => $period,
                'price' => $finalPrice,
                'original_price' => $originalPrice,
                'period_discount' => $periodDiscount,
                'status' => 'active',
                'start_date' => $subscription->end_date,
                'end_date' => $subscription->end_date->addMonths($period),
                'grace_days' => $subscription->grace_days,
                'grace_ends_at' => $subscription->end_date->addMonths($period)->addDays($subscription->grace_days),
                'previous_sub_id' => $subscription->id
            ]);

            $subscription->update([
                'status' => 'canceled',
                'canceled_at' => now()
            ]);

            $this->clearSubscriberCache($subscription->subscribable);
           
            event(new SubscriptionRenewed($newSubscription, $subscription));

            return $newSubscription;
        });
    }

    public function autoRenew(Subscription $subscription): ?Subscription
    {
        try {
            if (!$subscription->shouldAutoRenew()) {
                return null;
            }

            return $this->renew($subscription);
        } catch (\Exception $e) {
           
            event(new AutoRenewalFailed($subscription, $e->getMessage()));
            return null;
        }
    }

    public function getRenewalPrice(Subscription $subscription, ?int $newPeriod = null): array
    {
        $period = $newPeriod ?? $subscription->period_months;
        $plan = $subscription->plan;

        $originalPrice = $plan->getPriceForPeriod($period);
        $periodDiscount = $plan->getDiscountForPeriod($period);
        $finalPrice = $originalPrice * (1 - ($periodDiscount / 100));

        return [
            'original_price' => $originalPrice,
            'discount_percentage' => $periodDiscount,
            'final_price' => $finalPrice
        ];
    }

    // Usage Methods
    public function checkUsage(Subscription $subscription, string $metric): array
    {
        $ttl = config('plan-subscription.cache.ttl.active', 5);
        return $this->cache()->remember(
            $this->getUsageCacheKey($subscription->subscribable, $metric),
            now()->addMinutes($ttl),
            function () use ($subscription, $metric) {
                $limit = $subscription->getFeatureLimit($metric);
                $usage = $subscription->usages()->where('metric', $metric)->first();
                $used = $usage ? $usage->used : 0;
                
                $usedPercentage = $limit !== -1 ? round(($used / $limit) * 100, 2) : 0;
                $remainingPercentage = $limit !== -1 ? round(100 - $usedPercentage, 2) : 100;

                return [
                    'limit' => $limit,
                    'used' => $used,
                    'remaining' => $limit === -1 ? -1 : max(0, $limit - $used),
                    'used_percentage' => $usedPercentage,
                    'remaining_percentage' => $remainingPercentage
                ];
            }
        );
    }

    public function increaseUsage(Subscription $subscription, string $metric, int $amount = 1): bool
    {
        if ($amount < 1) {
            throw UsageException::invalidAmount($amount);
        }

        $usage = $subscription->usages()->firstOrCreate(
            ['metric' => $metric],
            ['used' => 0]
        );

        $limit = $subscription->getFeatureLimit($metric);
        
        if ($limit !== -1) {
            $newUsage = $usage->used + $amount;
            if ($newUsage > $limit) {
                event(new UsageLimitReached($subscription, $metric, $newUsage, $limit));
                throw UsageException::usageLimitExceeded($metric);
            }

            if ($newUsage === $limit) {
                event(new UsageLimitReached($subscription, $metric, $newUsage, $limit));
            }
        }

        $usage->increment('used', $amount);
        $this->clearUsageCache($subscription->subscribable, $metric);

        event(new UsageRecorded(
            $subscription,
            $metric,
            $usage->used,
            $limit === -1 ? -1 : max(0, $limit - $usage->used)
        ));

        return true;
    }

    public function decreaseUsage(Subscription $subscription, string $metric, int $amount = 1): bool
    {
        if ($amount < 1) {
            throw UsageException::invalidAmount($amount);
        }

        $usage = $subscription->usages()->where('metric', $metric)->first();
        if (!$usage || $usage->used < $amount) {
            throw UsageException::cannotDecreaseUsage($metric);
        }

        $usage->decrement('used', $amount);
        $this->clearUsageCache($subscription->subscribable, $metric);

        return true;
    }

    public function resetUsage(Subscription $subscription, ?string $metric = null): bool
    {
        if ($metric) {
            $this->clearUsageCache($subscription->subscribable, $metric);
        } else {
            $this->clearAllUsageCache($subscription->subscribable);
        }

        $query = $subscription->usages();
        $query->update(['used' => 0]);

        event(new UsageReset($subscription, $metric));

        return true;
    }

    // Feature & Module Access
    public static function hasFeatureAccess(Subscription $subscription, string $feature): bool
    {
        try {
            $limit = $subscription->getFeatureLimit($feature);
            if ($limit === -1) return true;

            $usage = $subscription->usages()->where('metric', $feature)->first();
            return !$usage || $usage->used < $limit;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function hasModuleAccess(Subscription $subscription, string $module): bool
    {
        return $subscription->hasModule($module);
    }

    public static function getFeatureLimit(Subscription $subscription, string $feature): int
    {
        return $subscription->getFeatureLimit($feature);
    }

    // Helper Methods
    protected static function prepareSubscriptionData(Plan $plan, string $cycle): array
    {
        return [
            'plan_id' => $plan->id,
            'plan_limits' => $plan->limits,
            'plan_modules' => $plan->modules,
            'cycle' => $cycle,
            'price' => $cycle === 'yearly' ? $plan->yearly_price : $plan->monthly_price,
            'status' => $plan->has_trial ? 'trialing' : 'active',
            'start_date' => now(),
            'end_date' => self::calculateEndDate($plan, $cycle),
            'trial_end' => $plan->has_trial ? now()->addDays($plan->trial_days) : null
        ];
    }

    protected static function prepareUpgradeData(Subscription $oldSub, Plan $newPlan, float $proratedCredit): array
    {
        return [
            'plan_id' => $newPlan->id,
            'plan_limits' => $newPlan->limits,
            'plan_modules' => $newPlan->modules,
            'cycle' => $oldSub->cycle,
            'price' => $oldSub->cycle === 'yearly' ? $newPlan->yearly_price : $newPlan->monthly_price,
            'status' => 'active',
            'start_date' => now(),
            'end_date' => self::calculateEndDate($newPlan, $oldSub->cycle),
            'previous_sub_id' => $oldSub->id,
            'prorated_credit' => $proratedCredit
        ];
    }

    protected static function calculateEndDate(Plan $plan, string $cycle): Carbon
    {
        if ($plan->has_trial) {
            return now()->addDays($plan->trial_days);
        }

        return $cycle === 'yearly' ? now()->addYear() : now()->addMonth();
    }

    protected function calculateProratedCredit(Subscription $subscription): float
    {
        $remainingDays = now()->diffInDays($subscription->end_date);
        $totalDays = $subscription->start_date->diffInDays($subscription->end_date);
        
        if ($totalDays <= 0) return 0;

        return ($subscription->price / $totalDays) * $remainingDays;
    }

    // Validation Methods
    protected static function validateSubscription(Subscription $subscription): void
    {
        if (!$subscription->exists) {
            throw new Exception('Subscription tidak valid');
        }

        if (self::hasExpired($subscription)) {
            throw new Exception('Subscription telah kadaluarsa');
        }
    }

    protected static function validateFeatureAccess(Subscription $subscription, string $feature): void
    {
        if (!self::hasFeatureAccess($subscription, $feature)) {
            throw new Exception("Tidak memiliki akses ke fitur '{$feature}'");
        }
    }

    protected static function validateModuleAccess(Subscription $subscription, string $module): void
    {
        if (!self::hasModuleAccess($subscription, $module)) {
            throw new Exception("Tidak memiliki akses ke modul '{$module}'");
        }
    }

    protected function handleGracePeriod(Subscription $subscription): void
    {
        if (!$subscription->grace_ends_at && $subscription->end_date <= now()) {
            $subscription->update([
                'grace_ends_at' => now()->addDays($subscription->grace_days)
            ]);

            event(new SubscriptionEnteredGracePeriod($subscription));
        }
    }

    public static function isInGracePeriod(Subscription $subscription): bool
    {
        return $subscription->isInGracePeriod();
    }

    public static function daysLeftInGracePeriod(Subscription $subscription): int
    {
        return $subscription->daysLeftInGracePeriod();
    }

    public function canRenew(Subscription $subscription): bool
    {
        return $subscription->canRenew();
    }

    public function getRemainingDays(Subscription $subscription): int
    {
        return $subscription->remainingDays();
    }
}