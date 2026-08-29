<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->cascadeOnDelete();
            $table->enum('period_type', ['month', 'quarter', 'year', 'custom'])->default('month');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->enum('scope', ['family', 'category', 'user', 'group'])->default('family');
            $table->json('scope_target_ids')->nullable();
            $table->decimal('amount', 15, 2);
            $table->json('alert_thresholds')->nullable();
            $table->boolean('rollover')->default(false);
            $table->foreignId('parent_budget_id')->nullable()->constrained('budgets')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
