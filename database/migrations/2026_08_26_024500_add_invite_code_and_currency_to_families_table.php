<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('families', function (Blueprint $table) {
            // 加入邀請碼欄位(供 AuthController 註冊流程用)
            $table->string('invite_code', 12)->nullable()->unique()->after('name');

            // 加入貨幣欄位(AuthController create() 用到)
            // 原本已有 pool_currency,這個是單純的 currency(向後相容舊的 AuthController 邏輯)
            $table->string('currency', 10)->default('TWD')->after('invite_code');
        });
    }

    public function down(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->dropUnique(['invite_code']);
            $table->dropColumn(['invite_code', 'currency']);
        });
    }
};