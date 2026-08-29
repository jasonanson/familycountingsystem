<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>離線存取模式 - HomeSync Finance</title>
    <link rel="preload" href="/fonts/material-symbols-outlined.woff2" as="font" type="font/woff2" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Microsoft JhengHei', system-ui, sans-serif; background-color: #FAFAF9; color: #1e1b17; }
    </style>
</head>
<body class="flex flex-col items-center justify-center min-h-screen p-6 text-center">
    <div class="bg-white border border-[#E7E5E4] rounded-2xl p-8 max-w-md shadow-lg space-y-4">
        <div class="w-16 h-16 bg-[#006b5f]/10 text-[#006b5f] rounded-full flex items-center justify-center mx-auto">
            <span class="material-symbols-outlined text-4xl">wifi_off</span>
        </div>
        <h1 class="text-2xl font-bold text-[#006b5f]">{{ __('auto.0717') }}</h1>
        <p class="text-on-surface-variant text-sm leading-relaxed">
            您目前處於無網路連線狀態，HomeSync 已自動切換至離線模式。先前的快取資料可繼續檢視，連線恢復後系統將自動同步最新記帳資訊。
        </p>
        <div class="pt-4">
            <button onclick="window.location.reload()" class="px-6 py-2.5 bg-[#006b5f] text-white font-bold rounded-xl hover:bg-[#006b5f]/90 transition-all shadow">
                重新連線嘗試
            </button>
        </div>
    </div>
</body>
</html>
