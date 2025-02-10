<?php

namespace Aliziodev\PlanSubscription\Concerns;

trait ManagesConnection
{
    public function getConnectionName()
    {
        if (config('plan-subscription.tenancy_central_connection', true)) {
            if (trait_exists('Stancl\Tenancy\Database\Concerns\CentralConnection')) {
                return config('tenancy.database.central_connection', 'central');
            }
        }
        return parent::getConnectionName();
    }
} 