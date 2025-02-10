<?php

namespace Aliziodev\PlanSubscription\Exceptions;

use Exception;

class InvalidPeriodException extends Exception
{
    public static function invalidPeriod(int $period): self
    {
        return new static("Period {$period} is not valid for this plan");
    }

    public static function periodNotAvailable(int $period): self
    {
        return new static("Period {$period} is not available for this plan");
    }

    public static function invalidPricing(int $period): self
    {
        return new static("Price for period {$period} is not set");
    }
}
