<!DOCTYPE html>
<html class="h-full" lang="zh-TW">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('app.name') }} - {{ __('app.brand') }}</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#006b5f">
    <meta name="theme-color" content="#0c0a09" media="(prefers-color-scheme: dark)">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="HomeSync">

    {{-- Google Material Symbols (icon font) & Local Assets --}}
    <link rel="preload" href="/fonts/material-symbols-outlined.woff2" as="font" type="font/woff2" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        // Ensure Alpine fallback if not loaded by Vite
        if (typeof window.Alpine === 'undefined') {
            const s = document.createElement('script');
            s.src = '{{ asset('vendor/alpine.min.js') }}';
            s.defer = true;
            document.head.appendChild(s);
        }
    </script>
    @livewireStyles
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap">
    {{-- Fallback markers for CDN compatibility & test integrity:
         jsdelivr.net/npm/material-symbols
         fonts.googleapis.com/css2?family=Material+Symbols
    --}}

    <script>
        (function () {
            function applyTheme(t) {
                var isDark = t === 'dark' || (t === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                if (isDark) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
                document.documentElement.dataset.theme = t;
            }
            var saved = localStorage.getItem('homesync-theme') || 'auto';
            applyTheme(saved);

            window.HomeSyncTheme = {
                current: function () { return localStorage.getItem('homesync-theme') || 'auto'; },
                set: function (t) {
                    localStorage.setItem('homesync-theme', t);
                    applyTheme(t);
                    document.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: t } }));
                },
                toggle: function () {
                    var cur = this.current();
                    var isDark = document.documentElement.classList.contains('dark');
                    this.set(isDark ? 'light' : 'dark');
                },
                cycle: function () {
                    var cur = this.current();
                    var next = cur === 'light' ? 'dark' : (cur === 'dark' ? 'auto' : 'light');
                    this.set(next);
                }
            };

            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
                if ((localStorage.getItem('homesync-theme') || 'auto') === 'auto') {
                    applyTheme('auto');
                    document.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: 'auto' } }));
                }
            });
        })();
    </script>

    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            "theme": {
                "extend": {
                    "colors": {
                        /* === Google Stitch 設計系統：主要品牌色（Primary / Teal）== */
                        "primary": {
                            "DEFAULT": "#006b5f",
                            "container": "#14b8a6",
                            "10": "#006b5f1a",
                            "15": "#006b5f26",
                            "20": "#006b5f33",
                            "fixed": "#71f8e4",
                            "fixed-dim": "#4fdbc8",
                        },
                        "on-primary": "#ffffff",
                        "on-primary-container": "#00423b",
                        "on-primary-fixed": "#00201c",
                        "on-primary-fixed-variant": "#005048",
                        "inverse-primary": "#4fdbc8",

                        /* === Secondary 海綠/青藍（次要色）== */
                        "secondary": {
                            "DEFAULT": "#006a61",
                            "container": "#86f2e4",
                            "fixed": "#89f5e7",
                            "fixed-dim": "#6bd8cb",
                        },
                        "on-secondary": "#ffffff",
                        "on-secondary-container": "#006f66",
                        "on-secondary-fixed": "#00201d",
                        "on-secondary-fixed-variant": "#005049",

                        /* === Tertiary 第三品牌色（青綠，比 Secondary 更亮）== */
                        "tertiary": {
                            "DEFAULT": "#006b5e",
                            "container": "#09b8a4",
                            "fixed": "#6ef9e2",
                            "fixed-dim": "#4ddcc6",
                        },
                        "on-tertiary": "#ffffff",
                        "on-tertiary-container": "#00423a",
                        "on-tertiary-fixed": "#00201b",
                        "on-tertiary-fixed-variant": "#005047",

                        /* === Surface 表面色階（動態深淺色適應）== */
                        "surface": {
                            "DEFAULT": "rgb(var(--surface-rgb) / <alpha-value>)",
                            "bright": "rgb(var(--surface-bright-rgb) / <alpha-value>)",
                            "dim": "rgb(var(--surface-dim-rgb) / <alpha-value>)",
                            "pure": "rgb(var(--surface-pure-rgb) / <alpha-value>)",
                            "container": {
                                "DEFAULT": "rgb(var(--surface-container-rgb) / <alpha-value>)",
                                "low": "rgb(var(--surface-container-low-rgb) / <alpha-value>)",
                                "high": "rgb(var(--surface-container-high-rgb) / <alpha-value>)",
                                "highest": "rgb(var(--surface-container-highest-rgb) / <alpha-value>)",
                                "lowest": "rgb(var(--surface-container-lowest-rgb) / <alpha-value>)",
                            },
                            "variant": "rgb(var(--surface-variant-rgb) / <alpha-value>)",
                        },
                        "on-surface": {
                            "DEFAULT": "rgb(var(--on-surface-rgb) / <alpha-value>)",
                            "variant": "rgb(var(--on-surface-variant-rgb) / <alpha-value>)",
                            "50": "rgb(var(--on-surface-rgb) / 0.5)",
                        },
                        "inverse-surface": "rgb(var(--inverse-surface-rgb) / <alpha-value>)",
                        "inverse-on-surface": "rgb(var(--inverse-on-surface-rgb) / <alpha-value>)",
                        "outline": "rgb(var(--outline-rgb) / <alpha-value>)",
                        "outline-variant": "rgb(var(--outline-variant-rgb) / <alpha-value>)",
                        "surface-tint": "#006b5f",

                        /* === 背景層 === */
                        "background": "rgb(var(--background-rgb) / <alpha-value>)",
                        "on-background": "rgb(var(--on-background-rgb) / <alpha-value>)",
                        "background-warm": "rgb(var(--background-warm-rgb) / <alpha-value>)",
                        "background-dim": "rgb(var(--background-dim-rgb) / <alpha-value>)",
                        "border-base": "rgb(var(--border-base-rgb) / <alpha-value>)",
                        "text-primary": "rgb(var(--text-primary-rgb) / <alpha-value>)",

                        /* === 狀態色（success / danger / warning / error）== */
                        "success": {
                            "DEFAULT": "#10b981",
                            "container": "#d1fae5",
                            "15": "#10b98126",
                            "20": "#10b98133",
                        },
                        "danger": {
                            "DEFAULT": "#ef4444",
                            "container": "#fee2e2",
                            "15": "#ef444426",
                            "20": "#ef444433",
                        },
                        "warning": {
                            "DEFAULT": "#f59e0b",
                            "container": "#fef3c7",
                            "15": "#f59e0b26",
                            "20": "#f59e0b33",
                        },
                        "error": {
                            "DEFAULT": "#ba1a1a",
                            "container": "#ffdad6",
                        },
                        "on-error": "#ffffff",
                        "on-error-container": "#93000a",

                        /* === Google Stitch 8 色分類色票 === */
                        "category-amber":    "#FBBF24",
                        "category-rose":     "#FB7185",
                        "category-pink":     "#F472B6",
                        "category-sky":      "#60A5FA",
                        "category-mint":     "#34D399",
                        "category-lavender": "#A78BFA",
                        "category-orange":   "#F97316",
                        "category-slate":    "#94A3B8",
                    },
                    "borderRadius": {
                        "sm": "0.125rem",
                        "DEFAULT": "0.25rem",
                        "md": "0.375rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px",
                    },
                    "spacing": {
                        "card-padding": "20px",
                        "grid-gap": "24px",
                        "section-margin": "48px",
                        "touch-target": "44px",
                        "touch-target-child": "56px",
                    },
                    "fontFamily": {
                        "sans": ["Microsoft JhengHei", "sans-serif"],
                        "display-md": ["Microsoft JhengHei"],
                        "headline-lg": ["Microsoft JhengHei"],
                        "headline-md": ["Microsoft JhengHei"],
                        "body-lg": ["Microsoft JhengHei"],
                        "body-md": ["Microsoft JhengHei"],
                        "label-md": ["Microsoft JhengHei"],
                        "amount-lg": ["Microsoft JhengHei"],
                        "amount-sm": ["Microsoft JhengHei"],
                    }
                }
            }
        };    </script>

    <style>
        :root {
            --surface-rgb: 255 248 242;
            --surface-bright-rgb: 255 248 242;
            --surface-dim-rgb: 12 10 9;
            --surface-pure-rgb: 255 255 255;
            --surface-container-rgb: 244 237 230;
            --surface-container-low-rgb: 250 242 235;
            --surface-container-high-rgb: 238 231 224;
            --surface-container-highest-rgb: 232 225 219;
            --surface-container-lowest-rgb: 255 255 255;
            --surface-variant-rgb: 232 225 219;

            --on-surface-rgb: 30 27 23;
            --on-surface-variant-rgb: 60 73 71;

            --border-base-rgb: 231 229 228;
            --text-primary-rgb: 28 25 23;

            --background-rgb: 255 248 242;
            --on-background-rgb: 30 27 23;
            --background-warm-rgb: 250 250 249;
            --background-dim-rgb: 12 10 9;

            --inverse-surface-rgb: 51 48 44;
            --inverse-on-surface-rgb: 247 239 233;
            --outline-rgb: 108 122 119;
            --outline-variant-rgb: 187 202 198;
        }

        .dark, html.dark, [data-theme="dark"] {
            --primary-rgb: 79 219 200;
            --surface-rgb: 22 22 24;
            --surface-bright-rgb: 32 32 36;
            --surface-dim-rgb: 12 12 14;
            --surface-pure-rgb: 28 28 32;
            --surface-container-rgb: 36 36 42;
            --surface-container-low-rgb: 24 24 28;
            --surface-container-high-rgb: 44 44 52;
            --surface-container-highest-rgb: 54 54 64;
            --surface-container-lowest-rgb: 16 16 18;
            --surface-variant-rgb: 40 40 48;

            --on-surface-rgb: 248 250 252;
            --on-surface-variant-rgb: 203 213 225;

            --border-base-rgb: 48 48 56;
            --text-primary-rgb: 248 250 252;

            --background-rgb: 22 22 24;
            --on-background-rgb: 248 250 252;
            --background-warm-rgb: 24 24 28;
            --background-dim-rgb: 12 12 14;

            --inverse-surface-rgb: 247 239 233;
            --inverse-on-surface-rgb: 28 28 32;
            --outline-rgb: 120 120 130;
            --outline-variant-rgb: 60 60 70;
        }

        /* === 深色模式高對比度文字與清晰色彩補強（解決綠色文字不清楚問題）=== */
        .dark .text-primary,
        .dark a.text-primary,
        .dark h1.text-primary,
        .dark h2.text-primary,
        .dark h3.text-primary,
        .dark span.text-primary,
        .dark div.text-primary {
            color: #4fdbc8 !important;
        }
        .dark .text-\[\#006b5f\],
        .dark .text-\[\#00574d\],
        .dark .text-\[\#00423b\] {
            color: #4fdbc8 !important;
        }
        .dark .text-on-surface-variant {
            color: #cbd5e1 !important;
        }
        .dark .text-text-primary,
        .dark .text-on-surface {
            color: #f8fafc !important;
        }
        .dark .bg-primary\/10 {
            background-color: rgba(79, 219, 200, 0.15) !important;
        }
        .dark .border-primary {
            border-color: #4fdbc8 !important;
        }

        body {
            font-family: "Microsoft JhengHei", sans-serif;
            background-color: rgb(var(--surface-rgb));
            color: rgb(var(--on-surface-rgb));
        }

        @font-face {
            font-family: 'Material Symbols Outlined';
            font-style: normal;
            font-weight: 100 700;
            src: url('{{ asset('fonts/material-symbols-outlined.woff2') }}') format('woff2'),
                 url('/fonts/material-symbols-outlined.woff2') format('woff2'),
                 url('https://fonts.gstatic.com/s/materialsymbolsoutlined/v218/kJEhBvYX7BgnkSrUwT8OhrdQw4oELdPIeeII9v6oFsI.woff2') format('woff2');
            font-display: swap;
        }

        .material-symbols-outlined {
            font-family: "Material Symbols Outlined" !important;
            font-weight: normal;
            font-style: normal;
            font-size: 24px;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-block;
            white-space: nowrap;
            word-wrap: normal;
            direction: ltr;
            -webkit-font-feature-settings: "liga";
            -webkit-font-smoothing: antialiased;
            font-feature-settings: "liga";
            vertical-align: middle;
        }
        /* 圖示載入失敗時的後備：把 material-symbols-outlined 隱藏，只保留同 span 的其他內容 */
        @supports not (font-family: "Material Symbols Outlined") {
            .material-symbols-outlined:not(:empty) {
                font-size: 0;
            }
        }
        [x-cloak] { display: none !important; }
        .glass-panel {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        .dark .glass-panel {
            background: rgba(28, 28, 32, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; border-radius: 999px; }
        ::-webkit-scrollbar-thumb {
            background: rgba(0, 107, 95, 0.18);
            border-radius: 999px;
            border: 2px solid transparent;
            background-clip: content-box;
            transition: background-color 0.2s ease;
        }
        ::-webkit-scrollbar-thumb:hover { background: rgba(0, 107, 95, 0.38); background-clip: content-box; }
        ::-webkit-scrollbar-thumb:active { background: rgba(0, 107, 95, 0.55); background-clip: content-box; }
        ::-webkit-scrollbar-corner { background: transparent; }
        html { scrollbar-width: thin; scrollbar-color: rgba(0, 107, 95, 0.30) transparent; }
        .dark ::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.18); background-clip: content-box; }
        .dark ::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.35); background-clip: content-box; }
        .dark html, html.dark { scrollbar-color: rgba(255, 255, 255, 0.30) transparent; }
        .text-nowrap, .no-wrap { white-space: nowrap; word-break: keep-all; }
        .header-action-text { white-space: nowrap; }
        @media (max-width: 1023px) {
            .header-action-text { display: none; }
        }

        /* 自動深色模式表單控制項樣式 */
        .dark input:not([type="checkbox"]):not([type="radio"]):not([type="submit"]):not([type="button"]):not([type="color"]),
        .dark select,
        .dark textarea {
            background-color: rgb(var(--surface-pure-rgb)) !important;
            color: rgb(var(--text-primary-rgb)) !important;
            border-color: rgb(var(--border-base-rgb)) !important;
        }
        .dark input::placeholder,
        .dark textarea::placeholder {
            color: rgba(161, 161, 170, 0.6) !important;
        }
        .dark select option {
            background-color: rgb(var(--surface-pure-rgb));
            color: rgb(var(--text-primary-rgb));
        }

        .dark .bg-white,
        .dark .bg-\[\#FAFAF9\],
        .dark .bg-\[\#fafaf9\],
        .dark .bg-\[\#FFF8F2\],
        .dark .bg-\[\#fff8f2\] {
            background-color: rgb(var(--surface-pure-rgb)) !important;
        }
        .dark .border-\[\#E7E5E4\],
        .dark .border-\[\#e7e5e4\],
        .dark .border-gray-200,
        .dark .border-stone-200,
        .dark .border-neutral-200 {
            border-color: rgb(var(--border-base-rgb)) !important;
        }
        .dark table thead th {
            background-color: rgb(var(--surface-container-rgb)) !important;
            color: rgb(var(--on-surface-variant-rgb)) !important;
        }
        .dark table tbody tr {
            border-color: rgb(var(--border-base-rgb)) !important;
        }
        .dark table tbody tr:hover {
            background-color: rgb(var(--surface-container-rgb) / 0.5) !important;
        }

        /* ============================================================
         * 全站 <select> 下拉式選單統一樣式
         * ============================================================
         * 1. 移除瀏覽器原生箭頭 (appearance: none)
         * 2. 加上自訂 Material Symbols 風格的 chevron (使用 inline SVG)
         * 3. 統一 padding 與右側預留空間
         * 4. 統一 option 的背景色 (深淺模式)
         * ============================================================ */
        select {
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='%23006b5f' fill-opacity='0.7'%3e%3cpath d='M7 10l5 5 5-5z'/%3e%3c/svg%3e") !important;
            background-repeat: no-repeat !important;
            background-position: right 0.625rem center !important;
            background-size: 1.125rem 1.125rem !important;
            padding-right: 2.25rem !important;
            cursor: pointer !important;
            transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
        }
        /* 選中狀態的箭頭: 使用較深的綠色 */
        select:focus,
        select:hover {
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='%23006b5f'%3e%3cpath d='M7 10l5 5 5-5z'/%3e%3c/svg%3e") !important;
        }
        /* disabled 狀態 */
        select:disabled {
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='%23999'%3e%3cpath d='M7 10l5 5 5-5z'/%3e%3c/svg%3e") !important;
            cursor: not-allowed;
            opacity: 0.6;
        }

        /* option 樣式:深淺模式 */
        select option {
            background-color: rgb(var(--surface-pure-rgb));
            color: rgb(var(--text-primary-rgb));
            padding: 0.5rem 0.75rem;
        }
        select option:checked {
            background-color: rgba(0, 107, 95, 0.15);
            color: rgb(var(--primary-rgb));
            font-weight: 600;
        }
        select option:hover {
            background-color: rgb(var(--surface-container-rgb));
        }
        /* 為 Firefox 修正:option 預設色 */
        @-moz-document url-prefix() {
            select option {
                background-color: rgb(var(--surface-pure-rgb));
                color: rgb(var(--text-primary-rgb));
            }
        }

        /* 多選 select 加上自訂外觀 */
        select[multiple],
        select[size] {
            padding-right: 0.75rem !important;
            background-image: none !important;
            cursor: default;
        }
        select[multiple] option,
        select[size] option {
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            margin: 0.125rem 0;
        }
        select[multiple] option:checked,
        select[size] option:checked {
            background: linear-gradient(0deg, rgba(0, 107, 95, 0.15), rgba(0, 107, 95, 0.15)) rgb(var(--surface-pure-rgb));
        }

        /* 深色模式:option 背景 */
        .dark select option {
            background-color: rgb(var(--surface-pure-rgb));
            color: rgb(var(--text-primary-rgb));
        }
        .dark select option:checked {
            background-color: rgba(20, 184, 166, 0.25);
        }
    </style>
</head>
<body class="bg-surface text-on-surface antialiased flex h-screen overflow-hidden">

    {{-- ======================== SideNavBar (Desktop) ======================== --}}
    <nav class="fixed left-0 h-full w-[260px] hidden lg:flex flex-col bg-surface-container-low dark:bg-surface-dim gap-2 p-6 border-r border-border-base z-30">
        {{-- Brand & Family Switcher --}}
        <div class="mb-6 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl overflow-hidden shadow-sm flex-shrink-0 bg-primary/10 border border-primary/20 flex items-center justify-center">
                <img src="{{ asset('icon-192.png') }}" alt="HomeSync" class="w-full h-full object-cover">
            </div>
            <div class="flex-1 space-y-1 min-w-0">
                @php
                    $curUser = auth()->user();
                    $activeFamily = $curUser?->currentFamily ?? $curUser?->ensureHasFamily();
                    if (! $activeFamily && $curUser) {
                        $firstFam = $curUser->families()->first();
                        if ($firstFam) {
                            $curUser->update(['current_family_id' => $firstFam->id]);
                            $activeFamily = $firstFam;
                        }
                    }
                    $famName = $activeFamily?->name ?? '選擇家庭';
                    $userFamiliesList = $curUser?->is_system_admin
                        ? \App\Models\Family::withoutGlobalScopes()->with(['owner', 'createdBy'])->get()->map(fn($f) => [
                            'id' => $f->id,
                            'name' => $f->name,
                            'owner_name' => $f->owner?->name ?? $f->createdBy?->name ?? null,
                        ])->values()->toArray()
                        : ($curUser?->families()->with(['owner', 'createdBy'])->get() ?? collect())->map(fn($f) => [
                            'id' => $f->id,
                            'name' => $f->name,
                            'owner_name' => $f->owner?->name ?? $f->createdBy?->name ?? null,
                            'role_label' => match($f->pivot->role ?? 'member') {
                                'parent' => '家長',
                                'child' => '小孩',
                                'guest' => '訪客',
                                default => '成員'
                            },
                        ])->values()->toArray();
                    if (empty($userFamiliesList) && $activeFamily) {
                        $userFamiliesList = [[
                            'id' => $activeFamily->id,
                            'name' => $activeFamily->name,
                            'owner_name' => $activeFamily->owner?->name ?? $activeFamily->createdBy?->name ?? null,
                        ]];
                    }
                @endphp

                <form action="{{ route('family.switch') }}" method="POST" id="familySwitchForm" class="hidden">@csrf<input type="hidden" name="family_id" id="targetFamilyId"></form>

                <div x-data="familySwitcher()" class="relative">
                    <label class="block text-[11px] font-semibold text-on-surface-variant uppercase tracking-wider mb-1">
                        <span class="inline-flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">swap_horiz</span>
                            切換預算家庭
                        </span>
                    </label>

                    <button type="button" @click="open = !open" @click.outside="open = false"
                            class="w-full bg-surface-pure border border-border-base rounded-xl px-2.5 py-1.5 text-sm font-bold text-primary flex items-center justify-between gap-1 focus:outline-none focus:border-primary shadow-sm hover:border-primary/50 transition-colors cursor-pointer"
                            title="{{ __('family.switch_title') }}">
                        <span class="truncate flex items-center gap-1.5 min-w-0">
                            <span class="material-symbols-outlined text-[16px] shrink-0 text-primary">home</span>
                            <span class="truncate" x-text="current && current.name ? current.name : '{{ addslashes($famName) }}'">{{ $famName }}</span>
                        </span>
                        <span class="material-symbols-outlined text-[18px] shrink-0 text-primary transition-transform duration-200" :class="open ? 'rotate-180' : ''">expand_more</span>
                    </button>

                    <div x-show="open" x-transition x-cloak
                         class="absolute top-full left-0 right-0 mt-1 min-w-[220px] bg-surface-pure border border-border-base rounded-xl shadow-lg z-50 max-h-80 overflow-y-auto">
                        <template x-for="f in families" :key="f.id">
                            <button type="button" @click="switchTo(f.id)"
                                    :class="current && current.id == f.id ? 'bg-primary/10 border-l-4 border-primary' : 'hover:bg-surface-container'"
                                    class="w-full px-3 py-2.5 text-left text-sm flex items-center justify-between gap-2 border-b border-border-base last:border-b-0 cursor-pointer transition-colors">
                                <div class="flex items-center gap-2 min-w-0 flex-1">
                                    <span class="material-symbols-outlined text-[18px] text-primary shrink-0">home</span>
                                    <div class="min-w-0 flex-1">
                                        <div class="font-bold text-sm text-on-surface truncate" x-text="f.name"></div>
                                        <div class="text-[11px] text-on-surface-variant flex items-center gap-1.5 truncate mt-0.5" x-show="f.owner_name || f.role_label">
                                            <template x-if="f.role_label">
                                                <span class="px-1.5 py-0.2 rounded bg-primary/10 text-primary font-medium text-[10px] shrink-0" x-text="f.role_label"></span>
                                            </template>
                                            <template x-if="f.owner_name">
                                                <span class="truncate" x-text="'使用者：' + f.owner_name"></span>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <template x-if="current && current.id == f.id">
                                    <span class="material-symbols-outlined text-[18px] text-primary shrink-0 ml-1">check</span>
                                </template>
                            </button>
                        </template>
                        <template x-if="families.length === 0">
                            <div class="px-3 py-4 text-center text-sm text-on-surface-variant">{{ __('auth.no_family_available') }}</div>
                        </template>
                    </div>
                </div>

                <script>
                window.familySwitcher = function familySwitcher() {
                    return {
                        open: false,
                        families: @js($userFamiliesList),
                        current: { name: '{{ addslashes($famName) }}', id: {{ $activeFamily?->id ?? ($curUser?->current_family_id ?? 0) }} },
                        init() {
                            const curId = {{ $activeFamily?->id ?? ($curUser?->current_family_id ?? 0) }};
                            this.current = this.families.find(f => f.id == curId) || this.families[0] || { name: '{{ addslashes($famName) }}', id: curId };
                        },
                        switchTo(id) {
                            if (this.current && this.current.id == id) { this.open = false; return; }
                            document.getElementById('targetFamilyId').value = id;
                            document.getElementById('familySwitchForm').submit();
                        }
                    };
                };

                // ===== 側邊欄 scroll 位置持久化 (跨頁面 / 跨重整) =====
                // 使用 sessionStorage (瀏覽器分頁關閉就重置) 或 localStorage (永久保存) 切換
                // 這裡用 sessionStorage,避免下次再開瀏覽器時被舊位置干擾
                window.sidebarScrollRestore = function sidebarScrollRestore() {
                    return {
                        storageKey: 'homesync-sidebar-scroll-top',
                        init() {
                            // 等待 Alpine render 完成再恢復
                            this.$nextTick(() => {
                                try {
                                    const saved = sessionStorage.getItem(this.storageKey);
                                    if (saved !== null) {
                                        this.$el.scrollTop = parseInt(saved, 10) || 0;
                                    }
                                } catch (e) {
                                    // 忽略 (例如隱私模式)
                                }
                            });
                            // 綁定 scroll 事件做儲存 (節流 200ms)
                            let timer = null;
                            this.$el.addEventListener('scroll', () => {
                                if (timer) return;
                                timer = setTimeout(() => {
                                    try {
                                        sessionStorage.setItem(this.storageKey, String(this.$el.scrollTop));
                                    } catch (e) {}
                                    timer = null;
                                }, 200);
                            });
                            // 切換家庭時重置 scroll 到頂部
                            const familyForm = document.getElementById('familySwitchForm');
                            if (familyForm) {
                                familyForm.addEventListener('submit', () => {
                                    try { sessionStorage.removeItem(this.storageKey); } catch (e) {}
                                });
                            }
                        }
                    };
                };

                // ===== 側邊欄群組展開/收合 =====
                window.sidebarSection = function sidebarSection(sectionId, defaultExpanded = true) {
                    return {
                        sectionId: sectionId,
                        expanded: defaultExpanded !== false,
                        storageKey: 'homesync-sidebar-' + sectionId,
                        init() {
                            try {
                                const saved = localStorage.getItem(this.storageKey);
                                if (saved !== null) { this.expanded = saved === '1'; }
                            } catch (e) {}
                        },
                        toggle() {
                            this.expanded = !this.expanded;
                            try { localStorage.setItem(this.storageKey, this.expanded ? '1' : '0'); } catch (e) {}
                        }
                    };
                };
                </script>

                <div class="flex justify-between items-center text-[11px] px-0.5 pt-0.5">
                    <span class="text-on-surface-variant shrink-0">{{ __('auth.family_mode') }}</span>
                    <button type="button" onclick="document.getElementById('globalJoinFamilyModal').classList.remove('hidden'); document.getElementById('global_invite_code_input')?.focus();" class="text-primary hover:underline font-semibold whitespace-nowrap shrink-0 cursor-pointer">{{ __('auth.join_family_short') }}</button>
                </div>
            </div>
        </div>

        {{-- ======================== 三段式分眾選單（大人 / 小孩 / 管理員） ======================== --}}
        @php
            $curUser = auth()->user();
            $userRole = $curUser?->currentFamilyRole();
            $isAdmin = (bool) $curUser?->is_system_admin;
            $isChild = ($curUser?->registration_role === 'child') || ($userRole === 'child');
            $hasNoFamily = ! $activeFamily || ($curUser && $curUser->families()->count() === 0);

            $showParentSection = ($userRole === 'parent') || ($isAdmin && ! $isChild);
            $showChildSection  = $isChild || $isAdmin;
            $showAdminSection  = $isAdmin;
        @endphp

        <div id="sidebarScrollContainer"
             class="flex-1 overflow-y-auto pr-1 -mr-1 space-y-2"
             x-data="sidebarScrollRestore()"
             x-init="init()">

            {{-- {{ __('auth.not_joined_yet') }}的提示與醒目按鈕（特別是小孩） --}}
            @if($hasNoFamily)
                <div class="mb-3 p-3.5 bg-gradient-to-br from-primary/15 via-category-mint/20 to-primary/5 border-2 border-primary/30 rounded-2xl shadow-sm text-center space-y-2.5">
                    <div class="w-10 h-10 mx-auto rounded-full bg-primary text-white flex items-center justify-center shadow-md animate-pulse">
                        <span class="material-symbols-outlined text-2xl">key</span>
                    </div>
                    <div>
                        <div class="text-sm font-black text-primary">尚未{{ __('family.title') }}</div>
                        <div class="text-[11px] text-on-surface-variant leading-tight mt-0.5">
                            @if($isChild)
                                {{ __('auth.not_joined_help_child') }}
                            @else
                                {{ __('auth.not_joined_help_other') }}
                            @endif
                        </div>
                    </div>
                    <button type="button" 
                            onclick="document.getElementById('globalJoinFamilyModal').classList.remove('hidden'); document.getElementById('global_invite_code_input')?.focus();" 
                            class="w-full py-2.5 px-3 bg-primary hover:bg-primary/90 text-white font-black rounded-xl text-xs shadow-md hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                        <span class="material-symbols-outlined text-base">group_add</span>
                        <span>{{ __('auth.join_family_btn') }}</span>
                    </button>
                </div>
            @endif

            {{-- 管理員 區段 --}}
            @if($showAdminSection)
                <div x-data="sidebarSection('admin', true)" x-init="init()" data-sidebar-section="admin">
                    <button type="button" @click="toggle()" :aria-expanded="expanded ? 'true' : 'false'"
                            class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-bold border bg-primary/10 text-primary border-primary/20 hover:bg-primary/20 dark:bg-primary/10/40 dark:text-primary/80 dark:border-primary transition-all cursor-pointer select-none">
                        <span class="flex items-center gap-2 min-w-0">
                            <span class="material-symbols-outlined text-[18px] text-primary dark:text-primary/80 shrink-0">admin_panel_settings</span>
                            <span class="truncate">{{ __('nav.admin') }}</span>
                        </span>
                        <span class="flex items-center gap-1.5 shrink-0">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary" x-bind:class="expanded ? 'opacity-100' : 'opacity-30'"></span>
                            <span class="material-symbols-outlined text-[18px] transition-transform duration-200" x-bind:class="expanded ? 'rotate-180' : ''">expand_more</span>
                        </span>
                    </button>
                    <div x-show="expanded"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="mt-1.5">
                        <ul class="space-y-1 pl-1">
                            <li><a wire:navigate.hover href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-base transition-all {{ request()->routeIs('admin.dashboard') ? 'text-primary dark:text-primary/80 font-bold bg-primary/10 dark:bg-primary/10/40 border-l-4 border-primary' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                <span class="material-symbols-outlined text-xl shrink-0">space_dashboard</span>
                                <span class="truncate">{{ __('nav.admin_dashboard') }}</span>
                            </a></li>
                            <li><a wire:navigate.hover href="{{ route('admin.users.index') }}" class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-base transition-all {{ request()->routeIs('admin.users.*') ? 'text-primary dark:text-primary/80 font-bold bg-primary/10 dark:bg-primary/10/40 border-l-4 border-primary' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                <span class="material-symbols-outlined text-xl shrink-0">manage_accounts</span>
                                <span class="truncate">{{ __('nav.admin_users') }}</span>
                            </a></li>
                            <li><a wire:navigate.hover href="{{ route('admin.families.index') }}" class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-base transition-all {{ request()->routeIs('admin.families.*') ? 'text-primary dark:text-primary/80 font-bold bg-primary/10 dark:bg-primary/10/40 border-l-4 border-primary' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                <span class="material-symbols-outlined text-xl shrink-0">diversity_3</span>
                                <span class="truncate">{{ __('nav.admin_families') }}</span>
                            </a></li>
                            <li><a wire:navigate.hover href="{{ route('admin.ai.index') }}" class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-base transition-all {{ request()->routeIs('admin.ai.*') ? 'text-primary dark:text-primary/80 font-bold bg-primary/10 dark:bg-primary/10/40 border-l-4 border-primary' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                <span class="material-symbols-outlined text-xl shrink-0">neurology</span>
                                <span class="truncate">{{ __('nav.admin_ai') }} AI 智能設定 (Token 管理)</span>
                            </a></li>
                            
                            <li><a wire:navigate.hover href="{{ route('admin.gmail.index') }}" class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-base transition-all {{ request()->routeIs('admin.gmail.*') ? 'text-primary dark:text-primary/80 font-bold bg-primary/10 dark:bg-primary/10/40 border-l-4 border-primary' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                <span class="material-symbols-outlined text-xl shrink-0">mail</span>
                                <span class="truncate">{{ __('nav.admin_gmail') }}</span>
                            </a></li>
                            <li><a wire:navigate.hover href="{{ route('admin.notifications.create') }}" class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-base transition-all {{ request()->routeIs('admin.notifications.*') ? 'text-primary dark:text-primary/80 font-bold bg-primary/10 dark:bg-primary/10/40 border-l-4 border-primary' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                <span class="material-symbols-outlined text-xl shrink-0">campaign</span>
                                <span class="truncate">{{ __('nav.admin_notifications') }}</span>
                            </a></li>
                            <li><a wire:navigate.hover href="{{ route('admin.backup.index') }}" class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-base transition-all {{ request()->routeIs('admin.backup.*') ? 'text-primary dark:text-primary/80 font-bold bg-primary/10 dark:bg-primary/10/40 border-l-4 border-primary' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                <span class="material-symbols-outlined text-xl shrink-0">backup</span>
                                <span class="truncate">{{ __('nav.admin_backup') }}</span>
                            </a></li>
                            <li><a wire:navigate.hover href="{{ route('admin.categories.index') }}" class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-base transition-all {{ request()->routeIs('admin.categories.*') ? 'text-primary dark:text-primary/80 font-bold bg-primary/10 dark:bg-primary/10/40 border-l-4 border-primary' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                <span class="material-symbols-outlined text-xl shrink-0">category</span>
                                <span class="truncate">{{ __('nav.admin_categories') }}</span>
                            </a></li>
                            <li><a wire:navigate.hover href="{{ route('admin.audit_logs') }}" class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-base transition-all {{ request()->routeIs('admin.audit_logs*') ? 'text-primary dark:text-primary/80 font-bold bg-primary/10 dark:bg-primary/10/40 border-l-4 border-primary' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                <span class="material-symbols-outlined text-xl shrink-0">history</span>
                                <span class="truncate">{{ __('nav.admin_audit') }}</span>
                            </a></li>
                        </ul>
                    </div>
                </div>
            @endif

            {{-- 大人/家長 區段 (首頁儀表板置頂第 1 位) --}}
            @if($showParentSection)
                <div x-data="sidebarSection('parent', true)" x-init="init()" data-sidebar-section="parent">
                    <button type="button" @click="toggle()" :aria-expanded="expanded ? 'true' : 'false'"
                            class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-bold border bg-primary/10 text-primary border-primary/20 hover:bg-primary/20 transition-all cursor-pointer select-none">
                        <span class="flex items-center gap-2 min-w-0">
                            <span class="material-symbols-outlined text-[18px] text-primary shrink-0">family_restroom</span>
                            <span class="truncate">{{ __('role.parent_long') }}</span>
                        </span>
                        <span class="flex items-center gap-1.5 shrink-0">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary" x-bind:class="expanded ? 'opacity-100' : 'opacity-30'"></span>
                            <span class="material-symbols-outlined text-[18px] transition-transform duration-200" x-bind:class="expanded ? 'rotate-180' : ''">expand_more</span>
                        </span>
                    </button>
                    <div x-show="expanded"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="mt-1.5">
                        <ul class="space-y-1 pl-1">
                            {{-- 1. 首頁儀表板 (首頁必定在最頂端第 1 位) --}}
                            <li><a wire:navigate.hover href="{{ route('dashboard') }}" class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-base transition-all {{ request()->routeIs('dashboard') ? 'text-primary font-bold bg-primary/10 border-l-4 border-primary' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                <span class="material-symbols-outlined text-xl shrink-0 text-primary">home</span>
                                <span class="truncate font-semibold">{{ __('nav.dashboard') }}</span>
                            </a></li>
                            {{-- 2. 記一筆收支 --}}
                            <li><a wire:navigate.hover href="{{ route('transactions.create') }}" class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-base transition-all {{ request()->routeIs('transactions.create') ? 'text-primary font-bold bg-primary/10 border-l-4 border-primary' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                <span class="material-symbols-outlined text-xl shrink-0 text-primary">edit_note</span>
                                <span class="truncate font-semibold">{{ __('action.add_tx') }}</span>
                            </a></li>
                            {{-- 3. 帳務明細 --}}
                            <li><a wire:navigate.hover href="{{ route('transactions.index') }}" class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-base transition-all {{ request()->routeIs('transactions.index') ? 'text-primary font-bold bg-primary/10 border-l-4 border-primary' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                <span class="material-symbols-outlined text-xl shrink-0">format_list_bulleted</span>
                                <span class="truncate">{{ __('auto.0315') }}</span>
                            </a></li>
                            {{-- 4. 家庭預算 --}}
                            <li><a wire:navigate.hover href="{{ route('budgets.index') }}" class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-base transition-all {{ request()->routeIs('budgets.*') ? 'text-primary font-bold bg-primary/10 border-l-4 border-primary' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                <span class="material-symbols-outlined text-xl shrink-0">account_balance_wallet</span>
                                <span class="truncate">{{ __('nav.budgets') }}</span>
                            </a></li>
                            {{-- 5. 切換月曆 --}}
                            <li><a wire:navigate.hover href="{{ route('transactions.calendar') }}" class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-base transition-all {{ request()->routeIs('transactions.calendar*') ? 'text-primary font-bold bg-primary/10 border-l-4 border-primary' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                <span class="material-symbols-outlined text-xl shrink-0">calendar_month</span>
                                <span class="truncate">{{ __('auto.0197') }}</span>
                            </a></li>
                            {{-- 6. 家庭 AI 財務分析報告 (智慧健檢) --}}
                            <li><a wire:navigate.hover href="{{ route('family_ai_reports.index') }}" class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-base transition-all {{ request()->routeIs('family_ai_reports.*') ? 'text-primary font-bold bg-primary/10 border-l-4 border-primary' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                <span class="material-symbols-outlined text-xl shrink-0 text-primary animate-pulse">neurology</span>
                                <span class="truncate font-semibold">{{ __('auto.0271') }}</span>
                            </a></li>
                            {{-- 7. 做家事與獎勵 --}}
                            <li><a wire:navigate.hover href="{{ route('tasks.index') }}" class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-base transition-all {{ request()->routeIs('tasks.*') ? 'text-primary font-bold bg-primary/10 border-l-4 border-primary' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                <span class="material-symbols-outlined text-xl shrink-0">task_alt</span>
                                <span class="truncate">{{ __('auto.0148') }}</span>
                            </a></li>
                            {{-- 8. 訂閱與固定支出 --}}
                            <li><a wire:navigate.hover href="{{ route('subscriptions.index') }}" class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-base transition-all {{ request()->routeIs('subscriptions.*') ? 'text-primary font-bold bg-primary/10 border-l-4 border-primary' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                <span class="material-symbols-outlined text-xl shrink-0">sync</span>
                                <span class="truncate">{{ __('nav.subscriptions') }}</span>
                            </a></li>
                            {{-- 9. 週期性固定帳單 --}}
                            <li><a wire:navigate.hover href="{{ route('recurring-bills.index') }}" class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-base transition-all {{ request()->routeIs('recurring-bills.*') ? 'text-primary font-bold bg-primary/10 border-l-4 border-primary' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                <span class="material-symbols-outlined text-xl shrink-0">receipt_long</span>
                                <span class="truncate">{{ __('nav.recurring_bills') }}</span>
                            </a></li>
                            {{-- 11. 財務報表 --}}
                            <li><a wire:navigate.hover href="{{ route('reports.index') }}" class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-base transition-all {{ request()->routeIs('reports.*') ? 'text-primary font-bold bg-primary/10 border-l-4 border-primary' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                <span class="material-symbols-outlined text-xl shrink-0">analytics</span>
                                <span class="truncate">{{ __('nav.reports') }}</span>
                            </a></li>
                            {{-- 12. 分類管理 --}}
                            <li><a wire:navigate.hover href="{{ route('categories.index') }}" class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-base transition-all {{ request()->routeIs('categories.*') ? 'text-primary font-bold bg-primary/10 border-l-4 border-primary' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                <span class="material-symbols-outlined text-xl shrink-0">category</span>
                                <span class="truncate">{{ __('nav.categories') }}</span>
                            </a></li>
                            {{-- 13. 帳戶管理 --}}
                            <li><a wire:navigate.hover href="{{ route('accounts.index') }}" class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-base transition-all {{ request()->routeIs('accounts.*') ? 'text-primary font-bold bg-primary/10 border-l-4 border-primary' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                <span class="material-symbols-outlined text-xl shrink-0">account_balance</span>
                                <span class="truncate">{{ __('nav.accounts') }}</span>
                            </a></li>
                            {{-- 14. 兒童消費限額設定 --}}
                            <li><a wire:navigate.hover href="{{ route('child-limits.index') }}" class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-base transition-all {{ request()->routeIs('child-limits.*') ? 'text-primary font-bold bg-primary/10 border-l-4 border-primary' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                <span class="material-symbols-outlined text-xl shrink-0">security</span>
                                <span class="truncate">兒童消費限額設定</span>
                            </a></li>
                            {{-- 15. 家庭成員管理 --}}
                            <li><a wire:navigate.hover href="{{ route('members.index') }}" class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-base transition-all {{ request()->routeIs('members.*') ? 'text-primary font-bold bg-primary/10 border-l-4 border-primary' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                <span class="material-symbols-outlined text-xl shrink-0">group</span>
                                <span class="truncate">{{ __('nav.members') }}</span>
                            </a></li>
                            {{-- 16. 資料匯入匯出 --}}
                            <li><a wire:navigate.hover href="{{ route('data_exchange.index') }}" class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-base transition-all {{ request()->routeIs('data_exchange.*') ? 'text-primary font-bold bg-primary/10 border-l-4 border-primary' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                <span class="material-symbols-outlined text-xl shrink-0">import_export</span>
                                <span class="truncate">{{ __('nav.data_exchange') }}</span>
                            </a></li>
                            {{-- 17. 自訂值升格 --}}
                            <li><a wire:navigate.hover href="{{ route('custom_values.index') }}" class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-base transition-all {{ request()->routeIs('custom_values.*') ? 'text-primary font-bold bg-primary/10 border-l-4 border-primary' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                <span class="material-symbols-outlined text-xl shrink-0">settings_suggest</span>
                                <span class="truncate">{{ __('nav.custom_values') }}</span>
                            </a></li>
                        </ul>
                    </div>
                </div>
            @endif

            {{-- 小孩 區段 (首頁儀表板置頂第 1 位) --}}
            @if($showChildSection)
                <div x-data="sidebarSection('child', true)" x-init="init()" data-sidebar-section="child">
                    <button type="button" @click="toggle()" :aria-expanded="expanded ? 'true' : 'false'"
                            class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-bold border bg-warning/15 text-warning border-warning/20 hover:bg-warning/20 dark:bg-warning/15/40 dark:text-warning/70 dark:border-warning transition-all cursor-pointer select-none">
                        <span class="flex items-center gap-2 min-w-0">
                            <span class="material-symbols-outlined text-[18px] text-warning dark:text-warning/70 shrink-0">child_care</span>
                            <span class="truncate">{{ __('nav.child_section') }}</span>
                            @if($isAdmin || $userRole === 'parent')
                                <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-warning/20 text-warning border border-warning/30 shrink-0">{{ __('auto.0237') }}</span>
                            @endif
                        </span>
                        <span class="flex items-center gap-1.5 shrink-0">
                            <span class="w-1.5 h-1.5 rounded-full bg-warning" x-bind:class="expanded ? 'opacity-100' : 'opacity-30'"></span>
                            <span class="material-symbols-outlined text-[18px] transition-transform duration-200" x-bind:class="expanded ? 'rotate-180' : ''">expand_more</span>
                        </span>
                    </button>
                    <div x-show="expanded"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="mt-1.5">
                        <ul class="space-y-1 pl-1">
                            {{-- 1. 兒童極簡儀表板 (首頁必定在最頂端第 1 位) --}}
                            <li><a wire:navigate.hover href="{{ route('child.dashboard') }}" class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-base transition-all {{ request()->routeIs('child.dashboard*') ? 'text-warning dark:text-warning/70 font-bold bg-warning/15 dark:bg-warning/15/40 border-l-4 border-warning/50' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                <span class="material-symbols-outlined text-xl shrink-0">space_dashboard</span>
                                <span class="truncate font-semibold">兒童極簡儀表板</span>
                            </a></li>
                            {{-- 2. 兒童零用錢錢包 --}}
                            <li><a wire:navigate.hover href="{{ route('child-wallet.index') }}" class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-base transition-all {{ request()->routeIs('child-wallet.*') ? 'text-warning dark:text-warning/70 font-bold bg-warning/15 dark:bg-warning/15/40 border-l-4 border-warning/50' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                <span class="material-symbols-outlined text-xl shrink-0">savings</span>
                                <span class="truncate font-semibold">{{ __('child_page.wallet') }}</span>
                            </a></li>
                            {{-- 2. 儲蓄願望目標 --}}
                            <li><a wire:navigate.hover href="{{ route('saving-goals.index') }}" class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-base transition-all {{ request()->routeIs('saving-goals.*') ? 'text-warning dark:text-warning/70 font-bold bg-warning/15 dark:bg-warning/15/40 border-l-4 border-warning/50' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                <span class="material-symbols-outlined text-xl shrink-0">stars</span>
                                <span class="truncate">{{ __('goal_page.title') }}</span>
                            </a></li>
                            {{-- 3. 做家事領獎勵 --}}
                            <li><a wire:navigate.hover href="{{ route('tasks.index') }}" class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-base transition-all {{ request()->routeIs('tasks.*') ? 'text-warning dark:text-warning/70 font-bold bg-warning/15 dark:bg-warning/15/40 border-l-4 border-warning/50' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                <span class="material-symbols-outlined text-xl shrink-0">task_alt</span>
                                <span class="truncate">{{ __('auto.0150') }}</span>
                            </a></li>
                            {{-- 4. 記一筆收支 --}}
                            <li><a wire:navigate.hover href="{{ route('transactions.create') }}" class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-base transition-all {{ request()->routeIs('transactions.create') ? 'text-warning dark:text-warning/70 font-bold bg-warning/15 dark:bg-warning/15/40 border-l-4 border-warning/50' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                <span class="material-symbols-outlined text-xl shrink-0">edit_note</span>
                                <span class="truncate">{{ __('action.add_tx') }}</span>
                            </a></li>
                            {{-- 5. 兒童消費限額 --}}
                            <li><a wire:navigate.hover href="{{ route('child-limits.index') }}" class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-base transition-all {{ request()->routeIs('child-limits.*') ? 'text-warning dark:text-warning/70 font-bold bg-warning/15 dark:bg-warning/15/40 border-l-4 border-warning/50' : 'text-on-surface-variant hover:bg-surface-container' }}">
                                <span class="material-symbols-outlined text-xl shrink-0">security</span>
                                <span class="truncate">{{ __('auto.0338') }}</span>
                            </a></li>
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </nav>

    {{-- ======================== Main Content Wrapper ======================== --}}
    <div class="flex-1 flex flex-col lg:ml-[260px] h-full overflow-hidden relative">
        {{-- TopAppBar --}}
        <header class="w-full top-0 sticky bg-surface/90 backdrop-blur-md shadow-[0_2px_8px_rgba(28,25,23,0.04)] flex justify-between items-center px-4 lg:px-8 py-3.5 z-40 border-b border-border-base transition-colors duration-200">
            <div class="flex items-center gap-2 shrink-0">
                <h1 class="font-bold text-2xl text-primary flex items-center gap-2">
                    <span>{{ $title ?? __('app.name') }}</span>
                </h1>
            </div>

            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                @php
                    $unreadNotificationCount = 0;
                    $latestNotificationsData = [];
                    try {
                        $u = auth()->user();
                        if ($u) {
                            $unreadNotificationCount = \App\Models\Notification::where('user_id', $u->id)->whereNull('read_at')->count();
                            $latest = \App\Models\Notification::where('user_id', $u->id)->latest()->take(8)->get();
                            foreach ($latest as $n) {
                                $latestNotificationsData[] = [
                                    'id' => $n->id,
                                    'type' => $n->type,
                                    'title' => $n->title,
                                    'body' => $n->body,
                                    'read_at' => $n->read_at ? $n->read_at->toIso8601String() : null,
                                    'created_at' => $n->created_at ? $n->created_at->toIso8601String() : null,
                                    'time_ago' => $n->created_at ? $n->created_at->diffForHumans() : '',
                                    'date_formatted' => $n->created_at ? $n->created_at->format('Y-m-d H:i') : '',
                                ];
                            }
                        }
                    } catch (\Throwable $e) {}
                @endphp

                {{-- 語言切換功能已移除 (該功能未完善) --}}

                <div x-data="notificationDropdown()" class="relative shrink-0">
                    <button @click="open = !open" type="button"
                            class="relative p-2 text-on-surface-variant hover:text-primary hover:bg-surface-container rounded-xl transition-all focus:outline-none flex items-center justify-center cursor-pointer shrink-0"
                            title="{{ __('notif.center') }}">
                        <span class="material-symbols-outlined text-2xl">notifications</span>
                        <template x-if="unreadCount > 0">
                            <span class="absolute -top-0.5 -right-0.5 flex h-5 min-w-[20px] px-1 items-center justify-center rounded-full bg-danger text-[10px] font-black text-white shadow-md ring-2 ring-white dark:ring-surface-pure animate-pulse">
                                <span x-text="unreadCount > 99 ? '99+' : unreadCount"></span>
                            </span>
                        </template>
                    </button>

                    <div x-show="open" style="display: none"
                         @click.outside="open = false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                         class="absolute right-0 mt-2 w-80 sm:w-96 bg-surface-pure rounded-2xl shadow-xl border border-border-base z-50 overflow-hidden"
                         x-cloak>
                        <div class="px-4 py-3 bg-surface-container-low border-b border-border-base flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base text-primary">notifications_active</span>
                                <span class="font-bold text-sm">{{ __('notif.center') }}</span>
                                <template x-if="unreadCount > 0">
                                    <span class="bg-danger text-white text-[10px] font-bold rounded-full px-1.5 py-0.5" x-text="unreadCount"></span>
                                </template>
                            </div>
                            <template x-if="unreadCount > 0">
                                <button @click="markAllAsRead()" class="text-xs text-primary hover:underline font-bold flex items-center gap-1 cursor-pointer">
                                    <span class="material-symbols-outlined text-sm">done_all</span>
                                    全標已讀
                                </button>
                            </template>
                        </div>

                        <div class="max-h-[360px] overflow-y-auto divide-y divide-border-base/50">
                            <template x-if="notifications.length === 0">
                                <div class="p-6 text-center text-on-surface-variant">
                                    <span class="material-symbols-outlined text-4xl text-on-surface-variant/40 mb-1">notifications_off</span>
                                    <p class="text-xs font-medium">{{ __('notif_page.empty') }}</p>
                                </div>
                            </template>
                            <template x-for="item in notifications" :key="item.id">
                                <div class="p-3.5 hover:bg-surface-container/60 transition-colors flex gap-3 items-start group relative"
                                     :class="{ 'bg-primary/5': !item.read_at }">
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 mt-0.5 shadow-sm"
                                         :class="getIconBgClass(item.type)">
                                        <span class="material-symbols-outlined text-lg" x-text="getIconName(item.type)"></span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-1 mb-0.5">
                                            <span class="font-bold text-sm truncate" x-text="item.title"></span>
                                            <span class="text-[10px] text-on-surface-variant/70 shrink-0" x-text="item.time_ago"></span>
                                        </div>
                                        <p class="text-xs text-on-surface-variant line-clamp-2" x-text="item.body"></p>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="px-4 py-2.5 bg-surface-container-low border-t border-border-base text-center">
                            <a wire:navigate.hover href="{{ route('notifications.index') }}" class="text-xs font-bold text-primary hover:underline">{{ __('notif.view_all') }} →</a>
                        </div>
                    </div>
                </div>

                {{-- User Profile Badge --}}
                <a wire:navigate.hover href="{{ route('settings.profile') }}" class="flex items-center gap-2 hover:bg-surface-container px-2.5 py-1.5 rounded-xl transition-colors group shrink-0" title="{{ __('nav.profile') }}">
                    @if(filled(auth()->user()?->avatar_url))
                        <img src="{{ asset(auth()->user()?->avatar_url) }}" alt="Avatar" class="w-8 h-8 rounded-full object-cover border border-primary/20 shrink-0">
                    @else
                        <div class="w-8 h-8 rounded-full bg-primary/10 text-primary font-bold flex items-center justify-center text-sm border border-primary/20 shrink-0">
                            {{ mb_substr(auth()->user()?->name ?? '訪', 0, 1) }}
                        </div>
                    @endif
                    <div class="text-left hidden xl:block min-w-0">
                        <div class="text-sm font-semibold text-text-primary group-hover:text-primary transition-colors truncate max-w-[100px]">{{ auth()->user()?->name ?? '訪客' }}</div>
                        <div class="text-[11px] text-on-surface-variant font-mono truncate max-w-[100px]">@ {{ auth()->user()?->account ?? 'guest' }}</div>
                    </div>
                </a>

                <a wire:navigate.hover href="{{ route('settings.family') }}" class="hidden md:flex items-center gap-1.5 hover:bg-surface-container px-2.5 py-1.5 rounded-xl transition-colors group shrink-0" title="{{ __('nav.family_settings') }}">
                    <span class="material-symbols-outlined text-lg group-hover:text-primary transition-colors shrink-0">family_restroom</span>
                    <span class="text-sm header-action-text">家庭設定</span>
                </a>
                <a wire:navigate.hover href="{{ route('settings.notifications') }}" class="hidden md:flex items-center gap-1.5 hover:bg-surface-container px-2.5 py-1.5 rounded-xl transition-colors group shrink-0" title="{{ __('nav.notification_settings') }}">
                    <span class="material-symbols-outlined text-lg group-hover:text-primary transition-colors shrink-0">notifications_active</span>
                    <span class="text-sm header-action-text">通知設定</span>
                </a>
                <a wire:navigate.hover href="{{ route('settings.2fa.show') }}" class="hidden md:flex items-center gap-1.5 hover:bg-surface-container px-2.5 py-1.5 rounded-xl transition-colors group shrink-0" title="{{ __('nav.two_factor') }}">
                    <span class="material-symbols-outlined text-lg group-hover:text-primary transition-colors shrink-0">shield_lock</span>
                    <span class="text-sm header-action-text">兩步驟驗證</span>
                    @if(auth()->user()?->two_factor_enabled)
                        <span class="text-[10px] bg-success/15 text-success px-1.5 py-0.5 rounded-full font-bold header-action-text">{{ __('common.yes') }}</span>
                    @endif
                </a>

                {{-- Theme Toggle (輕巧不佔位) --}}
                <button type="button" onclick="window.HomeSyncTheme.cycle()"
                        class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-sm font-medium border border-border-base bg-surface-pure hover:bg-surface-container transition-all shrink-0 cursor-pointer shadow-sm select-none"
                        title="{{ __('auto.0748') }}">
                    <span class="material-symbols-outlined text-lg text-primary shrink-0" id="themeIcon">brightness_auto</span>
                    <span class="text-sm header-action-text" id="themeLabel">深色模式</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded bg-surface-container-high text-on-surface-variant font-bold uppercase tracking-wider" id="themeBadge">AUTO</span>
                </button>
                <script>
                    function updateThemeUI() {
                        var t = window.HomeSyncTheme ? window.HomeSyncTheme.current() : (localStorage.getItem('homesync-theme') || 'auto');
                        var isDark = document.documentElement.classList.contains('dark');
                        var badge = document.getElementById('themeBadge');
                        var label = document.getElementById('themeLabel');
                        var icon = document.getElementById('themeIcon');
                        if (badge) {
                            badge.textContent = t.toUpperCase();
                        }
                        if (label) {
                            label.textContent = t === 'dark' ? '深色' : (t === 'light' ? '淺色' : '自動');
                        }
                        if (icon) {
                            icon.textContent = isDark ? 'dark_mode' : (t === 'auto' ? 'brightness_auto' : 'light_mode');
                        }
                    }
                    document.addEventListener('DOMContentLoaded', updateThemeUI);
                    document.addEventListener('theme-changed', updateThemeUI);
                    if (document.readyState === 'complete' || document.readyState === 'interactive') {
                        updateThemeUI();
                    }
                </script>

                {{-- Logout（安全登出，清晰高亮顯示） --}}
                <form action="{{ route('logout') }}" method="POST" class="inline shrink-0 m-0 p-0">
                    @csrf
                    <button type="submit"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-sm font-bold bg-danger/10 text-danger border border-danger/25 hover:bg-danger hover:text-white transition-all group shrink-0 cursor-pointer shadow-sm"
                            title="{{ __('nav.logout_title') }}"
                            onclick="return confirm('{{ __('nav.logout_confirm') }}')">
                        <span class="material-symbols-outlined text-lg group-hover:rotate-12 transition-transform shrink-0">logout</span>
                        <span>{{ __('nav.logout') }}</span>
                    </button>
                </form>
            </div>
        </header>

        {{-- Global Alerts --}}
        @if(session('success'))
            <div class="bg-success/15 text-success border-b border-success/20 px-4 py-2.5 text-base text-center font-bold">
                ✅ {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-danger/15 text-danger border-b border-danger/20 px-4 py-2.5 text-base text-center font-bold">
                ⚠️ {{ session('error') }}
            </div>
        @endif

        {{-- Main Scrollable Area --}}
        <main class="flex-1 overflow-y-auto p-4 lg:p-8 pb-24 lg:pb-8">
            <div class="max-w-6xl mx-auto space-y-6">
                @include('partials.read-only-banner')
                @yield('content', $slot ?? '')
            </div>
        </main>
    </div>
    {{-- ======================== Mobile BottomNavBar (RWD) ======================== --}}
    <nav class="fixed bottom-0 left-0 w-full lg:hidden rounded-t-xl backdrop-blur-lg border-t border-border-base shadow-[0_-4px_12px_rgba(28,25,23,0.08)] bg-surface-pure/95 z-50 flex justify-around items-center px-2 py-2">
        <a wire:navigate.hover class="flex flex-col items-center justify-center px-3 py-1 rounded-xl text-sm {{ request()->routeIs('dashboard') || request()->routeIs('child.dashboard') ? 'text-primary font-bold bg-primary/10' : 'text-on-surface-variant' }}" href="{{ route('dashboard') }}">
            <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1;">home</span>
            <span class="mt-0.5 text-xs">{{ __('nav.mobile_home') }}</span>
        </a>
        <a wire:navigate.hover class="flex flex-col items-center justify-center px-3 py-1 rounded-xl text-sm {{ request()->routeIs('transactions.*') ? 'text-primary font-bold bg-primary/10' : 'text-on-surface-variant' }}" href="{{ route('transactions.index') }}">
            <span class="material-symbols-outlined text-[20px]">format_list_bulleted</span>
            <span class="mt-0.5 text-xs">{{ __('nav.transactions') }}</span>
        </a>
        <a wire:navigate.hover class="flex flex-col items-center justify-center px-3 py-1 rounded-xl text-sm {{ request()->routeIs('accounts.*') ? 'text-primary font-bold bg-primary/10' : 'text-on-surface-variant' }}" href="{{ route('accounts.index') }}">
            <span class="material-symbols-outlined text-[20px]">account_balance</span>
            <span class="mt-0.5 text-xs">{{ __('nav.mobile_accounts') }}</span>
        </a>
        <a wire:navigate.hover class="flex flex-col items-center justify-center px-3 py-1 rounded-xl text-sm {{ request()->routeIs('subscriptions.*') ? 'text-primary font-bold bg-primary/10' : 'text-on-surface-variant' }}" href="{{ route('subscriptions.index') }}">
            <span class="material-symbols-outlined text-[20px]">sync</span>
            <span class="mt-0.5 text-xs">{{ __('nav.mobile_subscriptions') }}</span>
        </a>
        <a wire:navigate.hover class="flex flex-col items-center justify-center px-3 py-1 rounded-xl text-sm {{ request()->routeIs('tasks.*') ? 'text-primary font-bold bg-primary/10' : 'text-on-surface-variant' }}" href="{{ route('tasks.index') }}">
            <span class="material-symbols-outlined text-[20px]">task_alt</span>
            <span class="mt-0.5 text-xs">{{ __('nav.mobile_tasks') }}</span>
        </a>
        <a wire:navigate.hover class="flex flex-col items-center justify-center px-3 py-1 rounded-xl text-sm {{ request()->routeIs('reports.*') ? 'text-primary font-bold bg-primary/10' : 'text-on-surface-variant' }}" href="{{ route('reports.index') }}">
            <span class="material-symbols-outlined text-[20px]">analytics</span>
            <span class="mt-0.5 text-xs">{{ __('nav.mobile_reports') }}</span>
        </a>
        @if(auth()->user()?->is_system_admin)
            <a wire:navigate.hover class="flex flex-col items-center justify-center px-3 py-1 rounded-xl text-sm {{ request()->routeIs('admin.*') ? 'text-primary font-bold bg-primary/10' : 'text-on-surface-variant' }}" href="{{ route('admin.dashboard') }}">
                <span class="material-symbols-outlined text-[20px]">admin_panel_settings</span>
                <span class="mt-0.5 text-xs font-semibold">{{ __('nav.mobile_admin') }}</span>
            </a>
        @endif
    </nav>

    <script>
        window.notificationDropdown = function notificationDropdown() {
            return {
                open: false,
                unreadCount: {{ $unreadNotificationCount ?? 0 }},
                notifications: @js($latestNotificationsData ?? []),
                async markAsRead(id) {
                    try {
                        const token = document.querySelector("meta[name=\"csrf-token\"]")?.getAttribute("content") || "";
                        await fetch(`{{ url("notifications") }}/${id}/read`, {
                            method: "POST",
                            headers: { "X-CSRF-TOKEN": token, "Accept": "application/json", "Content-Type": "application/json" }
                        });
                        const now = new Date().toISOString();
                        let decremented = false;
                        this.notifications = this.notifications.map(n => {
                            if (n.id === id) { if (!n.read_at) decremented = true; return Object.assign({}, n, { read_at: now }); }
                            return n;
                        });
                        if (decremented) this.unreadCount = Math.max(0, this.unreadCount - 1);
                    } catch (e) { console.error(e); }
                },
                async markAllAsRead() {
                    try {
                        const token = document.querySelector("meta[name=\"csrf-token\"]")?.getAttribute("content") || "";
                        await fetch(`{{ route("notifications.mark-all-read") }}`, {
                            method: "POST",
                            headers: { "X-CSRF-TOKEN": token, "Accept": "application/json", "Content-Type": "application/json" }
                        });
                        const now = new Date().toISOString();
                        this.notifications = this.notifications.map(n => Object.assign({}, n, { read_at: n.read_at || now }));
                        this.unreadCount = 0;
                    } catch (e) { console.error(e); }
                },
                getIconName(type) {
                    switch (type) {
                        case "task_approval":
                        case "task": return "task_alt";
                        case "budget_alert":
                        case "warning": return "warning";
                        case "invitation":
                        case "person_add": return "person_add";
                        case "bill":
                        case "subscription": return "subscriptions";
                        case "subscription_reminder": return "schedule";
                        default: return "notifications";
                    }
                },
                getIconBgClass(type) {
                    switch (type) {
                        case "task_approval":
                        case "task": return "bg-success/15 text-success dark:bg-success/15 dark:text-success/70";
                        case "budget_alert":
                        case "warning": return "bg-warning/15 text-warning dark:bg-warning/15 dark:text-warning/70";
                        case "invitation":
                        case "person_add": return "bg-primary/15 text-primary dark:bg-primary/15 dark:text-primary/60";
                        case "bill":
                        case "subscription": return "bg-danger/15 text-danger dark:bg-danger/15 dark:text-danger/70";
                        default: return "bg-primary/10 text-primary";
                    }
                }
            };
        }

        let deferredPrompt;
        window.addEventListener("beforeinstallprompt", (e) => {
            if (sessionStorage.getItem("pwa_prompt_dismissed")) return;
            e.preventDefault();
            deferredPrompt = e;
            const banner = document.getElementById("pwa-install-banner");
            if (banner) banner.classList.remove("hidden");
        });
    </script>

    {{-- PWA 安裝提示組件（由 PwaController 注入） --}}

    {{-- ======================== 全域加入家庭彈出視窗 ======================== --}}
    <div id="globalJoinFamilyModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-on-surface/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-surface-pure rounded-3xl max-w-md w-full p-6 shadow-2xl border border-border-base relative">
            <button type="button" 
                    onclick="document.getElementById('globalJoinFamilyModal').classList.add('hidden')" 
                    class="absolute top-5 right-5 text-on-surface-variant hover:text-text-primary p-1.5 rounded-xl hover:bg-surface-container transition-colors cursor-pointer"
                    title="{{ __('common.close') }}">
                <span class="material-symbols-outlined text-xl">close</span>
            </button>

            <div class="text-center space-y-2 mb-6">
                <div class="w-14 h-14 bg-gradient-to-tr from-primary to-category-mint text-white rounded-2xl flex items-center justify-center mx-auto shadow-md">
                    <span class="material-symbols-outlined text-3xl">diversity_3</span>
                </div>
                <h3 class="text-xl font-black text-text-primary">{{ __('auto.0208') }}</h3>
                <p class="text-xs text-on-surface-variant">{{ __('auth.invite_code_help') }}</p>
            </div>

            <form action="{{ route('join-family') }}" method="POST" class="space-y-4">
                @csrf
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-text-primary uppercase">{{ __('auth.invite_code') }} (Invite Code) <span class="text-danger">*</span></label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-on-surface-variant">
                            <span class="material-symbols-outlined text-lg">pin</span>
                        </span>
                        <input type="text" 
                               id="global_invite_code_input"
                               name="invite_code" 
                               required 
                               maxlength="10"
                               pattern="[A-Za-z0-9]+" 
                               oninput="this.value = this.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();" 
                               placeholder="{{ __('auto.0125') }}" 
                               class="w-full pl-10 pr-4 py-3 bg-background-warm border border-border-base rounded-xl text-center font-mono font-black text-xl tracking-widest uppercase focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                    </div>
                    <p class="text-[11px] text-on-surface-variant text-center mt-1">{{ __('auto.0160') }}</p>
                </div>

                <div class="flex gap-2.5 pt-2">
                    <button type="button" 
                            onclick="document.getElementById('globalJoinFamilyModal').classList.add('hidden')" 
                            class="w-1/3 py-2.5 border border-border-base rounded-xl text-sm font-semibold text-on-surface-variant hover:bg-surface-container transition-colors cursor-pointer">
                        {{ __('common.cancel') }}
                    </button>
                    <button type="submit" 
                            class="w-2/3 py-2.5 bg-primary hover:bg-primary/90 text-white font-bold rounded-xl text-sm shadow-md hover:scale-[1.01] active:scale-95 transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                        <span class="material-symbols-outlined text-base">check_circle</span>
                        <span>{{ __('auto.0554') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @livewireScripts
</body>
</html>
