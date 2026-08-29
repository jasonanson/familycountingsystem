@extends('layouts.app', ['title' => 'AI 智能設定 (Token 管理)'])

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    {{-- 頁面標頭 --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-surface-pure border border-border-base p-6 rounded-2xl shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary shrink-0">
                <span class="material-symbols-outlined text-2xl">neurology</span>
            </div>
            <div>
                <h1 class="text-xl font-bold text-on-surface flex items-center gap-2">
                    <span>Google Gemini AI 智能設定</span>
                    <span class="text-xs bg-primary/15 text-primary px-2.5 py-0.5 rounded-full font-bold">{{ __('auto.0407') }}</span>
                </h1>
                <p class="text-xs text-on-surface-variant mt-0.5">
                    由系統最高管理員（is_system_admin）填寫 API Token，全域 AI 功能（月報分析、超支提醒、廣播生成）將共享此設定。
                </p>
            </div>
        </div>
    </div>

    {{-- 提示訊息 --}}
    @if(session('success'))
        <div class="p-4 rounded-xl bg-success/15 border border-success/30 text-success text-sm flex items-center gap-2 font-medium">
            <span class="material-symbols-outlined text-lg">check_circle</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- 設定表單 --}}
    <form action="{{ route('admin.ai.update') }}" method="POST" class="space-y-6">
        @csrf

        <div class="bg-surface-pure border border-border-base rounded-2xl p-6 shadow-sm space-y-6">
            <h2 class="text-base font-bold text-on-surface flex items-center gap-2 border-b border-border-base pb-3">
                <span class="material-symbols-outlined text-primary">key</span>
                <span>API 憑證與模型配置</span>
            </h2>

            {{-- AI 開關 --}}
            <div class="flex items-center justify-between p-4 bg-surface-container rounded-xl border border-border-base">
                <div>
                    <label for="ai_enabled" class="text-sm font-bold text-on-surface cursor-pointer">{{ __('auto.0238') }}</label>
                    <p class="text-xs text-on-surface-variant mt-0.5">{{ __('auto.0713') }}</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="ai_enabled" id="ai_enabled" value="1" {{ $aiEnabled === '1' ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-11 h-6 bg-surface-container-high peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-border-base after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                </label>
            </div>

            {{-- Gemini API Token --}}
            <div x-data="{ showKey: false }" class="space-y-2">
                <div class="flex items-center justify-between">
                    <label for="gemini_api_key" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                        Google Gemini API Key / Token <span class="text-danger">*</span>
                    </label>
                    <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener noreferrer" class="text-xs text-primary hover:underline flex items-center gap-1 font-medium">
                        <span>{{ __('auto.0205') }}</span>
                        <span class="material-symbols-outlined text-sm">open_in_new</span>
                    </a>
                </div>
                <div class="relative">
                    <input :type="showKey ? 'text' : 'password'" 
                           name="gemini_api_key" 
                           id="gemini_api_key"
                           value="{{ old('gemini_api_key', $apiKey) }}" 
                           placeholder="AIzaSy..."
                           class="w-full bg-background-warm border border-border-base text-on-surface text-sm rounded-xl pl-3.5 pr-24 py-2.5 focus:outline-none focus:border-primary font-mono transition-colors">
                    <button type="button" 
                            @click="showKey = !showKey" 
                            class="absolute right-3 top-2.5 text-xs text-on-surface-variant hover:text-primary flex items-center gap-1 font-medium cursor-pointer">
                        <span class="material-symbols-outlined text-base" x-text="showKey ? 'visibility_off' : 'visibility'"></span>
                        <span x-text="showKey ? '隱藏' : '顯示'"></span>
                    </button>
                </div>
                <p class="text-[11px] text-on-surface-variant">
                    💡 提示：API Token 將安全保存在系統設定中。所有最高管理員均可在此共同查看與變更。
                </p>
            </div>

            {{-- Gemini 模型選擇 --}}
            <div class="space-y-2">
                <label for="gemini_model" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                    AI 模型選擇（預設：Gemini 3.5 Flash Lite）
                </label>
                <select name="gemini_model" id="gemini_model" class="w-full bg-background-warm border border-border-base text-on-surface text-sm rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-primary transition-colors cursor-pointer">
                    @foreach($availableModels as $key => $label)
                        <option value="{{ $key }}" {{ $model === $key ? 'selected' : '' }}>{{ $label }} ({{ $key }})</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-on-surface-variant">
                    ⚡ 建議選用 <strong class="text-primary">Gemini 3.5 Flash Lite</strong>，具備極高的回應速度與極佳的繁體中文財務分析品質。
                </p>
            </div>

            {{-- 連線測試按鈕 --}}
            <div x-data="aiConnectionTester()" class="pt-2 border-t border-border-base">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div>
                        <span class="text-xs font-bold text-on-surface">API 連線診斷</span>
                        <p class="text-[11px] text-on-surface-variant">{{ __('auto.0559') }}</p>
                    </div>
                    <button type="button" 
                            @click="test()"
                            :disabled="loading"
                            class="px-4 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface border border-border-base rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all cursor-pointer disabled:opacity-50">
                        <span class="material-symbols-outlined text-sm" :class="loading ? 'animate-spin' : ''" x-text="loading ? 'sync' : 'network_ping'"></span>
                        <span x-text="loading ? '正在測試連線...' : '測試 API 連線'"></span>
                    </button>
                </div>

                <div x-show="result" x-transition class="mt-3 p-3.5 rounded-xl text-xs font-medium border"
                     :class="result?.success ? 'bg-success/10 border-success/30 text-success' : 'bg-danger/10 border-danger/30 text-danger'">
                    <div class="flex items-start gap-2">
                        <span class="material-symbols-outlined text-base shrink-0 mt-0.5" x-text="result?.success ? 'check_circle' : 'error'"></span>
                        <div class="flex-1">
                            <span class="font-bold" x-text="result?.success ? '連線成功！' : '連線失敗'"></span>
                            <p class="mt-0.5" x-text="result?.message"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 儲存按鈕 --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.dashboard') }}" class="px-5 py-2.5 bg-surface-container hover:bg-surface-container-high text-on-surface rounded-xl text-sm font-bold border border-border-base transition-colors">
                返回後台
            </a>
            <button type="submit" class="px-6 py-2.5 bg-primary hover:bg-primary/90 text-white rounded-xl text-sm font-bold shadow-md hover:scale-[1.01] active:scale-95 transition-all flex items-center gap-2 cursor-pointer">
                <span class="material-symbols-outlined text-lg">save</span>
                <span>{{ __('auto.0163') }}</span>
            </button>
        </div>
    </form>
</div>

<script>
function aiConnectionTester() {
    return {
        loading: false,
        result: null,
        test() {
            var keyInput = document.getElementById('gemini_api_key');
            var modelInput = document.getElementById('gemini_model');
            var key = keyInput ? keyInput.value : '';
            var model = modelInput ? modelInput.value : '';

            if (!key) {
                this.result = { success: false, message: '請先在上方輸入 API Token 再進行測試' };
                return;
            }

            this.loading = true;
            this.result = null;

            fetch("{{ route('admin.ai.test') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ api_key: key, model: model })
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                this.result = data;
            }.bind(this))
            .catch(function(err) {
                this.result = { success: false, message: '請求發生網路錯誤：' + err.message };
            }.bind(this))
            .finally(function() {
                this.loading = false;
            }.bind(this));
        }
    };
}
</script>
@endsection
