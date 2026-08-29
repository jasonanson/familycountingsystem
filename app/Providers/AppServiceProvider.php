<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Mechanisms\HandleRequests\HandleRequests;

class CustomHandleRequests extends HandleRequests
{
    public function getUpdateUri()
    {
        $route = $this->updateRoute ?? $this->findUpdateRoute();

        return url($route ? $route->uri() : '/livewire/update');
    }
}

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(HandleRequests::class, CustomHandleRequests::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        config(['livewire.asset_url' => url('/livewire/livewire.js')]);

        // 防禦機制：若 PHP 尚未載入 intl 擴充，為 Number::format 提供 Polyfill 避免 Filament 崩潰
        if (! extension_loaded('intl')) {
            \Illuminate\Support\Number::macro('format', function (int|float $number, ?int $precision = null, ?int $maxPrecision = null, ?string $locale = null) {
                return number_format($number, $precision ?? 0);
            });
            \Illuminate\Support\Number::macro('currency', function (int|float $number, string $in = 'TWD', ?string $locale = null) {
                return 'NT$ ' . number_format($number);
            });
        }

        view()->composer('layouts.app', function ($view) {
            if (\Illuminate\Support\Facades\Auth::check()) {
                $user = \Illuminate\Support\Facades\Auth::user();

                $unreadNotificationCount = \App\Models\Notification::where('user_id', $user->id)
                    ->whereNull('read_at')
                    ->count();

                $latestNotifications = \App\Models\Notification::where('user_id', $user->id)
                    ->latest()
                    ->take(5)
                    ->get()
                    ->map(function ($n) {
                        return [
                            'id' => $n->id,
                            'type' => $n->type,
                            'title' => $n->title,
                            'body' => $n->body,
                            'read_at' => $n->read_at ? $n->read_at->toIso8601String() : null,
                            'created_at' => $n->created_at ? $n->created_at->toIso8601String() : null,
                            'time_ago' => $n->created_at ? $n->created_at->diffForHumans() : '',
                        ];
                    });

                $view->with('unreadNotificationCount', $unreadNotificationCount)
                     ->with('latestNotificationsData', $latestNotifications);
            } else {
                $view->with('unreadNotificationCount', 0)
                     ->with('latestNotificationsData', collect());
            }
        });
    }
}

