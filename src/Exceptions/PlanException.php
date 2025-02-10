<?php

namespace Aliziodev\PlanSubscription\Exceptions;

use Exception;

class PlanException extends Exception
{
    public static function planNotFound(string $slug): self
    {
        return new static("Plan not found: {$slug}");
    }

    public static function inactivePlan(string $slug): self
    {
        return new static("Plan is not active: {$slug}");
    }

    public static function invalidFeature(string $feature): self
    {
        return new static("Invalid feature: {$feature}");
    }

    public static function invalidLimits(array $limits): self
    {
        return new static('Invalid plan limits configuration');
    }

    public static function invalidPeriods(array $periods): self
    {
        return new static('Invalid plan periods configuration');
    }

    public static function cannotDeleteActivePlan(): self
    {
        return new self("Cannot delete plan with active subscriptions.");
    }

    public static function invalidPlanData(string $field): self
    {
        return new self("Invalid plan data: {$field}");
    }

    public static function invalidFeatureLimit(string $feature): self
    {
        return new self("Invalid limit value for feature '{$feature}'.");
    }

    public static function invalidModule(string $module): self
    {
        return new self("Invalid module '{$module}'. Module is not configured.");
    }
} 