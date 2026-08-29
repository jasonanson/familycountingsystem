<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('auth.register_title') }} - {{ __('app.brand') }}</title>
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
<body class="min-h-screen bg-background-warm text-on-surface antialiased relative overflow-y-auto py-8 px-4 flex items-center justify-center">

    <div class="fixed inset-0 z-0 pointer-events-none overflow-hidden bg-gradient-to-br from-primary/10 to-background-warm">
        <div class="absolute top-[-10%] left-[-10%] w-[50vw] h-[50vw] rounded-full bg-primary/10 blur-[100px]"></div>
    </div>

    <main class="relative z-10 w-full max-w-[480px] bg-surface-pure/90 backdrop-blur-xl border border-border-base rounded-2xl shadow-xl p-8 space-y-6">
        <div class="flex flex-col items-center text-center space-y-2">
            <div class="w-12 h-12 bg-primary text-white rounded-2xl flex items-center justify-center shadow-md">
                <span class="material-symbols-outlined text-3xl">person_add</span>
            </div>
            <h1 class="text-2xl font-bold text-primary tracking-tight">{{ __('auth.register_title') }}</h1>
            <p class="text-sm text-on-surface-variant">{{ __('auth.register_subtitle') }}</p>
        </div>

        @if(isset($invitation) && $invitation)
            <div class="bg-primary/10 border border-primary/20 p-3 rounded-xl text-sm text-primary flex items-center gap-2 font-medium">
                <span class="material-symbols-outlined text-lg">mark_email_read</span>
                <span>{{ __('auth.invitation_notice', ['family' => $invitation->family->name]) }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="bg-danger/10 text-danger border border-danger/20 p-3 rounded-xl text-sm text-center font-medium">
                ⚠️ {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('register') }}" method="POST" class="space-y-4">
            @csrf
            @if(request('token') || isset($token))
                <input type="hidden" name="token" value="{{ old('token', request('token') ?? ($token ?? '')) }}">
            @endif

            <input type="hidden" name="registration_role" value="{{ old('registration_role', ((isset($invitation) && $invitation) || request('token')) ? 'member' : 'parent') }}">

            <div class="space-y-1">
                <label class="block text-sm font-bold text-on-surface-variant">{{ __('auth.name') }} <span class="text-danger">*</span></label>
                <input class="w-full px-4 py-2.5 bg-background-warm border border-border-base rounded-xl text-base focus:outline-none focus:border-primary" 
                       name="name" value="{{ old('name') }}" required placeholder="{{ __('auth.name_placeholder_ext') }}">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1">
                    <label class="block text-sm font-bold text-on-surface-variant">{{ __('common.account') }} <span class="text-danger">*</span></label>
                    <input class="w-full px-4 py-2.5 bg-background-warm border border-border-base rounded-xl text-base focus:outline-none focus:border-primary" 
                           name="account" value="{{ old('account') }}" required placeholder="{{ __('common.account') }}">
                </div>
                <div class="space-y-1">
                    <label class="block text-sm font-bold text-on-surface-variant">{{ __('common.email') }} <span class="text-danger">*</span></label>
                    <input class="w-full px-4 py-2.5 bg-background-warm border border-border-base rounded-xl text-base focus:outline-none focus:border-primary" 
                           type="email" name="email" value="{{ old('email', $invitation->email ?? '') }}" required placeholder="{{ __('common.email') }}">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1">
                    <label class="block text-sm font-bold text-on-surface-variant">{{ __('common.password') }} <span class="text-danger">*</span></label>
                    <input class="w-full px-4 py-2.5 bg-background-warm border border-border-base rounded-xl text-base focus:outline-none focus:border-primary" 
                           type="password" name="password" required placeholder="{{ __('auth.password_min_placeholder') }}">
                </div>
                <div class="space-y-1">
                    <label class="block text-sm font-bold text-on-surface-variant">{{ __('auth.password_confirm') }} <span class="text-danger">*</span></label>
                    <input class="w-full px-4 py-2.5 bg-background-warm border border-border-base rounded-xl text-base focus:outline-none focus:border-primary" 
                           type="password" name="password_confirmation" required placeholder="{{ __('auth.password_confirm_placeholder') }}">
                </div>
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-bold text-on-surface-variant">{{ __('auth.invite_code') }} ({{ __('common.optional') }})</label>
                <input class="w-full px-4 py-2.5 bg-background-warm border border-border-base rounded-xl text-base focus:outline-none focus:border-primary uppercase font-mono" 
                       name="invite_code" value="{{ old('invite_code', request('invite_code')) }}"
                       pattern="[A-Za-z0-9]*"
                       oninput="this.value = this.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();" 
                       placeholder="{{ __('auto.0155') }}">
                <p class="text-[11px] text-on-surface-variant">{{ __("auth.invite_code_format") }}</p>
            </div>

            <!-- 家長認證碼輸入欄位 (常駐顯示) -->
            <div class="space-y-1 p-3 bg-primary/5 border border-primary/20 rounded-xl">
                <label class="block text-sm font-bold text-primary flex items-center justify-between">
                    <span class="flex items-center gap-1">
                        <span class="material-symbols-outlined text-base">verified_user</span>
                        {{ __("auth.parent_code") }} (Parent Code)
                        @if(!((isset($invitation) && $invitation) || request('token')))
                            <span class="text-danger">*</span>
                        @endif
                    </span>
                    @if((isset($invitation) && $invitation) || request('token'))
                        <span class="text-xs text-on-surface-variant font-normal">({{ __("common.optional") }})</span>
                    @endif
                </label>
                <input class="w-full px-4 py-2 bg-white border border-border-base rounded-xl text-base focus:outline-none focus:border-primary uppercase font-mono" 
                       name="parent_code" value="{{ old('parent_code') }}"
                       @if(!((isset($invitation) && $invitation) || request('token'))) required @endif
                       placeholder="{{ __('auth.parent_code_placeholder') }}">
                <p class="text-[11px] text-on-surface-variant">
                    @if((isset($invitation) && $invitation) || request('token'))
                        {{ __("auth.parent_code_invite_mode") }}
                    @else
                        {{ __("auth.parent_code_help") }}
                    @endif
                </p>
            </div>

            <button type="submit" class="w-full py-3 bg-primary hover:bg-primary/90 text-white font-bold rounded-xl shadow-md transition-all hover:scale-[1.02] flex items-center justify-center gap-2 text-base">
                <span>{{ __('common.create') }}</span>
                <span class="material-symbols-outlined text-lg">arrow_forward</span>
            </button>
        </form>

        <div class="text-center text-sm text-on-surface-variant">
            {{ __("auth.have_account") }}
            <a href="{{ route('login') }}" class="text-primary font-bold hover:underline ml-1">{{ __('auth.login') }}</a>
        </div>
    </main>

</body>
</html>
