<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('child_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->cascadeOnDelete();
            $table->foreignId('child_user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('per_transaction_max', 15, 2)->nullable();
            $table->decimal('daily_max', 15, 2)->nullable();
            $table->decimal('weekly_max', 15, 2)->nullable();
            $table->decimal('monthly_max', 15, 2)->nullable();
            $table->decimal('ratio_of_pocket', 5, 2)->nullable();
            $table->json('overrides')->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_limits');
    }
};
