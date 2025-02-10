<?php

namespace Aliziodev\PlanSubscription\Services;

use Aliziodev\PlanSubscription\Contracts\PlanInterface;
use Aliziodev\PlanSubscription\Models\Plan;
use Aliziodev\PlanSubscription\Exceptions\PlanException;
use Illuminate\Database\Eloquent\Collection;

class PlanService implements PlanInterface
{
    public static function create(array $data): Plan
    {
        self::validatePlanData($data);
        return Plan::create($data);
    }

    public static function update(Plan $plan, array $data): Plan
    {
        if (!empty($data)) {
            self::validatePlanData($data, false);
        }
        $plan->update($data);
        return $plan->fresh();
    }

    public static function delete(Plan $plan): bool
    {
        if (self::hasActiveSubscriptions($plan)) {
            throw PlanException::cannotDeleteActivePlan();
        }
        return $plan->delete();
    }

    public static function deactivate(Plan $plan): bool
    {
        return $plan->update(['is_active' => false]);
    }

    public static function activate(Plan $plan): bool
    {
        return $plan->update(['is_active' => true]);
    }

    public static function setPopular(Plan $plan): bool
    {
        Plan::where('is_popular', true)->update(['is_popular' => false]);
        return $plan->update(['is_popular' => true]);
    }

    public static function unsetPopular(Plan $plan): bool
    {
        return $plan->update(['is_popular' => false]);
    }

    public static function findBySlug(string $slug): ?Plan
    {
        return Plan::where('slug', $slug)->first();
    }

    public static function findById(string $id): ?Plan
    {
        return Plan::find($id);
    }

    public static function getActivePlans(): Collection
    {
        return Plan::active()->get();
    }

    public static function getAllPlans(): Collection
    {
        return Plan::all();
    }

    protected static function validatePlanData(array $data, bool $isCreating = true): void
    {
        // Validate required fields for creation
        if ($isCreating) {
            if (empty($data['name'])) {
                throw PlanException::invalidPlanData('name is required');
            }
        }

        // Validate limits
        if (isset($data['limits'])) {
            $validMetrics = config('plan-subscription.default_metrics', []);
            foreach ($data['limits'] as $metric => $limit) {
                if (!in_array($metric, $validMetrics)) {
                    throw PlanException::invalidFeature($metric);
                }
                if (!is_numeric($limit)) {
                    throw PlanException::invalidLimits($data['limits']);
                }
            }
        }

        // Validate modules
        if (isset($data['modules'])) {
            $validModules = config('plan-subscription.modules', []);
            foreach ($data['modules'] as $module) {
                if (!in_array($module, $validModules)) {
                    throw PlanException::invalidModule($module);
                }
            }
        }

        // Validate periods
        if (isset($data['periods'])) {
            $validPeriods = array_keys(config('plan-subscription.periods', []));
            foreach ($data['periods'] as $period => $details) {
                if (!in_array($period, $validPeriods)) {
                    throw PlanException::invalidPeriods($data['periods']);
                }
                if (!isset($details['price']) || !is_numeric($details['price'])) {
                    throw PlanException::invalidPeriods($data['periods']);
                }
            }
        }
    }

    protected static function hasActiveSubscriptions(Plan $plan): bool
    {
        return $plan->subscriptions()
            ->whereIn('status', ['active', 'trialing'])
            ->where('end_date', '>', now())
            ->exists();
    }

    protected static function getPlanDetails(Plan $plan): array
    {
        return [
            'name' => $plan->name,
            'description' => $plan->description,
            'periods' => $plan->getAvailablePeriods(),
            'limits' => $plan->limits,
            'modules' => $plan->modules,
            'has_trial' => $plan->has_trial,
            'trial_days' => $plan->trial_days,
            'is_popular' => $plan->is_popular,
            'grace_days' => $plan->grace_days
        ];
    }
}