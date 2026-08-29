<?php

namespace App\Services\Pdf;

/**
 * PDF 服務介面（抽象層）。
 *
 * 設計目的：把 PDF 生成從 controller 解耦，日後要換套件只需實作新 driver 並改 config。
 *
 * Driver 對照：
 *   - "browser"        → 印表機友善 HTML 視圖（透過瀏覽器內建「列印成 PDF」取得 PDF）
 *   - "dompdf"         → Barryvdh\DomPDF\PDF（composer 安裝 barryvdh/laravel-dompdf 後生效）
 *
 * 用法：
 *   app(PdfRenderer::class)->render('reports.pdf.monthly', $data, '家庭月報_202608');
 *
 * 選擇 driver 從 config('pdf.driver') 讀取，預設為 "browser"。
 */
interface PdfRendererInterface
{
    /**
     * 將 Blade view 渲染為 PDF 並透過瀏覽器下載。
     *
     * @param  string  $view       Blade view 路徑 (e.g. "reports.pdf.monthly")
     * @param  array   $data       view 變數
     * @param  string  $filename   下載檔名（不含副檔名）
     * @param  string|null $title  HTML <title> 與 PDF metadata
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render(string $view, array $data, string $filename, ?string $title = null);
}
