<?php

namespace Aliziodev\PlanSubscription\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Aliziodev\PlanSubscription\Models\Plan create(array $data)
 * @method static \Aliziodev\PlanSubscription\Models\Plan update(\Aliziodev\PlanSubscription\Models\Plan $plan, array $data)
 * @method static bool delete(\Aliziodev\PlanSubscription\Models\Plan $plan)
 * @method static bool deactivate(\Aliziodev\PlanSubscription\Models\Plan $plan)
 * @method static bool activate(\Aliziodev\PlanSubscription\Models\Plan $plan)
 * @method static \Aliziodev\PlanSubscription\Models\Plan|null findBySlug(string $slug)
 * @method static \Illuminate\Database\Eloquent\Collection getActivePlans()
 * @method static array comparePlans(array $planSlugs)
 * @method static int calculateYearlyDiscount(\Aliziodev\PlanSubscription\Models\Plan $plan)
 * @method static bool validateLimits(array $limits)
 * @method static bool validateModules(array $modules)
 */
class Plan extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'plan';
    }
}