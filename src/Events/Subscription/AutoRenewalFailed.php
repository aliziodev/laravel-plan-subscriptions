<?php

namespace Aliziodev\PlanSubscription\Events\Subscription;

use Aliziodev\PlanSubscription\Models\Subscription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AutoRenewalFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public ?string $reason = null
    ) {}
} 