<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Family;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InitialDataSeeder extends Seeder
{
    /**
     * 建立預設系統管理員 (admin / admin) 與初始管理家庭。
     */
    public function run(): void
    {
        // 1. 系統管理員 (帳號: admin / 密碼: admin)
        $admin = User::firstOrCreate(
            ['account' => 'admin'],
            [
                'name' => '系統管理員',
                'email' => 'admin@example.com',
                'account' => 'admin',
                'password' => Hash::make('admin'),
                'is_system_admin' => true,
                'registration_role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // 確保密碼與管理員權限一致
        $admin->forceFill([
            'name' => '系統管理員',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin'),
            'is_system_admin' => true,
            'registration_role' => 'admin',
            'two_factor_enabled' => false,
            'email_verified_at' => now(),
        ])->save();

        // 2. 建立管理員專屬初始家庭
        $adminFamily = Family::firstOrCreate(
            ['name' => '管理員的家庭'],
            [
                'created_by_user_id' => $admin->id,
                'owner_user_id' => $admin->id,
                'currency' => 'TWD',
                'invite_code' => strtoupper(Str::random(6)),
                'total_pool_amount' => 0,
                'pool_currency' => 'TWD',
                'storage_quota_mb' => 500,
                'is_archived' => false,
            ]
        );

        if (empty($adminFamily->invite_code)) {
            $adminFamily->forceFill(['invite_code' => strtoupper(Str::random(6))])->save();
        }

        // 3. 設定成員關聯（管理員為家長身份）
        if (! $this->familyUserPivotExists($adminFamily->id, $admin->id)) {
            $adminFamily->members()->attach($admin->id, [
                'role' => 'parent',
                'display_name' => '系統管理員',
                'is_active' => true,
            ]);
        }

        // 4. 設定管理員目前作用中家庭
        $admin->forceFill(['current_family_id' => $adminFamily->id])->save();

        // 5. 建立初始日常現金帳戶
        Account::firstOrCreate(
            ['family_id' => $adminFamily->id, 'name' => '日常現金'],
            [
                'type' => 'cash',
                'balance' => 0.00,
                'currency' => 'TWD',
                'color' => '#10B981',
                'icon' => 'payments',
            ]
        );
    }

    /**
     * 檢查 family_user 是否已有對應紀錄。
     */
    private function familyUserPivotExists(int $familyId, int $userId): bool
    {
        return DB::table('family_user')
            ->where('family_id', $familyId)
            ->where('user_id', $userId)
            ->exists();
    }
}
