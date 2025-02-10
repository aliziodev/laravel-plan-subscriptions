<?php

namespace Aliziodev\PlanSubscription\Exceptions;

use Exception;

class UsageException extends Exception
{
    public static function usageLimitExceeded(string $metric): self
    {
        return new static("Usage limit exceeded for metric: {$metric}");
    }

    public static function invalidMetric(string $metric): self
    {
        return new static("Invalid metric: {$metric}");
    }

    public static function invalidAmount(int $amount): self
    {
        return new static("Invalid usage amount: {$amount}");
    }

    public static function metricNotFound(string $metric): self
    {
        return new static("Metric not found: {$metric}");
    }

    public static function cannotDecreaseUsage(string $metric): self
    {
        return new static("Cannot decrease usage below zero for metric: {$metric}");
    }
} 