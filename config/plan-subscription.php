<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here you can configure the database connections for the package.
    | Set to null to use default connection. false to disable central connection.
    | for model plan and subscriptions
    |
    */
    'tenancy_central_connection' => true,

    'subscribable_table' => 'tenants',
    'subscribable_model' => 'App\Models\Tenant',
    /*
    |--------------------------------------------------------------------------
    | Default Metrics
    |--------------------------------------------------------------------------
    |

    | Default metrics yang tersedia untuk batasan plan
    |
    */
    'default_metrics' => [
        'products',
        'storage',
        'employees',
        'users',
        'materials'
    ],

    /*
    |--------------------------------------------------------------------------
    | Available Modules
    |--------------------------------------------------------------------------
    |
    | Modul-modul yang tersedia untuk diaktifkan di plan
    |
    */
    'modules' => [
        'payroll',
        'auto_invoice'
    ],

    /*
    |--------------------------------------------------------------------------
    | Trial Periods
    |--------------------------------------------------------------------------
    |
    | Jumlah hari trial yang tersedia
    |
    */
    'trial_days' => 7,

    /*
    |--------------------------------------------------------------------------
    | Default Grace Period
    |--------------------------------------------------------------------------
    |

    | Jumlah hari tambahan setelah subscription berakhir sebelum 
    | layanan benar-benar dihentikan
    |
    */
    'grace_days' => 7,

    /*
    |--------------------------------------------------------------------------
    | Available Periods
    |--------------------------------------------------------------------------
    |
    | Periode berlangganan yang tersedia beserta diskon defaultnya
    |
    */
    'periods' => [
        1 => [
            'name' => '1 Month',
            'discount' => 0
        ],

        3 => [
            'name' => '3 Months',
            'discount' => 10
        ],

        6 => [
            'name' => '6 Months',
            'discount' => 15
        ],

        12 => [
            'name' => '1 Year',
            'discount' => 25
        ]
    ],


    
    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may define the cache store that should be used for subscription
    | Default is 'file'. Available options: 'redis', 'memcached', 'file', etc.
    |
    */
    'cache' => [
        'store' => env('SUBSCRIPTION_CACHE_STORE', 'file'),
        'ttl' => [
            'active' => 5, // minutes
            'history' => 60 // minutes
        ]
    ],

];
