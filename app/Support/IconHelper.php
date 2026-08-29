<?php

namespace App\Support;

/**
 * IconHelper — 統一處理 icon 名稱
 *
 * 歷史背景: 系統原本使用 Heroicons 命名 (heroicon-o-cutlery),
 * 但前端 <span class="material-symbols-outlined"> 載入的是 Material Symbols 字型,
 * 導致瀏覽器找不到對應 ligature 而顯示純文字。
 *
 * 為避免日後又混進新的 heroicon-* 名稱,所有 icon 顯示都應透過此 helper 處理:
 *
 *   <span class="material-symbols-outlined">{{ \App\Support\IconHelper::name($account->icon) }}</span>
 */
class IconHelper
{
    /**
     * Heroicons → Material Symbols Outlined 完整映射表
     */
    public const HEROICON_TO_MATERIAL = [
        // 支出類別
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
        'heroicon-o-wallet'              => 'account_balance_wallet',

        // 常見其他
        'heroicon-o-cog'                 => 'settings',
        'heroicon-o-pencil'              => 'edit',
        'heroicon-o-pencil-square'       => 'edit_note',
        'heroicon-o-trash'               => 'delete',
        'heroicon-o-x-mark'              => 'close',
        'heroicon-o-check'               => 'check',
        'heroicon-o-check-circle'        => 'check_circle',
        'heroicon-o-plus'                => 'add',
        'heroicon-o-minus'               => 'remove',
        'heroicon-o-arrow-right'         => 'arrow_forward',
        'heroicon-o-arrow-left'          => 'arrow_back',
        'heroicon-o-chevron-right'       => 'chevron_right',
        'heroicon-o-chevron-left'        => 'chevron_left',
        'heroicon-o-eye'                 => 'visibility',
        'heroicon-o-eye-slash'           => 'visibility_off',
        'heroicon-o-user'                => 'person',
        'heroicon-o-user-circle'         => 'account_circle',
        'heroicon-o-user-group'          => 'group',
        'heroicon-o-users'               => 'group',
        'heroicon-o-bell'                => 'notifications',
        'heroicon-o-document'            => 'description',
        'heroicon-o-folder'              => 'folder',
        'heroicon-o-calendar'            => 'calendar_today',
        'heroicon-o-clock'               => 'schedule',
        'heroicon-o-magnifying-glass'    => 'search',
        'heroicon-o-star'                => 'star',
        'heroicon-o-bolt'                => 'bolt',
        'heroicon-o-fire'                => 'local_fire_department',
        'heroicon-o-tag'                 => 'label',
        'heroicon-o-flag'                => 'flag',
        'heroicon-o-bookmark'            => 'bookmark',
        'heroicon-o-share'               => 'share',
        'heroicon-o-link'                => 'link',
        'heroicon-o-clipboard'           => 'content_paste',
        'heroicon-o-key'                 => 'key',
        'heroicon-o-lock-closed'         => 'lock',
        'heroicon-o-lock-open'           => 'lock_open',
        'heroicon-o-exclamation-triangle' => 'warning',
        'heroicon-o-information-circle' => 'info',
        'heroicon-o-question-mark-circle' => 'help',
    ];

    /**
     * 將 icon 名稱正規化為 Material Symbols Outlined 相容格式。
     */
    public static function name(?string $iconName, string $fallback = 'category'): string
    {
        if (empty($iconName)) {
            return $fallback;
        }

        if (! str_starts_with($iconName, 'heroicon-')) {
            return $iconName;
        }

        if (isset(self::HEROICON_TO_MATERIAL[$iconName])) {
            return self::HEROICON_TO_MATERIAL[$iconName];
        }

        // 自動轉換: 去前綴 + dash → underscore
        $stripped = preg_replace('/^heroicon-[osm]-/', '', $iconName);
        return str_replace('-', '_', $stripped);
    }

    /**
     * 判斷 icon 是否為有效的 Material Symbols 命名
     */
    public static function isValidMaterialSymbol(string $iconName): bool
    {
        return ! str_starts_with($iconName, 'heroicon-');
    }
}
