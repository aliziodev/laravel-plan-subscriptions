<?php

namespace Aliziodev\PlanSubscription\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature = 'subscriptions:install 
        {--seed : Seed the database with default plans}
        {--path= : Custom path for migrations (e.g., database/migrations/tenant)}';
    
    protected $description = 'Install the subscription package';

    public function handle()
    {
        $this->info('Installing Subscription Package...');

        $this->publishConfig();
        $this->copyMigrations();
        
        if ($this->confirm('Would you like to run the migrations?', true)) {
            $this->runMigrations();

            if ($this->option('seed') || $this->confirm('Would you like to seed the plans table?', true)) {
                $this->seedPlans();
            }
        }

        $this->info('Installation completed!');
    }

    protected function publishConfig()
    {
        $this->info('Publishing configuration...');
        
        $this->call('vendor:publish', [
            '--provider' => 'Aliziodev\PlanSubscription\PlanSubscriptionServiceProvider',
            '--tag' => 'plan-subscription-config'
        ]);

        // Memastikan config file ada
        if (!File::exists(config_path('plan-subscription.php'))) {
            $this->error('Configuration file not published! Please check your provider registration.');
            return;
        }

        $this->info('Configuration published successfully.');
    }

    protected function copyMigrations()
    {
        $this->info('Starting to copy migrations...');

        $targetPath = $this->getMigrationPath();

        if (!File::exists($targetPath)) {
            File::makeDirectory($targetPath, 0755, true);
        }

        $migrationFiles = [
            __DIR__.'/../../database/migrations/2025_01_01_000000_create_plans_table.php',
            __DIR__.'/../../database/migrations/2025_01_01_000001_create_subscriptions_table.php',
            __DIR__.'/../../database/migrations/2025_01_01_000002_create_subscription_usages_table.php'
        ];

        foreach ($migrationFiles as $file) {
            if (File::exists($file)) {
                $filename = basename($file);
                $newTimestamp = date('Y_m_d_His', time() + array_search($file, $migrationFiles));
                $newFilename = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}/', $newTimestamp, $filename);
                
                if (File::exists($targetPath . '/' . $newFilename)) {
                    $this->warn("Migration {$newFilename} already exists, skipping...");
                    continue;
                }

                File::copy($file, $targetPath . '/' . $newFilename);
                $this->info("Migration {$newFilename} copied successfully.");
            } else {
                $this->error("Migration file not found: {$file}");
            }
        }
    }

    protected function getMigrationPath(): string
    {
        if ($path = $this->option('path')) {
            return base_path($path);
        }

        $choices = [
            'database/migrations' => base_path('database/migrations'),
            'database/migrations/tenant' => base_path('database/migrations/tenant'),
            'custom' => 'Enter custom path'
        ];

        $choice = $this->choice(
            'Where would you like to place the migrations?',
            array_keys($choices),
            0
        );

        if ($choice === 'custom') {
            $customPath = $this->ask('Enter the custom path (relative to project root)');
            return base_path($customPath);
        }

        return $choices[$choice];
    }

    protected function runMigrations()
    {
        $this->info('Running migrations...');
        
        $path = $this->option('path');
        $command = ['--force' => true];
        
        if ($path) {
            $command['--path'] = $path;
        }

        $this->call('migrate', $command);
        $this->info('Migrations completed successfully.');
    }

    protected function seedPlans()
    {
        $this->info('Seeding plans...');
        
        $this->call('db:seed', [
            '--class' => 'Aliziodev\PlanSubscription\Database\Seeders\PlanSeeder',
            '--force' => true
        ]);
        
        $this->info('Plans seeded successfully.');
    }
} 