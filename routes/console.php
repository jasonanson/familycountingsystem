<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

/*
|--------------------------------------------------------------------------
| 排程任務註冊 (HomeSync Finance)
|--------------------------------------------------------------------------
|
| Laravel 11 採用 routes/console.php 統一註冊，所有指令都會被
| `php artisan schedule:list` 看到，並由 `schedule:run` 觸發。
|
| 時區：APP_TIMEZONE (見 .env，目前 UTC)。可改為 Asia/Taipei。
|
*/

// 每日 09:00 — 預算超支 / 警示檢查
Schedule::command('budgets:check-overspend')
    ->dailyAt('09:00')
    ->timezone('Asia/Taipei')
    ->withoutOverlapping(60)
    ->onOneServer()
    ->name('budgets-check-overspend')
    ->description('每日檢查家庭預算使用率，超支 / 警示自動發送站內 + Email 通知');

// 每日 09:30 — 訂閱提醒（提前 1, 3, 7 天）
Schedule::command('subscriptions:remind --days=1,3,7')
    ->dailyAt('09:30')
    ->timezone('Asia/Taipei')
    ->withoutOverlapping(60)
    ->onOneServer()
    ->name('subscriptions-remind')
    ->description('每日掃描訂閱服務，在下次扣款日前 1/3/7 天對家長發送提醒');

// 每日 03:00 — 重整 14 天前的通知快取（避免 notifications 表膨脹）
// 由 artisan model:prune 處理；保留空間給未來擴充。
