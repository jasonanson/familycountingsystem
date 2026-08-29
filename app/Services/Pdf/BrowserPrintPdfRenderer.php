<?php

namespace App\Services\Pdf;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * 瀏覽器友善 PDF 渲染器（降級方案）。
 *
 * 適用情境：本機環境 port 443 被防火牆封鎖，無法 composer install barryvdh/laravel-dompdf。
 *
 * 工作原理：
 *   - 直接回傳 HTML response，瀏覽器會自動載入內建的「列印成 PDF」對話框
 *   - header("Content-Disposition: attachment; filename=...pdf") 讓瀏覽器預設觸發下載流程
 *   - 使用者只需在列印對話框選「另存 PDF」即可取得檔案（Chrome / Edge / Safari 都支援）
 *   - 不需任何外部依賴，零部署成本
 *
 * 切換到正式 PDF 套件：
 *   1. composer require barryvdh/laravel-dompdf
 *   2. config/pdf.php 將 driver 改為 "dompdf"
 *   3. 全部 render() 呼叫完全不需修改
 */
class BrowserPrintPdfRenderer implements PdfRendererInterface
{
    public function __construct(protected ViewFactory $view)
    {
    }

    public function render(string $view, array $data, string $filename, ?string $title = null): SymfonyResponse
    {
        $html = $this->view->make($view, $data)->render();

        $safeFilename = preg_replace('/[^A-Za-z0-9_\-\x{4e00}-\x{9fa5}]/u', '_', $filename) ?: 'report';
        $safeFilename .= '.pdf';

        return new Response($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Content-Disposition' => "inline; filename=\"{$safeFilename}\"",
            'X-Pdf-Mode' => 'browser-print',
            'X-Pdf-Title' => $title ?? 'HomeSync Finance Report',
            // 提示瀏覽器自動觸發列印對話框
            'X-Pdf-Auto-Print' => '1',
        ]);
    }
}
