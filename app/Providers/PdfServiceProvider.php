<?php

namespace App\Providers;

use App\Services\Pdf\BrowserPrintPdfRenderer;
use App\Services\Pdf\DomPdfRenderer;
use App\Services\Pdf\PdfRendererInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * PDF 渲染器服務提供者。
 *
 * 從 config('pdf.driver') 決定綁定哪個實作，預設 'browser'（零依賴）。
 * 切換為 'dompdf' 前請先 composer require barryvdh/laravel-dompdf。
 */
class PdfServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PdfRendererInterface::class, function (Application $app) {
            $driver = config('pdf.driver', 'browser');

            return match ($driver) {
                'dompdf' => new DomPdfRenderer($app->make('view')),
                default  => new BrowserPrintPdfRenderer($app->make('view')),
            };
        });
    }

    public function boot(): void
    {
        //
    }
}
