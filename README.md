Laravel Subscriptions
====================

Paket manajemen subscription yang powerful untuk aplikasi Laravel dengan fitur-fitur:
- Plan & Feature Management
- Subscription Lifecycle
- Per-seat Billing
- Proration Support
- Trial Management
- Payment Gateway Integration Support

Instalasi
---------

1. Install via composer:
   composer require aliziodev/laravel-subscriptions

2. Jalankan installer:
   php artisan subscriptions:install

Penggunaan Dasar
---------------

1. Setup Model:
   Tambahkan trait HasSubscription ke model Tenant:
   
   use Aliziodev\Subscription\Traits\HasSubscription;
   
   class Tenant extends Model
   {
       use HasSubscription;
   }

2. Membuat Plan:
   
   use Aliziodev\Subscription\Models\Plan;
   use Aliziodev\Subscription\Models\Feature;
   
   // Buat Plan
   $plan = Plan::create([
       'name' => 'Pro Plan',
       'slug' => 'pro-plan',
       'price' => 99.99,
       'billing_cycle' => 'monthly',
       'trial_days' => 14
   ]);
   
   // Tambah Feature
   $feature = Feature::create([
       'name' => 'Max Users',
       'slug' => 'max-users',
       'type' => 'limit'
   ]);
   
   $plan->features()->attach($feature, ['value' => '10']);

3. Subscribe ke Plan:
   
   use Aliziodev\Subscription\Facades\Subscription;
   
   // Basic subscription
   $subscription = Subscription::subscribe($tenant, $plan);
   
   // Dengan opsi
   $subscription = Subscription::subscribe($tenant, $plan, [
       'trial_days' => 30,
       'metadata' => ['ref' => 'campaign_123']
   ]);

4. Cek Subscription:
   
   // Cek status subscription
   if ($tenant->subscribed()) {
       // Tenant memiliki subscription aktif
   }
   
   // Cek trial
   if ($tenant->onTrial()) {
       // Tenant dalam masa trial
   }
   
   // Cek fitur
   if ($tenant->hasFeature('max-users')) {
       // Tenant memiliki akses ke fitur
   }
   
   // Get nilai fitur
   $maxUsers = $tenant->getFeatureValue('max-users');

5. Switch Plan:
   
   $subscription = Subscription::switchPlan(
       $tenant->subscription,
       $newPlan,
       ['immediately' => true]
   );

6. Cancel Subscription:
   
   Subscription::cancel($tenant->subscription);
   
   // Cancel immediately
   Subscription::cancel($tenant->subscription, ['immediately' => true]);

Events
------

Package ini menyediakan beberapa events:
- SubscriptionCreated
- SubscriptionCancelled
- SubscriptionSwitched
- SubscriptionExpiring

Payment Gateway Integration
-------------------------

Implement PaymentGatewayInterface untuk integrasi payment gateway:

use Aliziodev\Subscription\Contracts\PaymentGatewayInterface;

class StripeGateway implements PaymentGatewayInterface
{
    public function charge(Subscription $subscription, array $options = [])
    {
        // Implementasi charge
    }
    
    public function refund(Subscription $subscription)
    {
        // Implementasi refund
    }
}

Configuration
------------

1. Publish config file:
   php artisan vendor:publish --tag=subscription-config

2. Konfigurasi Utama:
   
   return [
       'defaults' => [
           'trial_days' => 7,
           'proration' => true,
       ],
       'extensions' => [
           'payment' => [
               'gateway' => \App\Services\StripeGateway::class,
           ]
       ]
   ];

Testing
-------

composer test

Security
--------

Jika Anda menemukan masalah keamanan, silakan email ke security@alizio.dev

License
-------

The MIT License (MIT) 