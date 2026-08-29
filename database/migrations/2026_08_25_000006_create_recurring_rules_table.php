<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->cascadeOnDelete();
            $table->enum('type', ['subscription', 'bill', 'income'])->default('subscription');
            $table->json('template');
            $table->enum('cycle', ['monthly', 'yearly', 'weekly', 'custom'])->default('monthly');
            $table->json('cycle_custom')->nullable();
            $table->dateTime('next_run_at')->nullable();
            $table->dateTime('last_run_at')->nullable();
            $table->json('alert_days_before')->nullable();
            $table->boolean('auto_create')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_rules');
    }
};
