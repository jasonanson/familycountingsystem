<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 為 users 表新增 locale 欄位，支援個人化的介面語言偏好。
     * - NULL 表示使用全站預設（SystemSetting.default_locale 或 APP_LOCALE）。
     * - 合法值為 config('app.available_locales') 中列舉的代碼。
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 8)->nullable()->after('notification_preferences')
                ->comment('使用者偏好語系，NULL 表示採用全站預設');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
