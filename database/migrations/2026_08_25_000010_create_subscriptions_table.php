<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('amount', 15, 2);
            $table->enum('cycle', ['monthly', 'yearly', 'weekly', 'custom'])->default('monthly');
            $table->json('cycle_custom')->nullable();
            $table->date('next_billing_date')->nullable();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->json('shared_with')->nullable();
            $table->date('trial_until')->nullable();
            $table->string('url')->nullable();
            $table->text('note')->nullable();
            $table->boolean('is_paused')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
