<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>兩步驟驗證 - 家庭記帳 HomeSync Finance</title>
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

    <!-- Background Blobs -->
    <div class="fixed inset-0 z-0 pointer-events-none overflow-hidden bg-gradient-to-br from-primary-container/10 to-background-warm">
        <div class="absolute top-[-10%] left-[-10%] w-[50vw] h-[50vw] rounded-full bg-primary-container/10 blur-[100px]"></div>
        <div class="absolute bottom-[-20%] right-[-10%] w-[60vw] h-[60vw] rounded-full bg-primary/10 blur-[120px]"></div>
    </div>

    <!-- 2FA Verification Card -->
    <main class="relative z-10 w-full max-w-[420px] bg-surface-pure/90 backdrop-blur-xl border border-border-base rounded-2xl shadow-xl p-8 space-y-6">
        <!-- Header -->
        <div class="flex flex-col items-center text-center space-y-2">
            <div class="w-12 h-12 bg-primary text-white rounded-2xl flex items-center justify-center shadow-md">
                <span class="material-symbols-outlined text-3xl">shield_lock</span>
            </div>
            <h1 class="text-2xl font-bold text-primary tracking-tight">{{ __('auto.0182') }}</h1>
            <p class="text-sm text-on-surface-variant">{{ __('auto.0642') }}</p>
        </div>

        @if ($errors->any())
            <div class="bg-danger/10 border border-danger/20 text-danger rounded-xl px-4 py-3 text-sm font-medium">
                ⚠️ {{ $errors->first() }}
            </div>
        @endif

        @if (session('info'))
            <div class="bg-primary/10 border border-primary/20 text-primary rounded-xl px-4 py-3 text-sm font-medium flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">info</span>
                <span>{{ session('info') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="bg-danger/10 border border-danger/20 text-danger rounded-xl px-4 py-3 text-sm font-medium">
                ⚠️ {{ session('error') }}
            </div>
        @endif

        <!-- Form -->
        <form method="POST" action="{{ route('verify.show') }}" autocomplete="off" class="space-y-4">
            @csrf

            <div class="space-y-2">
                <label for="code" class="block text-sm font-bold text-on-surface-variant text-center">{{ __('auto.0213') }}</label>
                <div class="relative">
                    <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9A-Za-z\-]*" required autofocus
                           placeholder="123456"
                           class="w-full px-4 py-3 text-2xl font-mono font-bold tracking-widest text-center bg-background-warm border-2 border-border-base rounded-xl focus:outline-none focus:border-primary @error('code') border-danger/60 @enderror transition-colors">
                </div>
                <p class="text-xs text-on-surface-variant text-center leading-relaxed">
                    遺失手機時，亦可在此輸入 10 位<strong>一次性恢復碼</strong>（格式如：<span class="font-mono font-bold">XXXXX-XXXXX</span>）。
                </p>
            </div>

            <button type="submit" class="w-full py-3 bg-primary hover:bg-primary/90 text-white font-bold rounded-xl shadow-md transition-all hover:scale-[1.02] flex items-center justify-center gap-2 text-base cursor-pointer">
                <span>{{ __('2fa.verify_btn') }}</span>
                <span class="material-symbols-outlined text-lg">arrow_forward</span>
            </button>
        </form>

        <div class="pt-2 border-t border-border-base text-center">
            <a href="{{ route('login') }}" class="inline-flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary transition-colors font-semibold">
                <span class="material-symbols-outlined text-base">arrow_back</span>
                <span>{{ __('auto.0225') }}</span>
            </a>
        </div>
    </main>

</body>
</html>
