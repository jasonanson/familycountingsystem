<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // 支出預設分類
            [
                'name' => '餐飲食品',
                'type' => 'expense',
                'icon' => 'restaurant',
                'color' => '#F59E0B',
                'children' => ['早餐', '午餐', '晚餐', '飲料點心', '食材雜貨'],
            ],
            [
                'name' => '交通出行',
                'type' => 'expense',
                'icon' => 'local_shipping',
                'color' => '#3B82F6',
                'children' => ['公共運輸', '加油費', '停車費', '車輛維修保養', '計程車/共乘'],
            ],
            [
                'name' => '居住家居',
                'type' => 'expense',
                'icon' => 'home',
                'color' => '#10B981',
                'children' => ['房租/房貸', '水電瓦斯', '網路通訊', '居家用品', '修繕維護'],
            ],
            [
                'name' => '休閒娛樂',
                'type' => 'expense',
                'icon' => 'movie',
                'color' => '#8B5CF6',
                'children' => ['電影戲劇', '遊戲娛樂', '旅遊住宿', '運動健身', '興趣嗜好'],
            ],
            [
                'name' => '購物消費',
                'type' => 'expense',
                'icon' => 'shopping_bag',
                'color' => '#EC4899',
                'children' => ['服飾鞋包', '3C電子', '美妝保養', '個人用品'],
            ],
            [
                'name' => '醫療保健',
                'type' => 'expense',
                'icon' => 'favorite',
                'color' => '#EF4444',
                'children' => ['看診門診', '藥品保健', '醫療保險'],
            ],
            [
                'name' => '育兒教育',
                'type' => 'expense',
                'icon' => 'school',
                'color' => '#6366F1',
                'children' => ['學雜費', '書籍文具', '補習才藝', '玩具用品'],
            ],
            [
                'name' => '訂閱固定支出',
                'type' => 'expense',
                'icon' => 'autorenew',
                'color' => '#14B8A6',
                'children' => ['串流影音', '雲端服務', '定期保險'],
            ],
            [
                'name' => '其他支出',
                'type' => 'expense',
                'icon' => 'more_horiz',
                'color' => '#6B7280',
                'children' => [],
            ],

            // 收入預設分類
            [
                'name' => '薪資收入',
                'type' => 'income',
                'icon' => 'payments',
                'color' => '#10B981',
                'children' => ['經常性薪資', '加班費', '津貼補助'],
            ],
            [
                'name' => '獎金紅利',
                'type' => 'income',
                'icon' => 'auto_awesome',
                'color' => '#F59E0B',
                'children' => ['年終獎金', '績效獎金', '專案分紅'],
            ],
            [
                'name' => '零用錢/禮金',
                'type' => 'income',
                'icon' => 'redeem',
                'color' => '#EC4899',
                'children' => ['零用錢', '壓歲錢/紅包', '禮金禮券'],
            ],
            [
                'name' => '投資收益',
                'type' => 'income',
                'icon' => 'bar_chart',
                'color' => '#3B82F6',
                'children' => ['股票股利', '基金配息', '利息收入'],
            ],
            [
                'name' => '其他收入',
                'type' => 'income',
                'icon' => 'more_horiz',
                'color' => '#6B7280',
                'children' => [],
            ],
        ];

        $sortOrder = 1;
        foreach ($categories as $catData) {
            $parent = Category::create([
                'family_id' => null,
                'parent_id' => null,
                'name' => $catData['name'],
                'icon' => $catData['icon'],
                'color' => $catData['color'],
                'sort_order' => $sortOrder++,
                'is_custom' => false,
                'scope' => 'family',
                'type' => $catData['type'],
                'is_archived' => false,
            ]);

            $subSortOrder = 1;
            foreach ($catData['children'] as $childName) {
                Category::create([
                    'family_id' => null,
                    'parent_id' => $parent->id,
                    'name' => $childName,
                    'icon' => null,
                    'color' => $catData['color'],
                    'sort_order' => $subSortOrder++,
                    'is_custom' => false,
                    'scope' => 'family',
                    'type' => $catData['type'],
                    'is_archived' => false,
                ]);
            }
        }
    }
}
