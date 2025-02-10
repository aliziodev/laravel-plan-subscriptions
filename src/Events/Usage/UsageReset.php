<?php

namespace Aliziodev\PlanSubscription\Events\Usage;

use Aliziodev\PlanSubscription\Models\Subscription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UsageReset
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public ?string $metric = null
    ) {}
} 