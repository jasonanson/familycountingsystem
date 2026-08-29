@extends('layouts.app')

@section('title', '系統通知廣播')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-wrap justify-between items-end gap-4">
        <div>
            <h1 class="text-2xl font-bold text-text-primary flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">campaign</span>
                系統通知廣播
            </h1>
            <p class="text-sm text-on-surface-variant mt-1">{{ __('auto.0570') }}</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-surface-pure rounded-xl border border-border-base p-4 shadow-sm">
            <div class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">使用者總數</div>
            <div class="text-2xl font-bold text-text-primary mt-1">{{ number_format($stats['total_users']) }}</div>
        </div>
        <div class="bg-surface-pure rounded-xl border border-border-base p-4 shadow-sm">
            <div class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">管理員人數</div>
            <div class="text-2xl font-bold text-warning mt-1">{{ number_format($stats['total_admins']) }}</div>
        </div>
        <div class="bg-surface-pure rounded-xl border border-border-base p-4 shadow-sm">
            <div class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">家庭總數</div>
            <div class="text-2xl font-bold text-success mt-1">{{ number_format($stats['total_families']) }}</div>
        </div>
        <div class="bg-surface-pure rounded-xl border border-border-base p-4 shadow-sm">
            <div class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">近 7 天通知</div>
            <div class="text-2xl font-bold text-primary mt-1">{{ number_format($stats['recent_sent']) }}</div>
        </div>
    </div>

    <!-- Broadcast Form -->
    <form action="{{ route('admin.notifications.broadcast') }}" method="POST" class="bg-surface-pure rounded-xl border border-border-base p-6 shadow-sm space-y-5">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="md:col-span-2 flex items-center justify-between">
                <label class="block text-sm font-semibold text-text-primary">通知標題 <span class="text-danger">*</span></label>
                <div x-data="aiBroadcastGenerator()" class="relative">
                    <button type="button" @click="openModal = true" class="text-xs text-primary hover:text-primary/80 font-bold flex items-center gap-1 bg-primary/10 px-2.5 py-1 rounded-lg transition-colors cursor-pointer">
                        <span class="material-symbols-outlined text-sm">neurology</span>
                        <span>AI 幫我寫公告</span>
                    </button>

                    {{-- AI Modal --}}
                    <div x-show="openModal" style="display: none;" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                        <div @click.outside="openModal = false" class="bg-surface-pure border border-border-base rounded-2xl p-6 shadow-2xl max-w-md w-full space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-border-base">
                                <h3 class="text-base font-bold text-on-surface flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary">neurology</span>
                                    <span>Gemini AI 公告生成小幫手</span>
                                </h3>
                                <button type="button" @click="openModal = false" class="text-on-surface-variant hover:text-on-surface">
                                    <span class="material-symbols-outlined text-base">close</span>
                                </button>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-xs font-bold text-on-surface-variant">{{ __('auto.0640') }}</label>
                                <input type="text" x-model="topic" placeholder="{{ __('auto.0140') }}" class="w-full px-3.5 py-2.5 bg-background-warm border border-border-base text-on-surface rounded-xl text-sm focus:outline-none focus:border-primary">
                            </div>
                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" @click="openModal = false" class="px-4 py-2 bg-surface-container text-on-surface rounded-xl text-xs font-bold">{{ __('common.cancel') }}</button>
                                <button type="button" @click="generate()" :disabled="loading || !topic" class="px-4 py-2 bg-primary text-white rounded-xl text-xs font-bold flex items-center gap-1 disabled:opacity-50 cursor-pointer">
                                    <span class="material-symbols-outlined text-sm" :class="loading ? 'animate-spin' : ''" x-text="loading ? 'sync' : 'auto_awesome'"></span>
                                    <span x-text="loading ? '生成中...' : '一鍵產生並帶入'"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="md:col-span-2">
                <input type="text" name="title" id="notificationTitle" required maxlength="120"
                       class="w-full px-4 py-2.5 bg-background-warm border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary"
                       placeholder="{{ __('auto.0141') }}"
                       value="{{ old('title') }}">
                @error('title')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-text-primary mb-2">通知內容 <span class="text-danger">*</span></label>
                <textarea name="body" id="notificationBody" required maxlength="1000" rows="4"
                          class="w-full px-4 py-2.5 bg-background-warm border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary resize-y"
                          placeholder="{{ __('auto.0663') }}">{{ old('body') }}</textarea>
                @error('body')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-text-primary mb-2">{{ __('auto.0680') }}</label>
                <select name="type" class="w-full px-4 py-2.5 bg-background-warm border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                    <option value="info">ℹ️ 一般資訊</option>
                    <option value="success">✅ 成功訊息</option>
                    <option value="warning">⚠️ 警告訊息</option>
                    <option value="danger">🚨 重要提醒</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-text-primary mb-2">{{ __('auto.0523') }}</label>
                <select name="channel" class="w-full px-4 py-2.5 bg-background-warm border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                    <option value="database_email">{{ __('auto.0561') }}</option>
                    <option value="database">{{ __('auto.0156') }}</option>
                    <option value="email">{{ __('auto.0153') }}</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-text-primary mb-2">{{ __('auto.0522') }}</label>
                <select name="target" id="targetSelect" class="w-full px-4 py-2.5 bg-background-warm border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                    <option value="all">🌐 全站所有使用者</option>
                    <option value="admins">👑 僅管理員</option>
                    <option value="family">👨‍👩‍👧 指定家庭的所有成員</option>
                    <option value="none_in_family">🚫 指定家庭的「非」成員</option>
                    <option value="no_family">👤 尚未加入任何家庭的使用者</option>
                </select>
            </div>

            <div id="familySelectWrap" style="display: none;">
                <label class="block text-sm font-semibold text-text-primary mb-2">{{ __('family.select_family') }}</label>
                <select name="family_id" id="familySelect" class="w-full px-4 py-2.5 bg-background-warm border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                    <option value="">{{ __('auto.0643') }}</option>
                    @foreach($families as $f)
                        <option value="{{ $f->id }}">{{ $f->name }}</option>
                    @endforeach
                </select>
                @error('family_id')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
            </div>
        </div>

        <!-- Warning Box -->
        <div class="bg-warning/10 border border-warning/30 rounded-xl p-4 flex items-start gap-3">
            <span class="material-symbols-outlined text-warning text-[24px] shrink-0">warning</span>
            <div class="text-xs text-text-primary">
                <strong class="text-warning">⚠️ 注意：</strong>廣播通知會立即發送給所有目標使用者並寄送 Email，無法撤回。請確認內容與對象無誤後再送出。
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 pt-2">
            <button type="reset" class="px-5 py-2.5 bg-surface-container text-on-surface-variant rounded-xl text-sm font-semibold hover:bg-surface-container-high transition-colors">
                清空
            </button>
            <button type="submit" class="px-5 py-2.5 bg-primary text-white rounded-xl text-sm font-semibold hover:bg-primary-container transition-colors flex items-center gap-2 shadow-sm">
                <span class="material-symbols-outlined text-[18px]">send</span>
                發送廣播
            </button>
        </div>
    </form>
</div>

<script>
    const targetSelect = document.getElementById('targetSelect');
    const familyWrap = document.getElementById('familySelectWrap');
    const familySelect = document.getElementById('familySelect');
    
    function toggleFamilySelect() {
        const v = targetSelect.value;
        if (v === 'family' || v === 'none_in_family') {
            familyWrap.style.display = 'block';
            familySelect.required = true;
        } else {
            familyWrap.style.display = 'none';
            familySelect.required = false;
        }
    }
    
    targetSelect.addEventListener('change', toggleFamilySelect);
    toggleFamilySelect();

    function aiBroadcastGenerator() {
        return {
            openModal: false,
            topic: '',
            loading: false,
            generate() {
                if (!this.topic) return;
                this.loading = true;

                fetch("{{ route('admin.ai.broadcast') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ topic: this.topic })
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success && data.data) {
                        var titleInput = document.getElementById('notificationTitle');
                        var bodyInput = document.getElementById('notificationBody');
                        if (titleInput) titleInput.value = data.data.title || '';
                        if (bodyInput) bodyInput.value = data.data.body || '';
                        this.openModal = false;
                        this.topic = '';
                    } else {
                        alert('AI 生成失敗：' + (data.message || '未知錯誤'));
                    }
                }.bind(this))
                .catch(function(err) {
                    alert('請求失敗：' + err.message);
                })
                .finally(function() {
                    this.loading = false;
                }.bind(this));
            }
        };
    }
</script>
@endsection
