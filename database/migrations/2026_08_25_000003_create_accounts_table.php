<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['cash', 'bank', 'credit', 'ewallet', 'custom'])->default('cash');
            $table->string('type_custom')->nullable();
            $table->decimal('balance', 15, 2)->default(0);
            $table->string('currency', 10)->default('TWD');
            $table->string('color', 20)->nullable();
            $table->string('icon', 50)->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
