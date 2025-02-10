<?php

namespace Aliziodev\PlanSubscription\Exceptions;

use Exception;

class ModuleException extends Exception
{
    public static function moduleNotAvailable(string $module): self
    {
        return new static("Module {$module} is not available in current plan");
    }

    public static function invalidModule(string $module): self
    {
        return new static("Invalid module: {$module}");
    }

    public static function moduleAccessDenied(string $module): self
    {
        return new static("Access denied to module: {$module}");
    }
} 