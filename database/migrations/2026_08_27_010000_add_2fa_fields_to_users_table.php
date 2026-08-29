<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2FA 補強欄位
 *
 *   two_factor_enabled        — 使用者是否啟用 2FA
 *   two_factor_confirmed_at   — 啟用時間（純稽核用）
 *   two_factor_recovery_codes — JSON 陣列，10 組一次性恢復碼（已 hash）
 *
 * 注意：
 *   - two_factor_secret 欄位在 users 表既已存在（最初版遷移），沿用不變
 *   - 已啟用 2FA 但 recovery_codes 為 null 時，視同「舊用戶未產生恢復碼」，
 *     登入流程會在 require 2FA 階段提示他重新產生
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('two_factor_enabled')->default(false)->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_enabled');
            $table->json('two_factor_recovery_codes')->nullable()->after('two_factor_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_enabled', 'two_factor_confirmed_at', 'two_factor_recovery_codes']);
        });
    }
};
