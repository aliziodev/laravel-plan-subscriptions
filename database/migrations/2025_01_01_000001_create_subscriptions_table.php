<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('subscribable_id', 255)->unique();
            $table->foreignId('plan_id')->constrained();
            $table->json('plan_limits');
            $table->json('plan_modules')->nullable();


            // Period dan harga
            $table->integer('period_months');
            $table->decimal('price', 10, 2);
            $table->decimal('original_price', 10, 2);
            $table->decimal('period_discount', 10, 2)->default(0);

            // Status dan tanggal
            $table->enum('status', ['active', 'trialing', 'canceled'])->default('trialing');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('trial_end')->nullable();
            $table->date('canceled_at')->nullable();

            // Grace period (dari config)
            $table->date('grace_ends_at')->nullable();
            $table->integer('grace_days')->default(config('plan-subscription.grace_days', 7));

            // Renewal info
            $table->foreignId('previous_sub_id')->nullable()->constrained('subscriptions');
            $table->decimal('prorated_credit', 10, 2)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('subscribable_id')
                ->references('id')
                ->on(config('plan-subscription.subscribable_table', 'tenants'))
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('subscriptions');
        Schema::enableForeignKeyConstraints();
    }
};
