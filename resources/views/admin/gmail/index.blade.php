@extends('layouts.app')

@section('content')
@php
    use Illuminate\Support\Facades\Crypt;
    $status = $status ?? ['is_connected' => false, 'email' => null, 'client_id_full' => null];
@endphp

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold text-text-primary tracking-tight">📧 Gmail 連線設定</h1>
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-on-surface-variant hover:text-primary">
            ← 返回後台
        </a>
    </div>

    {{-- 狀態卡片 --}}
    <div class="rounded-2xl p-6 border {{ $status['is_connected'] ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800' }}">
        <div class="flex items-start gap-4">
            <div class="text-4xl">{{ $status['is_connected'] ? '✅' : '⚠️' }}</div>
            <div class="flex-1">
                <div class="text-xl font-semibold {{ $status['is_connected'] ? 'text-green-800 dark:text-green-200' : 'text-yellow-800 dark:text-yellow-200' }}">
                    {{ $status['is_connected'] ? '已連線' : '尚未連線' }}
                </div>
                @if($status['is_connected'])
                    <div class="mt-2 text-sm text-green-700 dark:text-green-300 space-y-1">
                        <div>寄件帳號：<code class="bg-green-100 dark:bg-green-900/40 px-2 py-0.5 rounded">{{ $status['email'] }}</code></div>
                        <div>連線時間：{{ $status['connected_at'] }}</div>
                    </div>
                @else
                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                        下面的 email 通知偏好會被儲存，但實際不會寄信。完成下方設定後即可啟用寄信功能。
                    </div>
                @endif
                @if($status['env_fallback_used'] ?? false)
                    <div class="mt-3 px-3 py-2 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200 text-xs">
                        ℹ️ 目前使用 <code>.env</code> 設定（向下相容）。建議移到資料庫以便在後台管理。
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 text-green-800 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-lg p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 text-red-800 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    {{-- 1. OAuth Client 憑證 --}}
    <div class="bg-surface-pure rounded-2xl p-6 border border-border-base shadow-sm">
        <h2 class="text-lg font-semibold text-text-primary mb-4">🔑 OAuth Client 憑證</h2>
        <p class="text-sm text-on-surface-variant mb-4">
            到 <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="underline">Google Cloud Console → Credentials</a> 建立 OAuth 2.0 Client ID（Web application 類型）。
        </p>

        <form method="POST" action="{{ route('admin.gmail.save') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-text-primary mb-1">Client ID</label>
                <input type="text" name="client_id" required maxlength="255"
                       value="{{ old('client_id', $status['client_id_full']) }}"
                       placeholder="xxxxxx.apps.googleusercontent.com"
                       class="w-full px-3 py-2 border border-border-base rounded-lg bg-surface-pure text-text-primary focus:outline-none focus:ring-2 focus:ring-primary">
            </div>

            <div>
                <label class="block text-sm font-medium text-text-primary mb-1">Client Secret</label>
                <input type="password" name="client_secret" required maxlength="255"
                       placeholder="GOCSPX-…"
                       class="w-full px-3 py-2 border border-border-base rounded-lg bg-surface-pure text-text-primary focus:outline-none focus:ring-2 focus:ring-primary">
                <p class="mt-1 text-xs text-on-surface-variant">存進資料庫前會用 Laravel Crypt（APP_KEY 為基礎）加密</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-text-primary mb-1">Authorized Redirect URI</label>
                <input type="url" name="redirect_uri" required maxlength="255"
                       value="{{ old('redirect_uri', $status['redirect_uri']) }}"
                       class="w-full px-3 py-2 border border-border-base rounded-lg bg-surface-pure text-text-primary focus:outline-none focus:ring-2 focus:ring-primary">
                <p class="mt-1 text-xs text-on-surface-variant">
                    ⚠️ 這串 URI 也必須到 Google Cloud Console 的「Authorized redirect URIs」註冊，否則 Google 會擋下 callback
                </p>
            </div>

            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-container transition-colors">
                💾 儲存 OAuth 憑證
            </button>
        </form>
    </div>

    {{-- 2. 連接 Gmail --}}
    <div class="bg-surface-pure rounded-2xl p-6 border border-border-base shadow-sm">
        <h2 class="text-lg font-semibold text-text-primary mb-4">🔗 連接 Gmail</h2>
        <p class="text-sm text-on-surface-variant mb-4">
            按下方按鈕會導向 Google 授權頁。登入你要用來寄信的 Gmail 帳號，同意授權後會自動回到後台。
        </p>
        <form method="GET" action="{{ route('admin.gmail.connect') }}">
            <button type="submit" {{ empty($status['client_id_full']) ? 'disabled' : '' }}
                    class="px-4 py-2 {{ empty($status['client_id_full']) ? 'bg-gray-300 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700' }} text-white rounded-lg transition-colors">
                🔗 連接 Gmail 帳號
            </button>
        </form>
        @if(empty($status['client_id_full']))
            <p class="mt-2 text-xs text-orange-600">請先填寫並儲存 OAuth Client 憑證，才能開始連接</p>
        @endif
    </div>

    {{-- 3. 寄測試信 --}}
    <div class="bg-surface-pure rounded-2xl p-6 border border-border-base shadow-sm {{ ! $status['is_connected'] ? 'opacity-60' : '' }}">
        <h2 class="text-lg font-semibold text-text-primary mb-4">📨 寄一封測試信</h2>
        <p class="text-sm text-on-surface-variant mb-4">驗證 OAuth 連線是否正常。</p>
        <form method="POST" action="{{ route('admin.gmail.test') }}" class="flex gap-2">
            @csrf
            <input type="email" name="to" required
                   placeholder="收件人 email"
                   class="flex-1 px-3 py-2 border border-border-base rounded-lg bg-surface-pure text-text-primary focus:outline-none focus:ring-2 focus:ring-primary"
                   {{ ! $status['is_connected'] ? 'disabled' : '' }}>
            <button type="submit" {{ ! $status['is_connected'] ? 'disabled' : '' }}
                    class="px-4 py-2 {{ ! $status['is_connected'] ? 'bg-gray-300 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700' }} text-white rounded-lg transition-colors whitespace-nowrap">
                寄出
            </button>
        </form>
    </div>

    {{-- 4. 斷開連線 --}}
    @if($status['is_connected'])
        <div class="bg-surface-pure rounded-2xl p-6 border border-red-200 shadow-sm">
            <h2 class="text-lg font-semibold text-red-600 mb-4">🗑 斷開 Gmail 連線</h2>
            <p class="text-sm text-on-surface-variant mb-4">
                會清除資料庫裡所有 Gmail 相關設定（Client ID / Secret / Refresh Token）。之後要寄信需要重新連線。
            </p>
            <form method="POST" action="{{ route('admin.gmail.disconnect') }}"
                  onsubmit="return confirm('確定要斷開 Gmail 連線嗎？此操作無法復原。');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    斷開連線
                </button>
            </form>
        </div>
    @endif

    {{-- 使用者通知偏好連結 --}}
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-2xl p-6 border border-blue-200 dark:border-blue-800">
        <h2 class="text-lg font-semibold text-text-primary mb-2">👤 個別使用者通知偏好</h2>
        <p class="text-sm text-on-surface-variant mb-3">
            每位使用者可以在自己的「<a href="{{ url('/settings/notifications') }}" class="underline">通知設定</a>」頁面決定哪些事件要 email 通知、哪些只要站內通知。
        </p>
        <p class="text-xs text-on-surface-variant">
            範例事件：預算超支、訂閱提醒、家庭邀請、兒童超限等。
        </p>
    </div>
</div>
@endsection
