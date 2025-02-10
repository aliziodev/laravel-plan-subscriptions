<?php

namespace Aliziodev\PlanSubscription\Contracts;

use Aliziodev\PlanSubscription\Models\Plan;
use Illuminate\Database\Eloquent\Collection;

interface PlanInterface
{
    public static function create(array $data): Plan;
    public static function update(Plan $plan, array $data): Plan;
    public static function delete(Plan $plan): bool;
    public static function deactivate(Plan $plan): bool;
    public static function activate(Plan $plan): bool;
    public static function setPopular(Plan $plan): bool;
    public static function unsetPopular(Plan $plan): bool;
    public static function findBySlug(string $slug): ?Plan;
    public static function findById(string $id): ?Plan;
    public static function getActivePlans(): Collection;
    public static function getAllPlans(): Collection;
} 