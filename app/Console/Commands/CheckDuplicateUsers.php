<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckDuplicateUsers extends Command
{
    protected $signature = 'users:check-duplicates';

    protected $description = '檢查 users 表中重複的 email 或 account,並提示管理員修正';

    public function handle(): int
    {
        $this->info('🔍 開始檢查 users 表的重複資料...');
        $this->newLine();

        // 1. 檢查 email 重複
        $emailDuplicates = User::query()
            ->select('email', DB::raw('COUNT(*) as cnt'))
            ->groupBy('email')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($emailDuplicates->isEmpty()) {
            $this->info('✓ 沒有重複的 email');
        } else {
            $this->warn('⚠ 發現 ' . $emailDuplicates->count() . ' 個重複的 email:');
            foreach ($emailDuplicates as $dup) {
                $users = User::where('email', $dup->email)->get();
                $this->line("  email: {$dup->email} (共 {$dup->cnt} 筆)");
                foreach ($users as $u) {
                    $this->line("    - ID:{$u->id} name:{$u->name} account:{$u->account}");
                }
            }
        }

        // 2. 檢查 account 重複
        $accountDuplicates = User::query()
            ->select('account', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('account')
            ->groupBy('account')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($accountDuplicates->isEmpty()) {
            $this->info('✓ 沒有重複的 account');
        } else {
            $this->warn('⚠ 發現 ' . $accountDuplicates->count() . ' 個重複的 account:');
            foreach ($accountDuplicates as $dup) {
                $users = User::where('account', $dup->account)->get();
                $this->line("  account: {$dup->account} (共 {$dup->cnt} 筆)");
                foreach ($users as $u) {
                    $this->line("    - ID:{$u->id} name:{$u->name} email:{$u->email}");
                }
            }
        }

        $this->newLine();
        $total = User::count();
        $this->info("總用戶數: {$total}");
        $this->info('提示:管理員可到 /admin/users 查看/編輯用戶');

        return self::SUCCESS;
    }
}