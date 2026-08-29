@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-on-surface">{{ __('auto.0183') }}</h2>
            <p class="text-sm text-on-surface-variant mt-1">{{ __('auto.0476') }}</p>
        </div>
        <span class="material-symbols-outlined text-primary text-4xl">shield_lock</span>
    </div>

    @if (session('success'))
        <div class="bg-success/10 border border-success/20 text-success rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="bg-danger/10 border border-danger/20 text-danger rounded-xl px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif
    @if (session('info'))
        <div class="bg-warning/10 border border-warning/20 text-warning rounded-xl px-4 py-3 text-sm">{{ session('info') }}</div>
    @endif

    @if ($user->two_factor_enabled)
        {{-- 已啟用狀態 --}}
        <div class="bg-surface-pure rounded-2xl p-card-padding border border-border-base shadow-sm">
            <div class="flex items-center gap-3 mb-4">
                <span class="material-symbols-outlined text-success">check_circle</span>
                <h3 class="font-bold text-lg">2FA 已啟用</h3>
                <span class="ml-auto text-xs text-on-surface-variant">啟用於 {{ optional($user->two_factor_confirmed_at)->format('Y/m/d H:i') }}</span>
            </div>
            <p class="text-sm text-on-surface-variant mb-4">{{ __('auto.0460') }}</p>

            <details class="mb-4 bg-surface rounded-xl p-4 border border-border-base">
                <summary class="font-bold text-sm cursor-pointer">顯示目前剩餘恢復碼數量</summary>
                <p class="mt-3 text-sm">
                    @php
                        $remaining = is_array($user->two_factor_recovery_codes) ? count($user->two_factor_recovery_codes) : 0;
                    @endphp
                    目前剩餘 <strong>{{ $remaining }}</strong> 組（原始 10 組）。
                    @if ($remaining <= 3)
                        <span class="text-danger font-bold ml-1">⚠️ 建議重新產生</span>
                    @endif
                </p>
            </details>

            <form method="POST" action="{{ route('settings.2fa.regenerate-recovery') }}" class="border-t border-border-base pt-4 mt-4">
                @csrf
                <h4 class="font-bold text-sm mb-2">{{ __('auto.0699') }}</h4>
                <div class="flex gap-2">
                    <input type="password" name="password" required placeholder="{{ __('auto.0641') }}"
                           class="flex-1 px-3 py-2 border border-on-surface-variant/30 rounded-lg text-sm">
                    <button type="submit" class="px-4 py-2 bg-warning hover:bg-warning text-white rounded-lg text-sm font-bold">
                        重新產生
                    </button>
                </div>
            </form>

            <form method="POST" action="{{ route('settings.2fa.disable') }}" class="border-t border-border-base pt-4 mt-4">
                @csrf
                <h4 class="font-bold text-sm mb-2 text-danger">{{ __('auto.0712') }}</h4>
                <div class="flex gap-2">
                    <input type="password" name="password" required placeholder="{{ __('auto.0641') }}"
                           class="flex-1 px-3 py-2 border border-on-surface-variant/30 rounded-lg text-sm">
                    <button type="submit" class="px-4 py-2 bg-danger hover:bg-danger text-white rounded-lg text-sm font-bold">{{ __('auto.0712') }}</button>
                </div>
            </form>
        </div>

        @if (session('new_recovery_codes'))
            <div class="bg-warning/10 border-2 border-warning/40 rounded-2xl p-card-padding">
                <h3 class="font-bold text-warning mb-2 flex items-center gap-2">
                    <span class="material-symbols-outlined">key</span>
                    新的 10 組恢復碼（僅顯示一次，請立即抄下）
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-2 font-mono text-sm">
                    @foreach (session('new_recovery_codes') as $code)
                        <code class="bg-white border border-warning/30 rounded px-2 py-1 text-center">{{ $code }}</code>
                    @endforeach
                </div>
            </div>
        @endif
    @elseif ($pendingSecret)
        {{-- 待確認狀態：使用者已點「啟用」但尚未輸入 OTP --}}
        @php
            $plainSecret = decrypt($pendingSecret);
            $qrUrl = app(\App\Services\TwoFactor\TotpService::class)->qrCodeUrl($plainSecret, $user->email);
            $qrImg = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=' . urlencode($qrUrl);
        @endphp
        <div class="bg-surface-pure rounded-2xl p-card-padding border-2 border-warning/30 shadow-sm">
            <div class="flex items-center gap-3 mb-4">
                <span class="material-symbols-outlined text-warning">pending</span>
                <h3 class="font-bold text-lg">{{ __('auto.0447') }}</h3>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <div class="text-center">
                    <p class="text-sm text-on-surface-variant mb-3">{{ __('auto.0114') }}</p>
                    <img src="{{ $qrImg }}" alt="2FA QR Code" class="mx-auto border-2 border-border-base rounded-xl w-60 h-60">
                    <p class="text-xs text-on-surface-variant mt-3">或手動輸入 secret：<br><code class="font-mono bg-surface-container-low px-2 py-1 rounded">{{ $plainSecret }}</code></p>
                </div>

                <div>
                    <form method="POST" action="{{ route('settings.2fa.confirm') }}">
                        @csrf
                        <label class="block text-sm font-bold text-on-surface-variant mb-2">{{ __('auto.0659') }}</label>
                        <input type="text" name="code" inputmode="numeric" pattern="\d{6}" required autofocus
                               placeholder="123456"
                               class="w-full px-4 py-3 text-2xl font-mono font-bold tracking-widest text-center border-2 border-on-surface-variant/30 rounded-xl focus:outline-none focus:border-primary @error('code') border-danger/40 @enderror">
                        @error('code')
                            <p class="text-danger text-xs mt-1">{{ $message }}</p>
                        @enderror

                        <button type="submit" class="mt-4 w-full px-5 py-3 bg-primary hover:bg-primary/90 text-white font-bold rounded-xl">
                            確認啟用
                        </button>
                    </form>

                    @if ($pendingRecoveryCodes)
                        <div class="mt-6 bg-warning/10 border border-warning/30 rounded-xl p-4">
                            <h4 class="font-bold text-sm text-warning mb-2 flex items-center gap-1">
                                <span class="material-symbols-outlined text-base">key</span>
                                10 組恢復碼（請抄下保存）
                            </h4>
                            <p class="text-xs text-warning mb-2">{{ __('auto.0694') }}</p>
                            <div class="grid grid-cols-2 gap-1 font-mono text-xs">
                                @foreach ($pendingRecoveryCodes as $code)
                                    <code class="bg-white px-2 py-1 rounded border border-warning/20">{{ $code }}</code>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @else
        {{-- 未啟用狀態 --}}
        <div class="bg-surface-pure rounded-2xl p-card-padding border border-border-base shadow-sm">
            <div class="flex items-center gap-3 mb-4">
                <span class="material-symbols-outlined text-on-surface-variant/70">shield_lock</span>
                <h3 class="font-bold text-lg">2FA 目前未啟用</h3>
            </div>
            <p class="text-sm text-on-surface-variant mb-4">
                啟用 2FA 後，即使密碼外洩，沒有手機上的驗證碼也無法登入您的帳號。
                支援 <strong>Google Authenticator</strong>、<strong>Authy</strong>、<strong>Microsoft Authenticator</strong> 等所有 TOTP 相容 App。
            </p>

            <form method="POST" action="{{ route('settings.2fa.enable') }}">
                @csrf
                <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary/90 text-white font-bold rounded-xl shadow-md">
                    啟用兩步驟驗證
                </button>
            </form>
        </div>
    @endif
</div>
@endsection
