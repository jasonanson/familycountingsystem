<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Available UI Locales
    |--------------------------------------------------------------------------
    |
    | 列舉系統支援的 UI 語系。下拉選單、按鈕、middleware 白名單
    | 全部讀這一份，新增 / 移除語系只需要改這裡。
    |
    | 順序即下拉選單顯示順序。
    |
    */
    'available' => [
        'zh_TW' => ['name' => '繁體中文', 'native' => '繁體中文', 'flag' => '🇹🇼', 'direction' => 'ltr'],
        'en'    => ['name' => 'English',  'native' => 'English',  'flag' => '🇺🇸', 'direction' => 'ltr'],
        'ja'    => ['name' => '日本語',   'native' => '日本語',   'flag' => '🇯🇵', 'direction' => 'ltr'],
    ],

    /*
    | 預設語系（如果 .env 沒設 APP_LOCALE 會採用這個）
    */
    'default' => env('APP_LOCALE', 'zh_TW'),

    /*
    | Fallback 語系（找不到翻譯時退回）
    */
    'fallback' => env('APP_FALLBACK_LOCALE', 'zh_TW'),
];
