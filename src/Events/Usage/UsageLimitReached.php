<?php

namespace Aliziodev\PlanSubscription\Events\Usage;

use Aliziodev\PlanSubscription\Models\Subscription;
use Illuminate\Queue\SerializesModels;

class UsageLimitReached
{
    use SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public string $metric,
        public int $currentUsage,
        public int $limit
    ) {
    }
} 