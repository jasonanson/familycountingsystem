<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['expense', 'income', 'transfer', 'split', 'refund', 'custom'])->default('expense');
            $table->string('type_custom')->nullable();
            $table->decimal('amount', 15, 2);
            $table->dateTime('occurred_at');
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('to_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('payee_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('payee_custom')->nullable();
            $table->string('description')->nullable();
            $table->text('note')->nullable();
            $table->json('attachment_ids')->nullable();
            $table->json('tag_ids')->nullable();
            $table->json('split_with')->nullable();
            $table->foreignId('recurring_rule_id')->nullable()->constrained('recurring_rules')->nullOnDelete();
            $table->foreignId('refunded_from_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->json('custom_fields')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
