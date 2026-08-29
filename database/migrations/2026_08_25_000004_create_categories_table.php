<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->nullable()->constrained('families')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('icon', 50)->nullable();
            $table->string('color', 20)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_custom')->default(false);
            $table->enum('scope', ['family', 'personal'])->default('family');
            $table->enum('type', ['expense', 'income', 'both'])->default('expense');
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
