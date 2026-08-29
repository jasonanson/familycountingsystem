<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 將資料表中的 heroicon-o-* icon 名稱轉換為 Material Symbols Outlined 等價物。
     *
     * 背景：原本 CategorySeeder 與帳戶建立流程使用 heroicon-o-* 命名,
     * 但前端 <span class="material-symbols-outlined"> 載入的是 Material Symbols 字型。
     * 結果:瀏覽器把 heroicon-o-cutlery 當成 Material Symbols ligature 找不到,
     *      退而顯示純文字(ICON-CUTLERY 之類),與卡片標題重疊。
     *
     * 修法:
     *   1. 對所有 categories.icon 與 accounts.icon 套用映射表。
     *   2. 若有未列入映射表的 heroicon-o-* 名稱,自動轉換為 Material Symbols 命名規則
     *      (去前綴、dash → underscore)。
     */
    public function up(): void
    {
        // 完整映射表 (從實際資料庫內容列出)
        $iconMap = [
            'heroicon-o-cutlery'             => 'restaurant',
            'heroicon-o-truck'               => 'local_shipping',
            'heroicon-o-home'                => 'home',
            'heroicon-o-film'                => 'movie',
            'heroicon-o-shopping-bag'        => 'shopping_bag',
            'heroicon-o-heart'               => 'favorite',
            'heroicon-o-academic-cap'        => 'school',
            'heroicon-o-arrow-path'          => 'autorenew',
            'heroicon-o-ellipsis-horizontal' => 'more_horiz',
            'heroicon-o-banknotes'           => 'payments',
            'heroicon-o-sparkles'            => 'auto_awesome',
            'heroicon-o-gift'                => 'redeem',
            'heroicon-o-chart-bar'           => 'bar_chart',
            'heroicon-o-building-library'    => 'account_balance',
            'heroicon-o-credit-card'         => 'credit_card',
        ];

        // 自動 fallback: 把沒在表內的 heroicon-o-* 名稱轉成 Material Symbols 命名
        $autoConvert = function ($iconName) use ($iconMap) {
            if (empty($iconName)) return $iconName;
            if (isset($iconMap[$iconName])) return $iconMap[$iconName];
            if (str_starts_with($iconName, 'heroicon-o-')) {
                // 去前綴 + dash → underscore
                $stripped = substr($iconName, strlen('heroicon-o-'));
                return str_replace('-', '_', $stripped);
            }
            return $iconName;
        };

        $tables = ['categories', 'accounts', 'saving_goals', 'tags'];
        $totalUpdated = 0;

        foreach ($tables as $table) {
            if (!\Illuminate\Support\Facades\Schema::hasTable($table)) continue;
            if (!\Illuminate\Support\Facades\Schema::hasColumn($table, 'icon')) continue;

            $rows = DB::table($table)->whereNotNull('icon')->where('icon', '!=', '')->get(['id', 'icon']);
            $count = 0;
            foreach ($rows as $row) {
                $newIcon = $autoConvert($row->icon);
                if ($newIcon !== $row->icon) {
                    DB::table($table)->where('id', $row->id)->update(['icon' => $newIcon]);
                    $count++;
                }
            }
            if ($count > 0) {
                echo "  [{$table}] 更新了 {$count} 筆 icon\n";
                $totalUpdated += $count;
            }
        }

        echo "總共更新: {$totalUpdated} 筆\n";
    }

    public function down(): void
    {
        // 此 migration 是單向修正,down() 不還原 (避免資料損毀)
    }
};
