<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('limits');
            $table->json('modules')->nullable();
            $table->json('periods');
            $table->integer('grace_days')->default(config('plan-subscription.grace_days'));
            $table->boolean('is_popular')->default(false);
            $table->boolean('has_trial')->default(false);
            $table->integer('trial_days')->default(config('plan-subscription.trial_days'), 0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('plans');
    }
};
