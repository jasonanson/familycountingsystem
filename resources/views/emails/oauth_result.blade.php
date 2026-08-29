@extends('layouts.app')
@section('title', $success ? '授權成功' : '授權失敗')

@section('content')
<div class="max-w-2xl mx-auto p-6 space-y-6">
    <div class="bg-white rounded-xl shadow p-8 border border-border-base text-center">
        <div class="mb-4">
            <span class="material-symbols-outlined {{ $success ? "text-success" : "text-danger" }}" style="font-size:64px;">
                {{ $success ? "verified" : "error" }}
            </span>
        </div>
        <h1 class="text-2xl font-bold text-on-surface mb-3">
            {{ $success ? "✅ Gmail OAuth2 授權成功" : "❌ 授權未完成" }}
        </h1>
        <p class="text-on-surface-variant mb-6">{{ $message }}</p>

        @if ($success && $refreshToken)
            <div class="bg-success/10 border border-success/20 rounded-lg p-4 text-left">
                <p class="font-bold text-success mb-1">連線資訊已自動儲存</p>
                <p class="text-xs text-success/80">
                    refresh_token 與授權帳號已寫入 <code>.env</code> 和 <code>storage/app/gmail-oauth-token.json</code>，無需手動抄寫。
                </p>
            </div>
        @endif

        @if (isset($email) && $email)
            <p class="mt-4 text-sm text-on-surface-variant">授權帳號：<strong>{{ $email }}</strong></p>
        @endif

        <a href="{{ route('admin.gmail.index') }}" class="inline-block mt-6 px-5 py-2.5 rounded-xl bg-primary text-white font-bold shadow hover:bg-primary/90 transition-colors">
            ← 回 Gmail 連線設定
        </a>
    </div>
</div>
@endsection
