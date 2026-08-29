<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 註冊時使用者聲明的角色(待 FamilyUser 確認/調整)
            // parent: 自己聲明為家長(可建立家庭)
            // member: 自己聲明為成員(必須有有效邀請碼才能加入)
            // guest: 訪客/無角色(註冊後由家長指派)
            $table->enum('registration_role', ['parent', 'member', 'child', 'guest'])->nullable()->after('account');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('registration_role');
        });
    }
};