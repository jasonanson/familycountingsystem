<x-app-layout>
    <x-slot name="title">{{ __('auto.0336') }}</x-slot>

    <div x-data="savingGoalsPage()" class="space-y-8 max-w-6xl mx-auto">

        @if($isRestrictedAdmin ?? false)
            <div class="bg-warning/15 border-2 border-warning/30 rounded-2xl p-4 flex items-start gap-3 text-on-surface shadow-sm">
                <span class="material-symbols-outlined text-warning text-2xl shrink-0 mt-0.5">lock_person</span>
                <div>
                    <h4 class="font-bold text-sm text-warning flex items-center gap-1.5">
                        <span>{{ __('auto.0583') }}</span>
                        <span class="px-2 py-0.2 bg-warning/20 text-warning text-[11px] font-bold rounded-md">{{ __('auto.0255') }}</span>
                    </h4>
                    <p class="text-xs text-on-surface-variant mt-0.5">
                        您目前正在檢視小孩儲蓄願望目標畫面。依系統權限規範，系統管理員為大人身分，此面板僅供畫面檢視與進度查看，無法新增、修改、刪除或存入儲蓄目標。
                    </p>
                </div>
            </div>
        @endif

        <!-- Top Header & Stats Summary Banner (Google Stitch 5.2) -->
        <div class="bg-surface-pure border border-border-base rounded-2xl p-6 shadow-[0_4px_12px_rgba(28,25,23,0.03)] space-y-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-category-sky/15 text-category-sky flex items-center justify-center flex-shrink-0 shadow-sm">
                        <span class="material-symbols-outlined text-[32px]">stars</span>
                    </div>
                    <div>
                        <h1 class="text-2xl font-black text-text-primary tracking-tight flex items-center gap-2">
                            <span>{{ __('auto.0336') }}</span>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary/10 text-primary border border-primary/20">
                                兒童專屬
                            </span>
                            @if($isRestrictedAdmin ?? false)
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-warning/15 text-warning border border-warning/30">{{ __('auto.0237') }}</span>
                            @endif
                        </h1>
                        <p class="text-sm text-on-surface-variant mt-1">
                            設定夢想目標、定期存入零用錢，讓每一次的儲蓄都看得到成長與成果！
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    @if($isRestrictedAdmin ?? false)
                        <span class="px-3.5 py-2 bg-surface-container-high border border-border-base rounded-xl text-xs font-bold text-on-surface-variant flex items-center gap-1.5 select-none">
                            <span class="material-symbols-outlined text-base">lock</span>
                            <span>{{ __('auto.0568') }}</span>
                        </span>
                    @else
                        <button type="button"
                                @click="openCreateGoalModal()"
                                class="px-5 py-2.5 bg-primary text-white font-bold rounded-xl hover:bg-primary/90 transition-all active:scale-95 shadow-sm flex items-center gap-2 text-sm cursor-pointer">
                            <span class="material-symbols-outlined text-lg">add</span>
                            <span>+ 新增儲蓄目標</span>
                        </button>
                    @endif
                </div>
            </div>

            <!-- Overall Stats Bar -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-4 border-t border-border-base/70">
                <div class="p-3 bg-surface-container-low rounded-xl border border-border-base/50">
                    <span class="text-xs text-on-surface-variant font-medium block">{{ __('auto.0684') }}</span>
                    <span class="font-mono text-xl font-bold text-text-primary mt-1 block">
                        {{ $stats->in_progress_goals ?? 0 }} <span class="text-xs font-normal text-on-surface-variant">個</span>
                    </span>
                </div>

                <div class="p-3 bg-surface-container-low rounded-xl border border-border-base/50">
                    <span class="text-xs text-on-surface-variant font-medium block">{{ __('auto.0314') }}</span>
                    <span class="font-mono text-xl font-bold text-success mt-1 block">
                        {{ $stats->completed_goals ?? 0 }} <span class="text-xs font-normal text-on-surface-variant">{{ __('auto.0144') }}</span>
                    </span>
                </div>

                <div class="p-3 bg-surface-container-low rounded-xl border border-border-base/50">
                    <span class="text-xs text-on-surface-variant font-medium block">{{ __('auto.0596') }}</span>
                    <span class="font-mono text-xl font-black text-primary mt-1 block">
                        NT$ {{ number_format($stats->total_current_amount ?? 0) }}
                    </span>
                </div>

                <div class="p-3 bg-surface-container-low rounded-xl border border-border-base/50">
                    <span class="text-xs text-on-surface-variant font-medium block">{{ __('auto.0379') }}</span>
                    <div class="flex items-center gap-2 mt-1">
                        <div class="flex-1 h-2 bg-surface-variant rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-primary to-category-mint rounded-full" style="width: {{ $stats->overall_percentage ?? 0 }}%;"></div>
                        </div>
                        <span class="font-mono text-sm font-bold text-primary">{{ $stats->overall_percentage ?? 0 }}%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Goals Grid (Stitch 5.2 Bento-ish Cards) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($goals as $goal)
                @php
                    $target = (float) $goal->target_amount;
                    $current = (float) $goal->current_amount;
                    $percent = $target > 0 ? min(100, round(($current / $target) * 100)) : 0;
                    $isCompleted = ($current >= $target);
                    $color = $goal->color ?: 'category-sky';
                    $icon = \App\Support\IconHelper::name($goal->icon ?? null, 'sports_esports');

                    // 截止日倒數計算
                    $daysRemaining = null;
                    if ($goal->deadline) {
                        $daysRemaining = \Carbon\Carbon::now()->startOfDay()->diffInDays($goal->deadline->startOfDay(), false);
                    }
                @endphp
                <div class="bg-surface-pure border border-border-base rounded-2xl p-6 shadow-[0_4px_12px_rgba(28,25,23,0.03)] hover:shadow-md transition-all flex flex-col justify-between relative overflow-hidden group">
                    <!-- Top Accent Color Bar -->
                    <div class="absolute top-0 left-0 right-0 h-1.5 bg-{{ $color }}"></div>

                    <div>
                        <!-- Header: Icon, Name & User -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3.5">
                                <div class="w-13 h-13 rounded-2xl bg-{{ $color }}/15 text-{{ $color }} flex items-center justify-center flex-shrink-0 shadow-sm group-hover:scale-105 transition-transform">
                                    <span class="material-symbols-outlined text-3xl">{{ $icon }}</span>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="font-bold text-lg text-text-primary truncate" title="{{ $goal->name }}">
                                        {{ $goal->name }}
                                    </h3>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-xs font-semibold text-on-surface-variant">
                                            歸屬: {{ $goal->user->name ?? '家庭' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            @if($isCompleted)
                                <span class="px-2.5 py-1 bg-success/10 text-success border border-success/20 rounded-full text-xs font-black flex items-center gap-1 shadow-sm">
                                    <span>🎉 已達成！</span>
                                </span>
                            @endif
                        </div>

                        <!-- Progress Bar & Amounts -->
                        <div class="bg-surface-container-low rounded-xl p-4 border border-border-base/60 mb-4 space-y-2">
                            <div class="flex justify-between items-baseline">
                                <span class="font-mono text-xl font-black text-{{ $color }}">
                                    NT$ {{ number_format($current) }}
                                </span>
                                <span class="text-xs font-mono text-on-surface-variant font-semibold">
                                    / NT$ {{ number_format($target) }}
                                </span>
                            </div>

                            <!-- Progress Track -->
                            <div class="h-3 w-full bg-surface-variant rounded-full overflow-hidden p-0.5">
                                <div class="h-full bg-{{ $color }} rounded-full transition-all duration-700 shadow-sm" 
                                     style="width: {{ $percent }}%;"></div>
                            </div>

                            <div class="flex justify-between items-center text-[11px] font-semibold text-on-surface-variant font-mono">
                                <span>{{ __('auto.0683') }}</span>
                                <span class="font-bold text-{{ $color }}">{{ $percent }}%</span>
                            </div>
                        </div>

                        <!-- Deadline Countdown Badge -->
                        <div class="flex items-center justify-between text-xs text-on-surface-variant">
                            @if($goal->deadline)
                                <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg {{ $daysRemaining < 0 ? 'bg-danger/10 text-danger border border-danger/20' : ($daysRemaining <= 7 ? 'bg-warning/10 text-warning border border-warning/20' : 'bg-surface-container-low border border-border-base') }}">
                                    <span class="material-symbols-outlined text-sm">calendar_today</span>
                                    @if($daysRemaining < 0)
                                        <span>已逾期 {{ abs($daysRemaining) }} 天 ({{ $goal->deadline->format('Y-m-d') }})</span>
                                    @elseif($daysRemaining == 0)
                                        <span class="font-bold">{{ __('auto.0107') }}</span>
                                    @else
                                        <span>預計 {{ $goal->deadline->format('Y-m-d') }} (剩 {{ $daysRemaining }} 天)</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-[11px] text-on-surface-variant flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">all_inclusive</span>
                                    <span>{{ __('auto.0478') }}</span>
                                </span>
                            @endif

                            @if(!$isCompleted)
                                <span class="text-xs font-bold text-category-orange font-mono">
                                    差 NT$ {{ number_format(max(0, $target - $current)) }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Card Actions -->
                    @if($isRestrictedAdmin ?? false)
                        <div class="mt-6 pt-4 border-t border-border-base flex items-center justify-between text-xs text-on-surface-variant font-medium select-none">
                            <span class="flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">visibility</span>
                                <span>{{ __('auto.0154') }}</span>
                            </span>
                            <span class="px-2 py-0.5 bg-surface-container-high rounded text-[10px] font-bold">🔒 管理員唯讀</span>
                        </div>
                    @else
                        @if(auth()->user()->canEditCurrentFamily())
                        <div class="mt-6 pt-4 border-t border-border-base flex items-center gap-2">
                            <!-- Deposit Button -->
                            <button type="button" 
                                    @click="openDepositModal(@js($goal))" 
                                    class="flex-1 py-2 px-3 bg-{{ $color }}/15 hover:bg-{{ $color }} text-{{ $color }} hover:text-white font-bold text-xs rounded-xl border border-{{ $color }}/30 transition-all flex items-center justify-center gap-1 cursor-pointer shadow-sm">
                                <span class="material-symbols-outlined text-base">add_circle</span>
                                <span>{{ __('auto.0265') }}</span>
                            </button>

                            <!-- Edit Button -->
                            <button type="button" 
                                    @click="openEditGoalModal(@js($goal))" 
                                    class="p-2 bg-surface-container-low hover:bg-surface-container text-on-surface-variant hover:text-primary rounded-xl border border-border-base transition-colors" 
                                    title="{{ __('auto.0593') }}">
                                <span class="material-symbols-outlined text-base">edit</span>
                            </button>

                            <!-- Delete Form -->
                            <form action="{{ route('saving-goals.destroy', $goal->id) }}" method="POST" onsubmit="return confirm('確定要刪除願望目標「{{ $goal->name }}」嗎？');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="p-2 bg-danger/10 hover:bg-danger text-danger hover:text-white rounded-xl border border-danger/20 transition-colors" 
                                        title="{{ __('auto.0201') }}">
                                    <span class="material-symbols-outlined text-base">delete</span>
                                </button>
                            </form>
                        </div>
                        @endif
                    @endif
                    </div>
                </div>
            @empty
                <!-- Empty State -->
                <div class="col-span-full bg-surface-pure border border-border-base rounded-2xl p-12 text-center shadow-sm">
                    <div class="w-16 h-16 rounded-full bg-category-sky/15 text-category-sky flex items-center justify-center mx-auto mb-4">
                        <span class="material-symbols-outlined text-3xl">sports_esports</span>
                    </div>
                    <h3 class="font-bold text-lg text-text-primary mb-1">{{ __('auto.0302') }}</h3>
                    <p class="text-sm text-on-surface-variant max-w-md mx-auto mb-6">
                        鼓勵孩子設定想要購買的玩具、遊戲、書籍或腳踏車，培養目標導向的儲蓄習慣！
                    </p>
                    @if(!($isRestrictedAdmin ?? false))
                        <button type="button" 
                                @click="openCreateGoalModal()" 
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-white font-bold rounded-xl hover:bg-primary/90 transition-all text-sm">
                            <span class="material-symbols-outlined text-base">add</span>
                            <span>{{ __('auto.0558') }}</span>
                        </button>
                    @endif
                </div>
            @endforelse

            <!-- Add Goal Ghost Card (If has goals and is child) -->
            @if($goals->isNotEmpty() && !($isRestrictedAdmin ?? false))
                <div @click="openCreateGoalModal()" 
                     class="bg-surface-pure rounded-2xl p-6 border-2 border-dashed border-border-base flex flex-col items-center justify-center gap-3 hover:border-primary hover:bg-surface-container-low transition-all cursor-pointer group min-h-[220px]">
                    <div class="w-12 h-12 rounded-full bg-primary/10 text-primary flex items-center justify-center group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-2xl">add</span>
                    </div>
                    <span class="font-bold text-sm text-primary">{{ __('auto.0386') }}</span>
                </div>
            @endif
        </div>

        <!-- Alpine.js Modal: 新增 / 編輯目標 (Google Stitch 5.2 Form) -->
        <div x-show="isGoalModalOpen" 
             x-cloak 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-on-surface/40 backdrop-blur-sm"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            
            <div @click.outside="isGoalModalOpen = false" 
                 class="bg-surface-pure border border-border-base rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-2">
                
                <div class="sticky top-0 bg-surface-bright/95 backdrop-blur-md px-6 py-4 border-b border-border-base flex items-center justify-between z-10">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold">
                            <span class="material-symbols-outlined text-xl" x-text="goalForm.icon || 'sports_esports'"></span>
                        </div>
                        <h2 class="font-bold text-lg text-text-primary" x-text="goalForm.is_editing ? '編輯儲蓄目標' : '新增儲蓄目標'"></h2>
                    </div>
                    <button type="button" @click="isGoalModalOpen = false" class="w-8 h-8 rounded-lg hover:bg-surface-container flex items-center justify-center text-on-surface-variant transition-colors">
                        <span class="material-symbols-outlined text-xl">close</span>
                    </button>
                </div>

                <form :action="goalForm.action_url" method="POST" class="p-6 space-y-5">
                    @csrf
                    <template x-if="goalForm.is_editing">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <!-- 1. 目標名稱 -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">{{ __('auto.0737') }}</label>
                        <input type="text" 
                               name="name" 
                               x-model="goalForm.name" 
                               required 
                               placeholder="{{ __('auto.0121') }}" 
                               class="w-full bg-surface-pure border border-border-base rounded-xl px-3.5 py-2.5 text-sm font-bold text-text-primary focus:outline-none focus:border-primary shadow-sm">
                    </div>

                    <!-- 2. 歸屬成員 -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">{{ __('auto.0450') }}</label>
                        @if($isChildUser ?? false)
                            {{-- 小孩只能為自己建立儲蓄願望,直接綁定 user_id --}}
                            <div class="w-full bg-surface-container border border-border-base rounded-xl px-3.5 py-2.5 text-sm font-bold text-on-surface-variant flex items-center gap-2">
                                <span class="material-symbols-outlined text-base">self_improvement</span>
                                <span>為 <strong class="text-text-primary">{{ auth()->user()->name }}</strong> 建立(自己)</span>
                            </div>
                            <input type="hidden" name="user_id" value="{{ $currentUserId }}">
                        @else
                            <select name="user_id" x-model="goalForm.user_id" required class="w-full bg-surface-pure border border-border-base rounded-xl px-3.5 py-2.5 text-sm font-semibold text-text-primary focus:outline-none focus:border-primary shadow-sm">
                                @foreach($members as $m)
                                    <option value="{{ $m->id }}">{{ $m->name }} (@ {{ $m->account }})</option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    <!-- 3. 目標金額 & 初始金額 -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">{{ __('goal_page.target_amount') }}</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-xs font-bold text-on-surface-variant font-mono">NT$</span>
                                <input type="number" 
                                       name="target_amount" 
                                       x-model="goalForm.target_amount" 
                                       required 
                                       min="1" 
                                       step="1" 
                                       placeholder="5000" 
                                       class="w-full pl-10 pr-3 py-2 bg-surface-pure border border-border-base rounded-xl font-bold text-sm text-text-primary focus:outline-none focus:border-primary shadow-sm font-mono">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">{{ __('auto.0533') }}</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-xs font-bold text-on-surface-variant font-mono">NT$</span>
                                <input type="number" 
                                       name="current_amount" 
                                       x-model="goalForm.current_amount" 
                                       min="0" 
                                       step="1" 
                                       placeholder="0" 
                                       class="w-full pl-10 pr-3 py-2 bg-surface-pure border border-border-base rounded-xl font-bold text-sm text-text-primary focus:outline-none focus:border-primary shadow-sm font-mono">
                            </div>
                        </div>
                    </div>

                    <!-- 4. 預計達成截止日 -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">{{ __('auto.0734') }}</label>
                        <input type="date" 
                               name="deadline" 
                               x-model="goalForm.deadline" 
                               class="w-full bg-surface-pure border border-border-base rounded-xl px-3.5 py-2 text-xs font-semibold text-text-primary focus:outline-none focus:border-primary shadow-sm">
                    </div>

                    <!-- 5. Material Symbol Icon 選擇器 -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">{{ __('auto.0690') }}</label>
                        <input type="hidden" name="icon" :value="goalForm.icon">
                        <div class="grid grid-cols-6 gap-2">
                            <template x-for="ic in availableIcons" :key="ic">
                                <button type="button" 
                                        @click="goalForm.icon = ic" 
                                        class="h-10 rounded-xl border flex items-center justify-center transition-all"
                                        :class="goalForm.icon === ic ? 'border-primary bg-primary/10 text-primary shadow-sm font-bold scale-105' : 'border-border-base text-on-surface-variant hover:bg-surface-container'">
                                    <span class="material-symbols-outlined text-xl" x-text="ic"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- 6. 主題色彩 -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">{{ __('auto.0049') }}</label>
                        <input type="hidden" name="color" :value="goalForm.color">
                        <div class="grid grid-cols-4 sm:grid-cols-8 gap-2">
                            <template x-for="c in stitchColors" :key="c.name">
                                <button type="button" 
                                        @click="goalForm.color = c.name" 
                                        class="h-10 rounded-xl flex items-center justify-center border-2 transition-transform cursor-pointer relative"
                                        :style="'background-color: ' + c.hex + ';'"
                                        :class="goalForm.color === c.name ? 'border-text-primary scale-110 shadow-md ring-2 ring-primary/40' : 'border-transparent opacity-80 hover:opacity-100'"
                                        :title="c.label">
                                    <template x-if="goalForm.color === c.name">
                                        <span class="material-symbols-outlined text-white text-base drop-shadow-md">check</span>
                                    </template>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-4 border-t border-border-base flex gap-3">
                        <button type="button" @click="isGoalModalOpen = false" class="flex-1 py-2.5 px-4 rounded-xl border border-border-base font-bold text-sm text-on-surface-variant hover:bg-surface-container transition-colors">{{ __('common.cancel') }}</button>
                        <button type="submit" class="flex-1 py-2.5 px-4 rounded-xl bg-primary font-bold text-sm text-white hover:bg-primary/90 transition-all shadow-sm active:scale-95">
                            儲存目標
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Alpine.js Modal: 存入零用錢 Modal -->
        <div x-show="isDepositModalOpen" 
             x-cloak 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-on-surface/40 backdrop-blur-sm"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            
            <div @click.outside="isDepositModalOpen = false" 
                 class="bg-surface-pure border border-border-base rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-2">
                
                <div class="sticky top-0 bg-surface-bright/95 backdrop-blur-md px-6 py-4 border-b border-border-base flex items-center justify-between z-10">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-category-mint/15 text-category-mint flex items-center justify-center font-bold">
                            <span class="material-symbols-outlined text-xl">savings</span>
                        </div>
                        <h2 class="font-bold text-lg text-text-primary">{{ __('auto.0265') }}</h2>
                    </div>
                    <button type="button" @click="isDepositModalOpen = false" class="w-8 h-8 rounded-lg hover:bg-surface-container flex items-center justify-center text-on-surface-variant transition-colors">
                        <span class="material-symbols-outlined text-xl">close</span>
                    </button>
                </div>

                <form :action="depositForm.action_url" method="POST" class="p-6 space-y-5">
                    @csrf
                    <!-- Goal Target Info Summary -->
                    <div class="p-4 bg-surface-container-low rounded-xl border border-border-base flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-category-sky/15 text-category-sky flex items-center justify-center font-bold">
                            <span class="material-symbols-outlined text-xl" x-text="depositForm.goal_icon || 'stars'"></span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="text-sm font-bold text-text-primary truncate" x-text="depositForm.goal_name"></h4>
                            <p class="text-xs text-on-surface-variant mt-0.5">
                                目前進度: NT$ <span x-text="depositForm.current_amount" class="font-mono font-bold"></span> / NT$ <span x-text="depositForm.target_amount" class="font-mono font-bold"></span>
                            </p>
                        </div>
                    </div>

                    <!-- 1. 存入金額 -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">{{ __('auto.0264') }}</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm font-bold text-on-surface-variant font-mono">NT$</span>
                            <input type="number" 
                                   name="amount" 
                                   x-model="depositForm.amount" 
                                   required 
                                   min="1" 
                                   step="1" 
                                   placeholder="{{ __('auto.0660') }}" 
                                   class="w-full pl-13 pr-4 py-2.5 bg-surface-pure border border-border-base rounded-xl font-bold text-lg text-primary focus:outline-none focus:border-primary shadow-sm font-mono">
                        </div>
                        
                        <!-- Quick Amount Tags -->
                        <div class="flex gap-2 mt-2">
                            <button type="button" @click="depositForm.amount = 100" class="px-2.5 py-1 bg-surface-container-low hover:bg-primary/10 hover:text-primary rounded-lg text-xs font-bold border border-border-base transition-colors">+100</button>
                            <button type="button" @click="depositForm.amount = 200" class="px-2.5 py-1 bg-surface-container-low hover:bg-primary/10 hover:text-primary rounded-lg text-xs font-bold border border-border-base transition-colors">+200</button>
                            <button type="button" @click="depositForm.amount = 500" class="px-2.5 py-1 bg-surface-container-low hover:bg-primary/10 hover:text-primary rounded-lg text-xs font-bold border border-border-base transition-colors">+500</button>
                            <template x-if="depositForm.gap > 0">
                                <button type="button" @click="depositForm.amount = depositForm.gap" class="px-2.5 py-1 bg-category-mint/15 text-category-mint hover:bg-category-mint hover:text-white rounded-lg text-xs font-bold border border-category-mint/30 transition-colors">存入全部差額 (NT$ <span x-text="depositForm.gap"></span>)</button>
                            </template>
                        </div>
                    </div>

                    <!-- 2. 備註 -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">{{ __('auto.0164') }}</label>
                        <input type="text" 
                               name="note" 
                               x-model="depositForm.note" 
                               placeholder="{{ __('auto.0719') }}" 
                               class="w-full bg-surface-pure border border-border-base rounded-xl px-3.5 py-2 text-xs font-semibold text-text-primary focus:outline-none focus:border-primary shadow-sm">
                    </div>

                    <!-- Actions -->
                    <div class="pt-3 border-t border-border-base flex gap-3">
                        <button type="button" @click="isDepositModalOpen = false" class="flex-1 py-2.5 px-4 rounded-xl border border-border-base font-bold text-sm text-on-surface-variant hover:bg-surface-container transition-colors">{{ __('common.cancel') }}</button>
                        <button type="submit" class="flex-1 py-2.5 px-4 rounded-xl bg-category-mint font-bold text-sm text-white hover:bg-category-mint/90 transition-all shadow-sm active:scale-95">
                            確認存入
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function savingGoalsPage() {
            return {
                isGoalModalOpen: false,
                isDepositModalOpen: false,
                membersList: @js($members ?? []),
                availableIcons: [
                    'sports_esports', 'videogame_asset', 'pedal_bike', 'school',
                    'flight', 'menu_book', 'savings', 'redeem',
                    'phone_iphone', 'sports_soccer', 'palette', 'music_note'
                ],
                stitchColors: [
                    { name: 'category-mint', hex: '#34D399', label: '薄荷綠 (Mint)' },
                    { name: 'category-sky', hex: '#60A5FA', label: '晴空藍 (Sky)' },
                    { name: 'category-amber', hex: '#FBBF24', label: '琥珀金 (Amber)' },
                    { name: 'category-orange', hex: '#F97316', label: '活力橘 (Orange)' },
                    { name: 'category-rose', hex: '#FB7185', label: '玫瑰紅 (Rose)' },
                    { name: 'category-pink', hex: '#F472B6', label: '櫻花粉 (Pink)' },
                    { name: 'category-lavender', hex: '#A78BFA', label: '薰衣紫 (Lavender)' },
                    { name: 'primary', hex: '#006b5f', label: '經典青 (Teal)' }
                ],
                goalForm: {
                    is_editing: false,
                    action_url: '{{ route("saving-goals.store") }}',
                    name: '',
                    user_id: '',
                    target_amount: '',
                    current_amount: '0',
                    deadline: '',
                    icon: 'sports_esports',
                    color: 'category-sky',
                },
                depositForm: {
                    action_url: '',
                    goal_name: '',
                    goal_icon: '',
                    target_amount: 0,
                    current_amount: 0,
                    gap: 0,
                    amount: '100',
                    note: '零用錢儲蓄',
                },
                openCreateGoalModal() {
                    this.goalForm = {
                        is_editing: false,
                        action_url: '{{ route("saving-goals.store") }}',
                        name: '',
                        user_id: this.membersList.length > 0 ? this.membersList[0].id : '',
                        target_amount: '3000',
                        current_amount: '0',
                        deadline: '',
                        icon: 'sports_esports',
                        color: 'category-sky',
                    };
                    this.isGoalModalOpen = true;
                },
                openEditGoalModal(goal) {
                    this.goalForm = {
                        is_editing: true,
                        action_url: '/saving-goals/' + goal.id,
                        name: goal.name || '',
                        user_id: goal.user_id || '',
                        target_amount: goal.target_amount || '',
                        current_amount: goal.current_amount || '0',
                        deadline: goal.deadline ? goal.deadline.substring(0, 10) : '',
                        icon: goal.icon || 'sports_esports',
                        color: goal.color || 'category-sky',
                    };
                    this.isGoalModalOpen = true;
                },
                openDepositModal(goal) {
                    const target = Number(goal.target_amount) || 0;
                    const current = Number(goal.current_amount) || 0;
                    const gap = Math.max(0, target - current);

                    this.depositForm = {
                        action_url: '/saving-goals/' + goal.id + '/deposit',
                        goal_name: goal.name || '',
                        goal_icon: goal.icon || 'stars',
                        target_amount: target.toLocaleString(),
                        current_amount: current.toLocaleString(),
                        gap: gap,
                        amount: Math.min(200, gap > 0 ? gap : 100),
                        note: '零用錢儲蓄',
                    };
                    this.isDepositModalOpen = true;
                }
            };
        }
    </script>
</x-app-layout>
