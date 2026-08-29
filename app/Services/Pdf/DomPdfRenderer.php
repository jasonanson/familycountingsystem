<?php

namespace App\Services\Pdf;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Barryvdh DomPDF 渲染器（composer 安裝 barryvdh/laravel-dompdf 後生效）。
 *
 * 啟用步驟（待 port 443 解封後執行）：
 *   1. composer require barryvdh/laravel-dompdf
 *   2. config/pdf.php 將 driver 改為 "dompdf"
 *
 * 此檔案目前可載入但實際 render() 會 throw 提示尚未安裝；
 * 之後用 `composer require` 安裝套件後即可無痛切換。
 */
class DomPdfRenderer implements PdfRendererInterface
{
    public function __construct(protected ViewFactory $view)
    {
    }

    public function render(string $view, array $data, string $filename, ?string $title = null): SymfonyResponse
    {
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            throw new \RuntimeException(
                'DomPDF renderer 啟用但 barryvdh/laravel-dompdf 套件尚未安裝。請執行：composer require barryvdh/laravel-dompdf'
            );
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, $data);
        if ($title) {
            $pdf->setOption('title', $title);
        }

        $safeFilename = preg_replace('/[^A-Za-z0-9_\-\x{4e00}-\x{9fa5}]/u', '_', $filename) ?: 'report';
        $safeFilename .= '.pdf';

        return $pdf->download($safeFilename);
    }
}
