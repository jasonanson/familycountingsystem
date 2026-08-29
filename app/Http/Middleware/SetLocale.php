<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SetLocale — 設定應用語系中介層
 *
 * 解析順序（由高至低）：
 *   1. 全站預設 SystemSetting.default_locale
 *   2. .env 的 APP_LOCALE（最終保底值）
 *
 * 並把解析結果同步到：
 *   - app()->setLocale()：影響所有 __() / trans() 輸出
 *   - Carbon::setLocale()：日期時間本地化（diffForHumans()、formatLocalized()）
 *
 * 注意:語言切換 UI 已於 2026/08/28 移除（功能未完善）,
 *       但中介層保留,確保系統仍以 .env APP_LOCALE 為預設語系。
 */
class SetLocale
{
    /** 白名單語系;不在清單內的輸入一律忽略,避免任意檔案包含風險 */
    public const AVAILABLE_LOCALES = ['zh_TW', 'en', 'ja'];

    public function handle(Request $request, Closure $next): Response
    {
        // 1. 全站預設 (管理員可在後台設定)
        $locale = \App\Models\SystemSetting::get('default_locale');

        // 2. .env APP_LOCALE (最終保底值)
        if (! $locale || ! in_array($locale, self::AVAILABLE_LOCALES, true)) {
            $locale = config('app.locale');
        }

        // 雙重保險:確保語系一定是白名單內
        if (! in_array($locale, self::AVAILABLE_LOCALES, true)) {
            $locale = 'zh_TW';
        }

        app()->setLocale($locale);

        // 同步 Carbon 語系,讓 diffForHumans() 之類的輸出在地化
        if (class_exists('Carbon\Carbon')) {
            \Carbon\Carbon::setLocale($this->mapToCarbonLocale($locale));
        }

        return $next($request);
    }

    /**
     * Carbon 內建語系代碼對應。Carbon 不支援 zh_TW 時自動退化到 zh。
     */
    protected function mapToCarbonLocale(string $locale): string
    {
        $map = [
            'zh_TW' => 'zh-TW',
            'zh_CN' => 'zh-CN',
            'en'    => 'en',
            'ja'    => 'ja',
            'ko'    => 'ko',
            'es'    => 'es',
        ];
        return $map[$locale] ?? 'en';
    }
}
