<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('auth.login_title') }} - {{ __('app.brand') }}</title>
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

    <!-- Login Card -->
    <main class="relative z-10 w-full max-w-[420px] bg-surface-pure/90 backdrop-blur-xl border border-border-base rounded-2xl shadow-xl p-8 space-y-6">
        <!-- Logo Header -->
        <div class="flex flex-col items-center text-center space-y-2">
            <div class="w-12 h-12 bg-primary text-white rounded-2xl flex items-center justify-center shadow-md">
                <span class="material-symbols-outlined text-3xl">account_balance_wallet</span>
            </div>
            <h1 class="text-2xl font-bold text-primary tracking-tight">{{ __('app.system_name') }}</h1>
            <p class="text-sm text-on-surface-variant">{{ __('app.tagline') }}</p>
        </div>

        @if(session('error'))
            <div class="bg-danger/10 text-danger border border-danger/20 p-3 rounded-xl text-sm text-center font-medium">
                ⚠️ {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-danger/10 text-danger border border-danger/20 p-3 rounded-xl text-sm text-center font-medium">
                ⚠️ {{ $errors->first() }}
            </div>
        @endif

        <!-- Form -->
        <form action="{{ route('login') }}" method="POST" class="space-y-4">
            @csrf

            <div class="space-y-1">
                <label class="block text-sm font-bold text-on-surface-variant" for="account">{{ __('auth.account_or_email') }}</label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-2.5 text-on-surface-variant text-lg">person</span>
                    <input class="w-full pl-10 pr-4 py-2.5 bg-background-warm border border-border-base rounded-xl text-base focus:outline-none focus:border-primary" 
                           id="account" name="account" value="{{ old('account') }}" required placeholder="{{ __('auth.account_placeholder') }}">
                </div>
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-bold text-on-surface-variant" for="password">{{ __('common.password') }}</label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-2.5 text-on-surface-variant text-lg">lock</span>
                    <input class="w-full pl-10 pr-4 py-2.5 bg-background-warm border border-border-base rounded-xl text-base focus:outline-none focus:border-primary" 
                           id="password" name="password" type="password" required placeholder="{{ __('auth.password_placeholder') }}">
                </div>
            </div>

            <div class="flex items-center justify-between text-sm pt-1">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded border-border-base text-primary focus:ring-primary">
                    <span class="text-on-surface-variant">{{ __('common.remember_me') }}</span>
                </label>
                <a href="{{ route('join-family') }}" class="text-primary font-semibold hover:underline">{{ __('auth.use_invite_code') }}</a>
            </div>

            <button type="submit" class="w-full py-3 bg-primary hover:bg-primary/90 text-white font-bold rounded-xl shadow-md transition-all hover:scale-[1.02] flex items-center justify-center gap-2 text-base">
                <span>{{ __('auth.login') }}</span>
                <span class="material-symbols-outlined text-lg">arrow_forward</span>
            </button>
        </form>

        <div class="flex items-center gap-4 my-4">
            <div class="flex-1 h-px bg-border-base"></div>
            <span class="text-sm text-on-surface-variant">{{ __('common.or') }}</span>
            <div class="flex-1 h-px bg-border-base"></div>
        </div>

        <a href="{{ route('register') }}" class="block w-full py-2.5 text-center bg-background-warm border border-border-base hover:border-primary text-on-surface text-sm font-bold rounded-xl transition-colors">
            {{ __("auth.register_prompt") }}
        </a>
    </main>

</body>
</html>
