<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('subscription_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained();
            $table->string('metric');
            $table->integer('used')->default(0);
            $table->date('reset_date')->nullable();
            $table->timestamps();

            $table->unique(['subscription_id', 'metric']);
        });
    }

    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('subscription_usages');
        Schema::enableForeignKeyConstraints();
    }
};