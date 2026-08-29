<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>使用邀請碼加入家庭 - 家庭記帳 HomeSync Finance</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="preload" href="/fonts/material-symbols-outlined.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body { font-family: 'Microsoft JhengHei', sans-serif; }</style>
</head>
<body class="min-h-screen bg-background-warm text-on-surface antialiased relative overflow-hidden flex items-center justify-center p-4">

    <main class="relative z-10 w-full max-w-[420px] bg-surface-pure/90 backdrop-blur-xl border border-border-base rounded-2xl shadow-xl p-8 space-y-6">
        <div class="flex flex-col items-center text-center space-y-2">
            <div class="w-12 h-12 bg-primary text-white rounded-2xl flex items-center justify-center shadow-md">
                <span class="material-symbols-outlined text-3xl">group_add</span>
            </div>
            <h1 class="text-2xl font-bold text-primary tracking-tight">{{ __('auto.0661') }}</h1>
            <p class="text-sm text-on-surface-variant">{{ __('auto.0662') }}</p>
        </div>

        @if(session('error'))
            <div class="bg-danger/10 text-danger border border-danger/20 p-3 rounded-xl text-sm text-center font-medium">
                ⚠️ {{ session('error') }}
            </div>
        @endif

        @auth
            <form action="{{ route('join-family') }}" method="POST" class="space-y-4">
                @csrf

                <div class="space-y-1">
                    <label class="block text-sm font-bold text-on-surface-variant">{{ __('auto.0286') }}</label>
                    <input class="w-full px-4 py-3 text-center tracking-widest font-mono text-2xl font-bold bg-background-warm border border-border-base rounded-xl focus:outline-none focus:border-primary uppercase"
                           name="invite_code" required
                           pattern="[A-Za-z0-9]+"
                           oninput="this.value = this.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();"
                           placeholder="ABC123">
                    <p class="text-xs text-on-surface-variant text-center mt-1">{{ __('auto.0431') }}</p>
                </div>

                <button type="submit" class="w-full py-3 bg-primary hover:bg-primary/90 text-white font-bold rounded-xl shadow-md transition-all hover:scale-[1.02] flex items-center justify-center gap-2 text-base">
                    <span>{{ __('auto.0554') }}</span>
                    <span class="material-symbols-outlined text-lg">check_circle</span>
                </button>
            </form>
        @else
            {{-- 未登入引導：避免使用者誤以為可以輸入邀請碼後無回應 --}}
            <div class="space-y-4">
                <div class="bg-warning/10 text-warning border border-warning/30 p-4 rounded-xl text-sm text-center font-medium flex flex-col items-center gap-2" style="background:#FEF3C7; color:#92400E; border-color:#FCD34D;">
                    <div class="flex items-center gap-1.5 font-bold text-base">
                        <span class="material-symbols-outlined text-lg">info</span>
                        <span>請先登入帳號才能使用邀請碼</span>
                    </div>
                    <p class="text-xs font-normal opacity-90 leading-relaxed">
                        為了保護您的家庭資料安全,使用邀請碼加入家庭前,請先登入您的帳號。<br>
                        若您還沒有帳號,請先註冊後再使用邀請碼。
                    </p>
                </div>

                <a href="{{ route('login') }}" class="w-full py-3 bg-primary hover:bg-primary/90 text-white font-bold rounded-xl shadow-md transition-all hover:scale-[1.02] flex items-center justify-center gap-2 text-base">
                    <span class="material-symbols-outlined text-lg">login</span>
                    <span>前往登入</span>
                </a>

                <div class="flex items-center gap-4 my-2">
                    <div class="flex-1 h-px bg-border-base"></div>
                    <span class="text-xs text-on-surface-variant">或</span>
                    <div class="flex-1 h-px bg-border-base"></div>
                </div>

                <a href="{{ route('register') }}" class="block w-full py-2.5 text-center bg-background-warm border border-border-base hover:border-primary text-on-surface text-sm font-bold rounded-xl transition-colors">
                    還沒有帳號?立即註冊
                </a>
            </div>
        @endauth

        <div class="text-center text-sm text-on-surface-variant">
            <a href="{{ route('dashboard') }}" class="text-primary font-bold hover:underline">{{ __('auto.0672') }}</a>
        </div>
    </main>

</body>
</html>
