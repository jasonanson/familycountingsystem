<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PDF Driver
    |--------------------------------------------------------------------------
    |
    | 支援兩個 driver：
    |   - "browser" — 瀏覽器內建「列印成 PDF」，零依賴，適合無網路環境
    |   - "dompdf"  — Barryvdh\DomPDF，composer require barryvdh/laravel-dompdf
    |
    | 切換時不需要改任何 controller / view，只改此設定檔。
    */

    'driver' => env('PDF_DRIVER', 'browser'),

    'paper' => [
        'size'      => env('PDF_PAPER_SIZE', 'a4'),
        'orientation' => env('PDF_PAPER_ORIENTATION', 'portrait'),
    ],

    'options' => [
        'isHtml5ParserEnabled' => true,
        'isRemoteEnabled' => false,
        'defaultFont' => 'sans-serif',
    ],
];
