<?php

namespace Aliziodev\PlanSubscription\Contracts;

use Aliziodev\PlanSubscription\Models\Subscription;

interface SubscriptionInterface
{
    // Core Subscription Methods
    public function subscribe($subscribable, string $planSlug, int $period = 1): Subscription;
    public function cancel(Subscription $subscription): bool;
    public function upgrade(Subscription $subscription, string $newPlanSlug): Subscription;
    public function renew(Subscription $subscription, ?int $newPeriod = null): Subscription;
    public function autoRenew(Subscription $subscription): ?Subscription;
    
    // Usage Methods
    public function checkUsage(Subscription $subscription, string $metric): array;
    public function increaseUsage(Subscription $subscription, string $metric, int $amount = 1): bool;
    public function decreaseUsage(Subscription $subscription, string $metric, int $amount = 1): bool;
    public function resetUsage(Subscription $subscription, string $metric = null): bool;
    
    // Access Control Methods
    public static function hasFeatureAccess(Subscription $subscription, string $feature): bool;
    public static function hasModuleAccess(Subscription $subscription, string $module): bool;
    public static function getFeatureLimit(Subscription $subscription, string $feature): int;
    
    // Pricing Methods
    public function getRenewalPrice(Subscription $subscription, ?int $newPeriod = null): array;
    
    // Grace Period Methods
    public static function isInGracePeriod(Subscription $subscription): bool;
    public static function daysLeftInGracePeriod(Subscription $subscription): int;
} 