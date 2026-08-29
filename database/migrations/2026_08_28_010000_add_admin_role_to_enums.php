<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 在 users.registration_role 與 family_user.role 兩個 enum 中加入 'admin'。
     *
     * 用途：
     *   - users.registration_role = 'admin'
     *       系統管理員角色（在表單上或後台指派時使用）。
     *   - family_user.role = 'admin'
     *       當 admin 透過家長邀請或被指派加入某家庭時的樞紐角色。
     */
    public function up(): void
    {
        // users.registration_role
        DB::statement("ALTER TABLE users MODIFY COLUMN registration_role ENUM('admin','parent','member','child','guest') NULL");

        // family_user.role
        DB::statement("ALTER TABLE family_user MODIFY COLUMN role ENUM('admin','parent','child','guest') DEFAULT 'parent'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN registration_role ENUM('parent','member','child','guest') NULL");
        DB::statement("ALTER TABLE family_user MODIFY COLUMN role ENUM('parent','child','guest') DEFAULT 'parent'");
    }
};
