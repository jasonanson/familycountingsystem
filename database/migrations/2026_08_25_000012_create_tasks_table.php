<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->cascadeOnDelete();
            $table->string('name');
            $table->enum('reward_type', ['fixed', 'flexible', 'custom'])->default('fixed');
            $table->decimal('reward_amount', 15, 2)->nullable();
            $table->string('reward_custom')->nullable();
            $table->foreignId('assignee_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pending', 'reported', 'approved', 'rejected'])->default('pending');
            $table->date('due_date')->nullable();
            $table->dateTime('reported_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reject_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
