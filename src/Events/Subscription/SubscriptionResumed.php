<?php

namespace Aliziodev\PlanSubscription\Events\Subscription;

use Aliziodev\PlanSubscription\Models\Subscription;
use Illuminate\Queue\SerializesModels;

class SubscriptionResumed
{
    use SerializesModels;

    public function __construct(public Subscription $subscription)
    {
    }
}