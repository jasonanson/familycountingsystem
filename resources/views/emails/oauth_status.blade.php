@extends('layouts.app')
@section('title', 'Gmail OAuth2 設定狀態')

@section('content')
<div class="max-w-2xl mx-auto p-6 space-y-6">
    <div class="bg-white rounded-xl shadow p-6 border border-border-base">
        <h1 class="text-2xl font-bold text-on-surface mb-2 flex items-center gap-2">
            <span class="material-symbols-outlined text-success">mark_email_read</span>
            Gmail OAuth2 設定狀態
        </h1>
        <p class="text-sm text-on-surface-variant">{{ __('auto.0494') }}</p>
    </div>

    <div class="bg-white rounded-xl shadow p-6 border border-border-base space-y-4">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined {{ $configured ? "text-success" : "text-warning" }}">
                {{ $configured ? "check_circle" : "warning" }}
            </span>
            <div>
                <div class="font-semibold text-on-surface">
                    {{ $configured ? "已設定完成（.env 都有值）" : "尚未設定完成" }}
                </div>
                <div class="text-xs text-on-surface-variant">MAIL_MAILER = <code>{{ $mailMailer }}</code></div>
            </div>
        </div>

        <table class="w-full text-sm">
            <tr class="border-b border-border-base">
                <td class="py-2 text-on-surface-variant">GMAIL_API_CLIENT_ID</td>
                <td class="py-2 font-mono text-xs">{{ $clientId ?: "(未設定)" }}</td>
            </tr>
            <tr class="border-b border-border-base">
                <td class="py-2 text-on-surface-variant">GMAIL_API_CLIENT_MAIL</td>
                <td class="py-2 font-mono">{{ $clientEmail ?: "(授權後會自動填入)" }}</td>
            </tr>
            <tr>
                <td class="py-2 text-on-surface-variant">OAuth Redirect URI</td>
                <td class="py-2 font-mono text-xs break-all">{{ $redirectUri }}</td>
            </tr>
        </table>
    </div>

    <div class="bg-white rounded-xl shadow p-6 border border-border-base space-y-3">
        <h2 class="font-bold text-on-surface">{{ __('common.next') }}</h2>
        @if (! $configured)
            <ol class="list-decimal list-inside text-sm text-on-surface-variant space-y-2">
                <li>確認 Google Cloud Console 的 Authorized redirect URI 已設成：<br>
                    <code class="text-xs bg-surface-container-low px-2 py-1 rounded">{{ $redirectUri }}</code>
                </li>
                <li>
                    <a href="{{ $authUrl }}" class="inline-block mt-2 px-4 py-2 rounded-lg bg-success text-white font-bold hover:bg-success">
                        🔐 前往 Google 授權
                    </a>
                </li>
                <li>授權完成後，本頁會自動顯示 refresh_token，把 .env 的 GMAIL_API_CLIENT_MAIL 與 MAIL_MAILER=gmail 設定好即可。</li>
            </ol>
        @else
            <p class="text-sm text-on-surface-variant">✅ 已完成。執行 <code class="bg-surface-container-low px-2 py-1 rounded">php artisan tinker</code> 然後 <code class="bg-surface-container-low px-2 py-1 rounded">Mail::raw('hello', fn($m) => $m->to($user->email)->subject('test'));</code> 測試寄信。</p>
        @endif
    </div>

    @if ($tokenExists && $tokenPayload)
        <div class="bg-white rounded-xl shadow p-6 border border-border-base">
            <h2 class="font-bold text-on-surface mb-2">storage/app/gmail-oauth-token.json</h2>
            <pre class="text-xs bg-on-surface text-success/60 p-3 rounded overflow-auto">{{ json_encode($tokenPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    @endif
</div>
@endsection