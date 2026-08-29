<x-app-layout>
    <x-slot name="title">{{ __('auto.0285') }}</x-slot>

    <div class="min-h-screen bg-background-warm">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            {{-- 頁面標題 --}}
            <div class="bg-surface-pure rounded-2xl p-6 shadow-sm border border-border-base">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-primary/10 border border-primary/20 rounded-2xl flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined text-3xl">family_restroom</span>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-on-surface">{{ __('settings_page.family') }}</h1>
                        <p class="text-sm text-on-surface-variant mt-0.5">{{ __('auto.0571') }}</p>
                    </div>
                </div>
            </div>

            @if(!$family)
                <div class="bg-surface-pure rounded-2xl p-6 shadow-sm border border-border-base text-center text-on-surface-variant">
                    <span class="material-symbols-outlined text-5xl mb-3 block">info</span>
                    您目前尚未屬於任何家庭,請先建立或加入家庭。
                    <div class="mt-4">
                        <a href="{{ route('dashboard') }}" class="text-primary font-bold hover:underline">{{ __('auto.0673') }}</a>
                    </div>
                </div>
            @else
                {{-- 家庭資訊 --}}
                <div class="bg-surface-pure rounded-2xl p-6 shadow-sm border border-border-base">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="material-symbols-outlined text-primary text-xl">info</span>
                        <h2 class="text-lg font-bold text-on-surface">{{ __('auto.0278') }}</h2>
                    </div>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div class="bg-surface-container-low rounded-xl p-3">
                            <dt class="text-on-surface-variant text-xs">家庭 ID</dt>
                            <dd class="font-mono text-base text-on-surface mt-1">#{{ $family->id }}</dd>
                        </div>
                        <div class="bg-surface-container-low rounded-xl p-3">
                            <dt class="text-on-surface-variant text-xs">建立時間</dt>
                            <dd class="text-on-surface mt-1">{{ $family->created_at?->format('Y-m-d H:i') }}</dd>
                        </div>
                        <div class="bg-surface-container-low rounded-xl p-3">
                            <dt class="text-on-surface-variant text-xs">家庭邀請碼</dt>
                            <dd class="font-mono text-base text-primary mt-1 tracking-widest">{{ $family->invite_code ?? '尚未生成' }}</dd>
                        </div>
                        <div class="bg-surface-container-low rounded-xl p-3">
                            <dt class="text-on-surface-variant text-xs">家庭總預算池</dt>
                            <dd class="font-bold text-lg text-primary mt-1">
                                {{ number_format($family->total_pool_amount ?? 0, 2) }} {{ $family->currency }}
                            </dd>
                        </div>
                    </dl>
                </div>

                {{-- 家庭設定表單(只有家長能編輯) --}}
                <div class="bg-surface-pure rounded-2xl p-6 shadow-sm border border-border-base">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="material-symbols-outlined text-primary text-xl">settings</span>
                        <h2 class="text-lg font-bold text-on-surface">{{ __('auto.0590') }}</h2>
                    </div>

                    @if($currentUserRole === 'parent')
                        <form action="{{ route('settings.family.update') }}" method="POST" class="space-y-4">
                            @csrf
                            @method('PUT')

                            <div>
                                <label class="block text-sm font-bold text-on-surface-variant mb-1">家庭名稱 <span class="text-danger">*</span></label>
                                <input type="text" name="name" value="{{ old('name', $family->name) }}" required maxlength="255"
                                       class="w-full px-4 py-2.5 bg-surface-pure border border-border-base rounded-xl text-base focus:outline-none focus:border-primary">
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-on-surface-variant mb-1">預設幣別 <span class="text-danger">*</span></label>
                                    <select name="currency" required class="w-full px-4 py-2.5 bg-surface-pure border border-border-base rounded-xl text-base focus:outline-none focus:border-primary">
                                        @foreach(['TWD','USD','JPY','EUR','GBP','AUD','CAD','CNY','HKD','SGD'] as $code)
                                            <option value="{{ $code }}" @selected(old('currency', $family->currency) === $code)>{{ $code }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-on-surface-variant mb-1">附件容量上限 (MB) <span class="text-danger">*</span></label>
                                    <input type="number" name="storage_quota_mb" value="{{ old('storage_quota_mb', $family->storage_quota_mb) }}" required min="100" max="10240"
                                           class="w-full px-4 py-2.5 bg-surface-pure border border-border-base rounded-xl text-base focus:outline-none focus:border-primary">
                                    <p class="text-xs text-on-surface-variant mt-1">{{ __('auto.0575') }}</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-on-surface-variant mb-1">Discord Webhook URL(選填)</label>
                                <input type="url" name="discord_webhook_url" value="{{ old('discord_webhook_url', $family->discord_webhook_url) }}"
                                       placeholder="https://discord.com/api/webhooks/..."
                                       pattern="^https://discord\.com/api/webhooks/"
                                       maxlength="255"
                                       class="w-full px-4 py-2.5 bg-surface-pure border border-border-base rounded-xl text-base focus:outline-none focus:border-primary font-mono text-sm">
                                <p class="text-xs text-on-surface-variant mt-1">Discord 伺服器設定 → 頻道設定 → Webhook → 複製 URL 貼上</p>
                            </div>

                            <div class="flex items-center justify-end gap-2 pt-2">
                                <button type="submit" class="px-6 py-2.5 bg-primary text-on-primary font-bold rounded-xl shadow-md hover:bg-primary/90 transition-all">{{ __('action.save_changes') }}</button>
                            </div>
                        </form>
                    @else
                        <div class="bg-warning/10 border border-warning/20 rounded-xl p-4 text-warning text-sm flex items-start gap-2">
                            <span class="material-symbols-outlined mt-0.5">lock</span>
                            <div>只有家長(role=parent)能編輯家庭設定。您的角色是 <span class="font-mono px-2 py-0.5 bg-warning/15 rounded">{{ $currentUserRole }}</span></div>
                        </div>
                    @endif
                </div>

                {{-- 家庭成員列表 --}}
                <div class="bg-surface-pure rounded-2xl p-6 shadow-sm border border-border-base">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-xl">group</span>
                            <h2 class="text-lg font-bold text-on-surface">家庭成員 ({{ $members->count() }})</h2>
                        </div>
                        <a href="{{ route('members.index') }}" class="text-sm text-primary font-bold hover:underline">{{ __('auto.0334') }}</a>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                        @foreach($members as $m)
                            <div class="flex items-center gap-2 bg-surface-container-low rounded-xl p-3">
                                <div class="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold flex-shrink-0">
                                    @if($m->avatar_url)
                                        <img src="{{ asset($m->avatar_url) }}" class="w-10 h-10 rounded-full object-cover" alt="{{ $m->name }}">
                                    @else
                                        {{ mb_substr($m->name, 0, 1) }}
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-bold text-on-surface truncate">{{ $m->name }}</div>
                                    <div class="text-xs text-on-surface-variant">{{ $m->pivot->role }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>