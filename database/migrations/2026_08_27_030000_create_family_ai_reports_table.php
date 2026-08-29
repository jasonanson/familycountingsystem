<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_ai_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->onDelete('cascade');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('month', 7); // e.g. '2026-08'
            $table->json('financial_metrics')->nullable();
            $table->longText('ai_report');
            $table->string('status')->default('sent'); // draft, reviewed, sent
            $table->unsignedInteger('sent_to_users_count')->default(0);
            $table->timestamps();

            $table->index(['family_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_ai_reports');
    }
};
