<?php

namespace Aliziodev\PlanSubscription\Exceptions;

use Exception;

class SubscriptionException extends Exception
{
    public static function alreadySubscribed(): self
    {
        return new static('Already have an active subscription');
    }

    public static function notSubscribed(): self
    {
        return new static('No active subscription found');
    }

    public static function alreadyCanceled(): self
    {
        return new static('Subscription is already canceled');
    }

    public static function invalidUpgrade(): self
    {
        return new static('Cannot upgrade to the same plan');
    }

    public static function cannotDowngrade(): self
    {
        return new static('Plan downgrades are not allowed');
    }

    public static function expired(): self
    {
        return new static('Subscription has expired');
    }

    public static function gracePeriodExpired(): self
    {
        return new static('Grace period has expired');
    }

    public static function featureNotAvailable(string $feature): self
    {
        return new self("Feature '{$feature}' is not available in current plan.");
    }

    public static function usageLimitExceeded(string $metric): self
    {
        return new static("Usage limit exceeded for metric: {$metric}");
    }

    public static function moduleNotAccessible(string $module): self
    {
        return new self("Module '{$module}' is not accessible in current plan.");
    }

    public static function invalidSubscriptionPeriod(): self
    {
        return new self("Invalid subscription period. End date must be after start date.");
    }

    public static function trialEnded(): self
    {
        return new self("Trial period has ended.");
    }
} 