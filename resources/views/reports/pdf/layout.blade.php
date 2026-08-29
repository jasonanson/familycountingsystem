<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'HomeSync Finance 月報' }}</title>

    <!-- 通用列印樣式（同時適用瀏覽器列印 PDF + barryvdh/dompdf） -->
    <style>
        @page { size: A4 portrait; margin: 18mm 14mm 18mm 14mm; }
        * { box-sizing: border-box; }
        html, body {
            margin: 0; padding: 0;
            font-family: "Noto Sans TC", "Microsoft JhengHei", "PingFang TC", sans-serif;
            font-size: 11pt; line-height: 1.55;
            color: #1c1917;
            background: #ffffff;
        }

        h1, h2, h3 { margin: 0; font-weight: 800; color: #006b5f; }
        h1 { font-size: 22pt; margin-bottom: 4pt; }
        h2 { font-size: 14pt; margin-top: 14pt; margin-bottom: 6pt; padding-bottom: 4pt; border-bottom: 2px solid #006b5f; }
        h3 { font-size: 12pt; margin-top: 10pt; margin-bottom: 4pt; color: #006b5f; }
        p { margin: 4pt 0; }
        .meta { color: #6b7280; font-size: 9.5pt; margin-bottom: 12pt; }
        .meta strong { color: #006b5f; }

        /* KPI 卡片 */
        .kpi-row {
            display: flex; gap: 8pt; margin-top: 10pt;
        }
        .kpi {
            flex: 1; border: 1px solid #e7e5e4; border-radius: 8pt;
            padding: 8pt 10pt; background: #fafaf9;
        }
        .kpi-label { font-size: 8.5pt; color: #6b7280; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5pt; }
        .kpi-value { font-size: 18pt; font-weight: 900; margin-top: 2pt; }
        .kpi-hint  { font-size: 8pt; color: #9ca3af; margin-top: 2pt; }
        .v-income  { color: #10b981; }
        .v-expense { color: #ef4444; }
        .v-balance { color: #006b5f; }

        /* 表格 */
        table { width: 100%; border-collapse: collapse; margin-top: 6pt; font-size: 10pt; }
        th, td { text-align: left; padding: 6pt 8pt; border-bottom: 1px solid #e7e5e4; }
        th { background: #f5f5f4; font-weight: 800; color: #44403c; }
        tr:nth-child(even) td { background: #fafaf9; }
        td.right, th.right { text-align: right; font-variant-numeric: tabular-nums; }

        .progress-bar {
            height: 8pt; background: #e7e5e4; border-radius: 4pt; overflow: hidden;
        }
        .progress-bar > div { height: 100%; background: #006b5f; }
        .progress-bar > div.warning { background: #f59e0b; }
        .progress-bar > div.danger  { background: #ef4444; }

        .tag {
            display: inline-block; padding: 1pt 6pt; border-radius: 999px;
            font-size: 8pt; font-weight: 700; background: #f5f5f4; color: #44403c;
        }
        .tag.danger  { background: #fee2e2; color: #b91c1c; }
        .tag.warning { background: #fef3c7; color: #92400e; }
        .tag.ok      { background: #d1fae5; color: #065f46; }

        .section {
            page-break-inside: avoid;
            margin-top: 14pt;
        }

        .footer {
            margin-top: 18pt; padding-top: 8pt; border-top: 1px solid #e7e5e4;
            font-size: 8pt; color: #9ca3af; text-align: center;
        }

        /* 僅在瀏覽器列印時自動觸發列印對話框，barryvdh/dompdf 不會執行 JS */
        @media print {
            .no-print { display: none !important; }
        }
    </style>
    <script>
        window.addEventListener('load', function () {
            // 透過 X-Pdf-Auto-Print header 識別是否需要自動列印
            var shouldPrint = document.querySelector('meta[name="x-pdf-auto-print"]');
            if (shouldPrint && shouldPrint.content === '1') {
                // 給瀏覽器一點時間渲染 SVG / 字型，再觸發列印
                setTimeout(function () { window.print(); }, 250);
            }
        });
    </script>
</head>
<body>
    @yield('content')

    <div class="footer">
        HomeSync Finance · {{ $familyName ?? '' }} · {{ $generatedAt ?? now()->format('Y-m-d H:i') }} · 本文件由系統自動產生
    </div>
</body>
</html>
