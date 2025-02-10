<?php

namespace Aliziodev\PlanSubscription;

use Aliziodev\PlanSubscription\Console\InstallCommand;
use Aliziodev\PlanSubscription\Services\PlanService;
use Illuminate\Support\ServiceProvider;

class PlanSubscriptionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register Plan Service
        $this->app->bind('plan', function () {
            return new PlanService();
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/plan-subscription.php' => config_path('plan-subscription.php'),
            ], 'plan-subscription-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'plan-subscription-migrations');

            $this->publishes([
                __DIR__.'/../database/seeders' => database_path('seeders'),
            ], 'plan-subscription-seeders');
        }

        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/../config/plan-subscription.php', 'plan-subscription'
        );
    }
}
