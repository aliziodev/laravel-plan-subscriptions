<?php

namespace Aliziodev\PlanSubscription\Events\Subscription;

use Aliziodev\PlanSubscription\Models\Subscription;
use Illuminate\Queue\SerializesModels;

class SubscriptionWillExpire
{
    use SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public int $daysLeft
    ) {
    }
} 