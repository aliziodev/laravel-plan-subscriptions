<?php

namespace Aliziodev\PlanSubscription\Contracts;

use Aliziodev\PlanSubscription\Models\Plan;
use Aliziodev\PlanSubscription\Models\Subscription;
use Illuminate\Database\Eloquent\{Collection, Relations\HasMany};


interface HasSubscriptionInterface
{
    // Relationship Methods
    public function subscriptions(): HasMany;
    
    // Core Subscription Methods
    public function startFreeTrial(string $trialPlanSlug = 'free-trial'): Subscription;
    public function subscribe(string $planSlug, int $period = 1): Subscription;
    public function cancelSubscription(): bool;
    public function upgradePlan(string $newPlanSlug): Subscription;
    public function renewSubscription(?int $newPeriod = null): Subscription;

    // Subscription Status Methods
    public function activeSubscription(): ?Subscription;
    public function subscribed(): bool;
    public function onTrial(): bool;
    public function canceled(): bool;
    public function canceledAndExpired(): bool;
    public function canceledButActive(): bool;
    
    // Usage Management Methods
    public function increaseUsage(string $metric, int $amount = 1): bool;
    public function decreaseUsage(string $metric, int $amount = 1): bool;
    public function resetUsage(?string $metric = null): bool;
    public function getUsage(string $metric): array;
    public function getAllUsage(): array;

    // Feature & Module Access Methods
    public function canUseMetric(string $metric): bool;
    public function canAccessModule(string $module): bool;
    public function getMetricLimit(string $metric): int;
    
    // Plan Information Methods
    public function currentPlan(): ?Plan;
    public function onPlan(string $planSlug): bool;
    
    // Subscription Details Methods

    public function remainingDays(): int;
    public function renewalDate(): ?string;
    public function trialDaysLeft(): int;
    public function getRenewalPrice(?int $newPeriod = null): array;
    
    // Grace Period Methods
    public function isInGracePeriod(): bool;
    public function gracePeriodEndsAt(): ?string;
    public function daysLeftInGracePeriod(): int;
    
    // History Methods
    public function getSubscriptionHistory(): Collection;
} 