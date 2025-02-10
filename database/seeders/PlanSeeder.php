<?php

namespace Aliziodev\PlanSubscription\Database\Seeders;

use Illuminate\Database\Seeder;
use Aliziodev\PlanSubscription\Models\Plan;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Trial',
                'slug' => 'trial',
                'description' => 'Plan trial untuk mencoba fitur dasar',
                'periods' => [
                    0 => [
                        'name' => 'Trial',
                        'price' => 0,
                        'discount' => 0
                    ]
                ],
                'limits' => [
                    'products' => 10,
                    'storage' => 1, // GB
                    'employees' => 2,
                    'users' => 1,
                    'materials' => 10
                ],
                'modules' => [],
                'grace_days' => 0,
                'is_popular' => false,
                'has_trial' => false, // trial plan tidak perlu trial period
                'trial_days' => 14,
                'is_active' => true
            ],
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'description' => 'Plan untuk bisnis kecil',
                'periods' => [
                    1 => [
                        'name' => '1 Bulan',
                        'price' => 99000,
                        'discount' => 0
                    ],
                    3 => [
                        'name' => '3 Bulan',
                        'price' => 297000,
                        'discount' => 10
                    ],
                    6 => [
                        'name' => '6 Bulan',
                        'price' => 594000,
                        'discount' => 15
                    ],
                    12 => [
                        'name' => '1 Tahun',
                        'price' => 1188000,
                        'discount' => 25
                    ]
                ],
                'limits' => [
                    'products' => 100,
                    'storage' => 5, // GB
                    'employees' => 5,
                    'users' => 3,
                    'materials' => 100
                ],
                'modules' => [],
                'grace_days' => config('plan-subscription.grace_days', 7),
                'is_popular' => false,
                'has_trial' => false, // trial sudah terpisah
                'trial_days' => 0,
                'is_active' => true
            ],
            [
                'name' => 'Professional',
                'slug' => 'pro',
                'description' => 'Plan untuk bisnis menengah',
                'periods' => [
                    1 => [
                        'name' => '1 Bulan',
                        'price' => 199000,
                        'discount' => 0
                    ],
                    3 => [
                        'name' => '3 Bulan',
                        'price' => 597000,
                        'discount' => 10
                    ],
                    6 => [
                        'name' => '6 Bulan',
                        'price' => 1194000,
                        'discount' => 15
                    ],
                    12 => [
                        'name' => '1 Tahun',
                        'price' => 2388000,
                        'discount' => 25
                    ]
                ],
                'limits' => [
                    'products' => 500,
                    'storage' => 20, // GB
                    'employees' => 20,
                    'users' => 10,
                    'materials' => 500
                ],
                'modules' => ['payroll'],
                'grace_days' => config('plan-subscription.grace_days', 7),
                'is_popular' => true,
                'has_trial' => false,
                'trial_days' => 0,
                'is_active' => true
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Plan untuk bisnis besar',
                'periods' => [
                    1 => [
                        'name' => '1 Bulan',
                        'price' => 499000,
                        'discount' => 0
                    ],
                    3 => [
                        'name' => '3 Bulan',
                        'price' => 1497000,
                        'discount' => 10
                    ],
                    6 => [
                        'name' => '6 Bulan',
                        'price' => 2994000,
                        'discount' => 15
                    ],
                    12 => [
                        'name' => '1 Tahun',
                        'price' => 5988000,
                        'discount' => 25
                    ]
                ],
                'limits' => [
                    'products' => -1, // unlimited
                    'storage' => 100, // GB
                    'employees' => -1, // unlimited
                    'users' => -1, // unlimited
                    'materials' => -1 // unlimited
                ],
                'modules' => ['payroll', 'auto_invoice'],
                'grace_days' => config('plan-subscription.grace_days', 7),
                'is_popular' => false,
                'has_trial' => false,
                'trial_days' => 0,
                'is_active' => true
            ]
        ];

        foreach ($plans as $plan) {
            Plan::create($plan);
        }
    }
}
