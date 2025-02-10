<?php

namespace Aliziodev\PlanSubscription\Events\Plan;

use Aliziodev\PlanSubscription\Models\Plan;
use Illuminate\Queue\SerializesModels;

class PlanCreated
{
    use SerializesModels;

    public function __construct(public Plan $plan)
    {
    }
} 