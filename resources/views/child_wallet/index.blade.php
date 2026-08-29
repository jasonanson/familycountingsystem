<x-app-layout>
    <x-slot name="title">{{ __('child_page.wallet') }}</x-slot>

    <div x-data="childWalletPage()" class="space-y-8 max-w-4xl mx-auto">

        @if($hasNoFamily ?? false)
            <div class="bg-gradient-to-r from-primary/15 via-category-mint/20 to-primary/10 border-2 border-primary/30 rounded-3xl p-6 sm:p-8 text-center space-y-4 shadow-sm">
                <div class="w-16 h-16 rounded-full bg-primary text-white flex items-center justify-center mx-auto shadow-md animate-bounce">
                    <span class="material-symbols-outlined text-4xl">diversity_3</span>
                </div>
                <div class="space-y-1.5">
                    <h2 class="text-xl sm:text-2xl font-black text-primary">{{ __('auto.0439') }}</h2>
                    <p class="text-sm text-on-surface-variant max-w-md mx-auto">
                        你目前尚未加入任何家庭。請向爸爸媽媽索取 6 位數家庭邀請碼，點擊下方按鈕即可直接加入！
                    </p>
                </div>
                <div>
                    <button type="button" 
                            onclick="document.getElementById('globalJoinFamilyModal').classList.remove('hidden'); document.getElementById('global_invite_code_input')?.focus();" 
                            class="px-6 py-3 bg-primary hover:bg-primary/90 text-white font-bold rounded-2xl text-base shadow-lg hover:scale-105 active:scale-95 transition-all inline-flex items-center gap-2 cursor-pointer">
                        <span class="material-symbols-outlined text-xl">key</span>
                        <span>{{ __('auth.join_family_btn') }}</span>
                    </button>
                </div>
            </div>
        @endif

        @if($isRestrictedAdmin ?? false)
            <div class="bg-warning/15 border-2 border-warning/30 rounded-2xl p-4 flex items-start gap-3 text-on-surface shadow-sm">
                <span class="material-symbols-outlined text-warning text-2xl shrink-0 mt-0.5">lock_person</span>
                <div>
                    <h4 class="font-bold text-sm text-warning flex items-center gap-1.5">
                        <span>{{ __('auto.0583') }}</span>
                        <span class="px-2 py-0.2 bg-warning/20 text-warning text-[11px] font-bold rounded-md">{{ __('auto.0255') }}</span>
                    </h4>
                    <p class="text-xs text-on-surface-variant mt-0.5">
                        您目前正在檢視小孩專屬零用錢錢包畫面。依系統權限規範，系統管理員為大人身分，此面板僅提供介面與數據檢視，無法直接以此面板進行小孩記帳操作；大人記帳請使用大人專屬面板。家長功能（如發放零用錢與限額管理）可正常使用。
                    </p>
                </div>
            </div>
        @endif

        <!-- Family / Child Switcher Header (If Multiple Children or Parent View) -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-surface-pure border border-border-base rounded-2xl p-5 shadow-[0_4px_12px_rgba(28,25,23,0.03)]">
            <div class="flex items-center gap-3.5">
                <div class="relative">
                    @if(filled($selectedChild?->avatar_url))
                        <img class="w-13 h-13 rounded-full object-cover border-2 border-primary-container shadow-sm" 
                             src="{{ asset($selectedChild->avatar_url) }}" 
                             alt="{{ $selectedChild->name ?? '兒童' }}">
                    @else
                        <div class="w-13 h-13 rounded-full bg-gradient-to-br from-primary-container to-category-mint text-white flex items-center justify-center text-xl font-bold shadow-sm">
                            {{ mb_substr($selectedChild?->name ?? '小', 0, 1) }}
                        </div>
                    @endif
                    <span class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-surface-pure bg-success"></span>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-text-primary flex items-center gap-2">
                        <span>{{ $selectedChild?->name ?? '小明' }} 的零用錢錢包</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-category-mint/15 text-primary border border-category-mint/30">
                            兒童專屬
                        </span>
                        @if($isRestrictedAdmin ?? false)
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-warning/15 text-warning border border-warning/30">{{ __('auto.0237') }}</span>
                        @endif
                    </h1>
                    <p class="text-xs text-on-surface-variant mt-0.5">
                        @if($isParent)
                            @if($hasNoChildren ?? false)
                                家長/管理員檢視視角 • 目前家庭尚無兒童成員（畫面預覽中）
                            @else
                                家長監護視角 • 可發放零用錢與檢視收支
                            @endif
                        @else
                            兒童自主理財 • 學習儲蓄與負責任消費
                        @endif
                    </p>
                </div>
            </div>

            <!-- Parent Action or Child Switching Dropdown -->
            <div class="flex items-center gap-2.5 flex-wrap">
                @if($isParent && $children->count() > 1)
                    <form action="{{ route('child-wallet.index') }}" method="GET" class="inline">
                        <select name="child_id" onchange="this.form.submit()" class="bg-surface-container-low border border-border-base rounded-xl px-3 py-2 text-xs font-bold text-primary focus:outline-none focus:border-primary cursor-pointer shadow-sm">
                            @foreach($children as $c)
                                <option value="{{ $c->id }}" {{ $selectedChild?->id == $c->id ? 'selected' : '' }}>
                                    👦 {{ $c->name }} 的錢包
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif

                @if($isParent)
                    <button type="button" 
                            @click="openAllowanceModal(@js($selectedChild))" 
                            class="px-4 py-2 bg-gradient-to-r from-primary to-category-mint text-white font-bold rounded-xl hover:opacity-95 transition-all active:scale-95 shadow-sm flex items-center gap-1.5 text-xs cursor-pointer">
                        <span class="material-symbols-outlined text-base">redeem</span>
                        <span>{{ __('auto.0515') }}</span>
                    </button>
                    <a href="{{ route('child-limits.index') }}" class="px-3 py-2 bg-surface-container-low border border-border-base text-on-surface-variant hover:text-primary font-bold rounded-xl transition-colors text-xs flex items-center gap-1">
                        <span class="material-symbols-outlined text-base">tune</span>
                        <span>{{ __('auto.0715') }}</span>
                    </a>
                @endif
            </div>
        </div>

        <!-- Daily Warning / Friendly Encouragement Banner (Google Stitch 5.1) -->
        @php
            $dailyMax = $limit?->daily_max;
            $dailyRemaining = $dailyMax ? max(0, $dailyMax - $todayExpense) : null;
        @endphp
        @if($dailyMax && $dailyRemaining !== null && $dailyRemaining <= ($dailyMax * 0.2))
            <div class="p-4 bg-category-orange/10 border border-category-orange/20 rounded-2xl flex items-center gap-3 animate-pulse">
                <span class="material-symbols-outlined text-category-orange text-3xl">warning</span>
                <p class="text-sm font-bold text-text-primary">
                    你今天已經花了 <span class="text-danger font-black">NT$ {{ number_format($todayExpense) }}</span>，剩下 <span class="text-category-orange font-black">NT$ {{ number_format($dailyRemaining) }}</span> 的額度囉！
                </p>
            </div>
        @else
            <div class="p-4 bg-category-mint/10 border border-category-mint/20 rounded-2xl flex items-center gap-3">
                <span class="material-symbols-outlined text-category-mint text-3xl">sentiment_very_satisfied</span>
                <p class="text-sm font-semibold text-text-primary">
                    今天已經花費 <strong class="text-primary font-black">NT$ {{ number_format($todayExpense) }}</strong>。零用錢狀況良好，記得存錢買願望清單喔！✨
                </p>
            </div>
        @endif

        <!-- Hero Card: Total Balance (Google Stitch 5.1 Hero Gradient) -->
        <section class="relative overflow-hidden rounded-[28px] bg-gradient-to-br from-primary-container via-primary to-category-mint p-6 md:p-8 text-white shadow-[0_12px_32px_rgba(20,184,166,0.3)]">
            <!-- Decorative Blobs & Watermark -->
            <div class="absolute -right-10 -bottom-10 w-56 h-56 bg-white/15 rounded-full blur-2xl pointer-events-none"></div>
            <div class="absolute top-1/2 -translate-y-1/2 right-6 opacity-20 pointer-events-none">
                <span class="material-symbols-outlined text-[150px]">monetization_on</span>
            </div>

            <div class="relative z-10 flex flex-col">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-bold text-white/90 tracking-wide uppercase">{{ __('auto.0720') }}</span>
                    <span class="px-3 py-1 bg-white/20 backdrop-blur-md rounded-full text-xs font-bold border border-white/30 flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-category-mint animate-ping"></span>
                        即時更新
                    </span>
                </div>

                <div class="my-4 flex items-baseline gap-2">
                    <span class="text-4xl md:text-5xl font-black tracking-tight drop-shadow-sm font-mono">
                        NT$ {{ number_format($balance) }}
                    </span>
                </div>

                <!-- Monthly Income & Expense Pill Comparison -->
                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <div class="inline-flex items-center bg-white/20 backdrop-blur-md rounded-2xl px-4 py-2 border border-white/30 text-xs font-bold shadow-sm">
                        <span class="material-symbols-outlined text-base mr-1.5 text-category-mint">arrow_circle_down</span>
                        <span>本月存入 NT$ {{ number_format($monthlyIncome) }}</span>
                    </div>

                    <div class="inline-flex items-center bg-white/20 backdrop-blur-md rounded-2xl px-4 py-2 border border-white/30 text-xs font-bold shadow-sm">
                        <span class="material-symbols-outlined text-base mr-1.5 text-category-orange">arrow_circle_up</span>
                        <span>本月支出 NT$ {{ number_format($monthlyExpense) }}</span>
                    </div>

                    @php
                        $monthNet = $monthlyIncome - $monthlyExpense;
                    @endphp
                    <div class="inline-flex items-center bg-black/20 backdrop-blur-md rounded-2xl px-4 py-2 border border-white/20 text-xs font-bold shadow-sm">
                        <span class="material-symbols-outlined text-base mr-1.5 text-category-amber">savings</span>
                        <span>本月結餘 {{ $monthNet >= 0 ? '+' : '' }}NT$ {{ number_format($monthNet) }}</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Quick Stats Bento Grid (Stitch 5.1 Bento-ish Grid) -->
        <section class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <!-- 1. 本週已花 -->
            <div class="bg-surface-pure rounded-2xl p-5 border border-border-base shadow-sm flex flex-col justify-between hover:border-category-orange/40 transition-colors">
                <div class="flex items-center gap-2 text-category-orange mb-2">
                    <span class="material-symbols-outlined text-2xl">shopping_cart</span>
                    <span class="font-bold text-xs">{{ __('auto.0425') }}</span>
                </div>
                <div class="font-mono text-2xl font-black text-text-primary">
                    NT$ {{ number_format($weekExpense) }}
                </div>
            </div>

            <!-- 2. 目標達成 -->
            <div class="bg-surface-pure rounded-2xl p-5 border border-border-base shadow-sm flex flex-col justify-between hover:border-category-sky/40 transition-colors">
                <div class="flex items-center gap-2 text-category-sky mb-2">
                    <span class="material-symbols-outlined text-2xl">flag</span>
                    <span class="font-bold text-xs">{{ __('auto.0093') }}</span>
                </div>
                @if($activeGoal)
                    <div class="flex items-baseline gap-1">
                        <span class="font-mono text-2xl font-black text-text-primary">NT$ {{ number_format($activeGoal->current_amount) }}</span>
                        <span class="text-xs text-on-surface-variant font-mono">/ {{ number_format($activeGoal->target_amount) }}</span>
                    </div>
                @else
                    <div class="text-xs text-on-surface-variant font-medium">尚未設定目標</div>
                @endif
            </div>

            <!-- 3. 做家事 / 獎勵任務 -->
            <div class="bg-surface-pure rounded-2xl p-5 border border-border-base shadow-sm flex flex-col justify-between col-span-2 md:col-span-1 hover:border-category-amber/40 transition-colors">
                <div class="flex items-center gap-2 text-category-amber mb-2">
                    <span class="material-symbols-outlined text-2xl">star</span>
                    <span class="font-bold text-xs">{{ __('auto.0270') }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-on-surface-variant font-semibold">{{ __('auto.0653') }}</span>
                    <a href="{{ route('tasks.index') }}" class="px-3.5 py-1.5 bg-category-amber/15 text-category-amber hover:bg-category-amber hover:text-white font-bold text-xs rounded-xl transition-all shadow-sm">
                        前往任務
                    </a>
                </div>
            </div>
        </section>

        <!-- Action Grid (2x2) 「我想做什麼？」 (Google Stitch 5.1) -->
        <section class="space-y-4">
            <h3 class="text-lg font-bold text-text-primary flex items-center gap-2">
                <span class="material-symbols-outlined text-primary-container text-2xl">explore</span>
                <span>{{ __('auto.0335') }}</span>
            </h3>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <!-- 1. 記一筆支出 -->
                @if($isRestrictedAdmin ?? false)
                    <div class="bg-surface-pure border-2 border-dashed border-border-base rounded-2xl p-5 shadow-sm flex flex-col items-center justify-center gap-2 min-h-[130px] opacity-70 cursor-not-allowed select-none text-center">
                        <div class="w-12 h-12 rounded-2xl bg-surface-container-high text-on-surface-variant/60 flex items-center justify-center shadow-sm">
                            <span class="material-symbols-outlined text-2xl">shopping_cart</span>
                        </div>
                        <span class="font-bold text-sm text-on-surface-variant">{{ __('action.add_expense') }}</span>
                        <span class="text-[10px] px-2 py-0.5 bg-surface-container-high rounded-full text-on-surface-variant font-bold">🔒 管理員限制</span>
                    </div>
                @else
                    <a href="{{ route('transactions.create', ['type' => 'expense']) }}" 
                       class="bg-surface-pure border border-border-base rounded-2xl p-5 shadow-sm flex flex-col items-center justify-center gap-2.5 hover:border-danger hover:shadow-md transition-all group min-h-[130px] active:scale-95">
                        <div class="w-13 h-13 rounded-2xl bg-danger/15 text-danger flex items-center justify-center group-hover:scale-110 transition-transform shadow-sm">
                            <span class="material-symbols-outlined text-3xl">shopping_cart</span>
                        </div>
                        <span class="font-bold text-sm text-text-primary group-hover:text-danger transition-colors">{{ __('action.add_expense') }}</span>
                    </a>
                @endif

                <!-- 2. 記一筆收入 -->
                @if($isRestrictedAdmin ?? false)
                    <div class="bg-surface-pure border-2 border-dashed border-border-base rounded-2xl p-5 shadow-sm flex flex-col items-center justify-center gap-2 min-h-[130px] opacity-70 cursor-not-allowed select-none text-center">
                        <div class="w-12 h-12 rounded-2xl bg-surface-container-high text-on-surface-variant/60 flex items-center justify-center shadow-sm">
                            <span class="material-symbols-outlined text-2xl">savings</span>
                        </div>
                        <span class="font-bold text-sm text-on-surface-variant">{{ __('action.add_income') }}</span>
                        <span class="text-[10px] px-2 py-0.5 bg-surface-container-high rounded-full text-on-surface-variant font-bold">🔒 管理員限制</span>
                    </div>
                @else
                    <a href="{{ route('transactions.create', ['type' => 'income']) }}" 
                       class="bg-surface-pure border border-border-base rounded-2xl p-5 shadow-sm flex flex-col items-center justify-center gap-2.5 hover:border-success hover:shadow-md transition-all group min-h-[130px] active:scale-95">
                        <div class="w-13 h-13 rounded-2xl bg-success/15 text-success flex items-center justify-center group-hover:scale-110 transition-transform shadow-sm">
                            <span class="material-symbols-outlined text-3xl">savings</span>
                        </div>
                        <span class="font-bold text-sm text-text-primary group-hover:text-success transition-colors">{{ __('action.add_income') }}</span>
                    </a>
                @endif

                <!-- 3. 看我的目標 -->
                <a href="{{ route('saving-goals.index') }}" 
                   class="bg-surface-pure border border-border-base rounded-2xl p-5 shadow-sm flex flex-col items-center justify-center gap-2.5 hover:border-category-sky hover:shadow-md transition-all group min-h-[130px] active:scale-95">
                    <div class="w-13 h-13 rounded-2xl bg-category-sky/15 text-category-sky flex items-center justify-center group-hover:scale-110 transition-transform shadow-sm">
                        <span class="material-symbols-outlined text-3xl">sports_score</span>
                    </div>
                    <span class="font-bold text-sm text-text-primary group-hover:text-category-sky transition-colors">{{ __('auto.0550') }}</span>
                </a>

                <!-- 4. 做家事任務 -->
                <a href="{{ route('tasks.index') }}" 
                   class="bg-surface-pure border border-border-base rounded-2xl p-5 shadow-sm flex flex-col items-center justify-center gap-2.5 hover:border-category-amber hover:shadow-md transition-all group min-h-[130px] active:scale-95">
                    <div class="w-13 h-13 rounded-2xl bg-category-amber/15 text-category-amber flex items-center justify-center group-hover:scale-110 transition-transform shadow-sm">
                        <span class="material-symbols-outlined text-3xl">star</span>
                    </div>
                    <span class="font-bold text-sm text-text-primary group-hover:text-category-amber transition-colors">{{ __('auto.0270') }}</span>
                </a>
            </div>
        </section>

        <!-- Active Saving Goal Shortcut Card (進行中儲蓄目標快捷區塊) -->
        @if($activeGoal)
            @php
                $target = (float) $activeGoal->target_amount;
                $current = (float) $activeGoal->current_amount;
                $gap = max(0, $target - $current);
                $percent = $target > 0 ? min(100, round(($current / $target) * 100)) : 0;
            @endphp
            <section class="bg-surface-pure border border-border-base rounded-2xl p-6 shadow-sm relative overflow-hidden">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-category-sky/15 text-category-sky flex items-center justify-center shadow-sm">
                            <span class="material-symbols-outlined text-2xl">{{ \App\Support\IconHelper::name($activeGoal->icon ?? null, 'sports_esports') }}</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">{{ __('auto.0527') }}</span>
                                @if($percent >= 100)
                                    <span class="px-2 py-0.5 bg-success/10 text-success border border-success/20 rounded-md text-[10px] font-bold">{{ __('auto.0313') }}</span>
                                @endif
                            </div>
                            <h4 class="text-lg font-black text-text-primary">{{ $activeGoal->name }}</h4>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-xs text-on-surface-variant">{{ __('auto.0697') }}</span>
                        <span class="text-lg font-bold text-category-orange font-mono">NT$ {{ number_format($gap) }}</span>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="space-y-1.5">
                    <div class="h-3.5 bg-surface-variant rounded-full overflow-hidden p-0.5">
                        <div class="h-full bg-gradient-to-r from-primary-container to-category-mint rounded-full transition-all duration-700 shadow-sm" style="width: {{ $percent }}%;"></div>
                    </div>
                    <div class="flex justify-between text-xs font-semibold text-on-surface-variant font-mono">
                        <span>已存 NT$ {{ number_format($current) }} ({{ $percent }}%)</span>
                        <span>目標 NT$ {{ number_format($target) }}</span>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-border-base flex justify-end">
                    <a href="{{ route('saving-goals.index') }}" class="text-xs font-bold text-primary hover:underline flex items-center gap-1">
                        <span>{{ __('auto.0573') }}</span>
                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </a>
                </div>
            </section>
        @endif

        <!-- Recent Transactions (零用錢收支流水帳明細表) -->
        <section class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold text-text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-2xl">format_list_bulleted</span>
                    <span>{{ __('auto.0668') }}</span>
                </h3>
                <a href="{{ route('transactions.index') }}" class="text-xs font-bold text-primary hover:underline flex items-center gap-1">
                    <span>{{ __('auto.0429') }}</span>
                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>

            <div class="bg-surface-pure rounded-2xl border border-border-base shadow-sm overflow-hidden divide-y divide-border-base/70">
                @forelse($transactions as $tx)
                    @php
                        $isIncome = ($tx->type === 'income');
                    @endphp
                    <div class="p-4 hover:bg-surface-container-low/60 transition-colors flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3.5 min-w-0">
                            <!-- Category Icon -->
                            <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0 shadow-sm {{ $isIncome ? 'bg-category-mint/15 text-category-mint' : 'bg-category-orange/15 text-category-orange' }}">
                                <span class="material-symbols-outlined text-xl">
                                    {{ \App\Support\IconHelper::name($tx->category?->icon ?? null, ($isIncome ? 'savings' : 'shopping_bag')) }}
                                </span>
                            </div>

                            <div class="min-w-0">
                                <h4 class="text-sm font-bold text-text-primary truncate">
                                    {{ $tx->description ?? ($isIncome ? '零用錢入帳' : '一般消費') }}
                                </h4>
                                <div class="flex items-center gap-2 mt-0.5 text-xs text-on-surface-variant">
                                    <span>{{ $tx->occurred_at?->format('Y-m-d H:i') ?? '剛才' }}</span>
                                    @if($tx->category)
                                        <span>•</span>
                                        <span class="font-semibold text-primary">{{ $tx->category->name }}</span>
                                    @endif
                                    @if($tx->notes)
                                        <span>•</span>
                                        <span class="italic text-on-surface-variant truncate max-w-[150px]">{{ $tx->notes }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Amount -->
                        <div class="text-right flex-shrink-0 font-mono">
                            <span class="text-base font-black {{ $isIncome ? 'text-success' : 'text-danger' }}">
                                {{ $isIncome ? '+' : '-' }} NT$ {{ number_format($tx->amount) }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="p-12 text-center text-on-surface-variant">
                        <span class="material-symbols-outlined text-4xl text-on-surface-variant/40 mb-2">receipt_long</span>
                        <p class="text-sm font-medium">{{ __('auto.0531') }}</p>
                        <p class="text-xs text-on-surface-variant mt-1">{{ __('auto.0708') }}</p>
                    </div>
                @endforelse
            </div>
        </section>

        <!-- Alpine.js 發放零用錢 Modal (家長端) -->
        <div x-show="isAllowanceModalOpen" 
             x-cloak 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-on-surface/40 backdrop-blur-sm"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            
            <div @click.outside="isAllowanceModalOpen = false" 
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
                            <span class="material-symbols-outlined text-xl">redeem</span>
                        </div>
                        <h2 class="font-bold text-lg text-text-primary">{{ __('auto.0516') }}</h2>
                    </div>
                    <button type="button" @click="isAllowanceModalOpen = false" class="w-8 h-8 rounded-lg hover:bg-surface-container flex items-center justify-center text-on-surface-variant transition-colors">
                        <span class="material-symbols-outlined text-xl">close</span>
                    </button>
                </div>

                <form action="{{ route('child-wallet.deposit') }}" method="POST" class="p-6 space-y-5">
                    @csrf
                    <!-- 1. 選擇發放對象 -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">{{ __('auto.0513') }}</label>
                        <select name="child_user_id" x-model="allowanceForm.child_user_id" required class="w-full bg-surface-pure border border-border-base rounded-xl px-3.5 py-2.5 text-sm font-semibold text-text-primary focus:outline-none focus:border-primary shadow-sm">
                            @foreach($children as $c)
                                <option value="{{ $c->id }}">👦 {{ $c->name }} (@ {{ $c->account }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 2. 扣款出資帳戶 -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">{{ __('auto.0345') }}</label>
                        <select name="account_id" required class="w-full bg-surface-pure border border-border-base rounded-xl px-3.5 py-2.5 text-sm font-semibold text-text-primary focus:outline-none focus:border-primary shadow-sm">
                            @foreach($parentAccounts as $acc)
                                <option value="{{ $acc->id }}">💳 {{ $acc->name }} (餘額 NT$ {{ number_format($acc->balance) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 3. 發放金額與快捷按鈕 -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">{{ __('auto.0514') }}</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 font-bold text-sm text-on-surface-variant font-mono">NT$</span>
                            <input type="number" 
                                   name="amount" 
                                   x-model="allowanceForm.amount" 
                                   required 
                                   min="1" 
                                   step="1" 
                                   placeholder="{{ __('auto.0664') }}" 
                                   class="w-full pl-13 pr-4 py-2.5 bg-surface-pure border border-border-base rounded-xl font-bold text-lg text-primary focus:outline-none focus:border-primary shadow-sm font-mono">
                        </div>
                        <!-- Quick Amount Tags -->
                        <div class="flex gap-2 mt-2">
                            <button type="button" @click="allowanceForm.amount = 100" class="px-2.5 py-1 bg-surface-container-low hover:bg-primary/10 hover:text-primary rounded-lg text-xs font-bold border border-border-base transition-colors">NT$ 100</button>
                            <button type="button" @click="allowanceForm.amount = 200" class="px-2.5 py-1 bg-surface-container-low hover:bg-primary/10 hover:text-primary rounded-lg text-xs font-bold border border-border-base transition-colors">NT$ 200</button>
                            <button type="button" @click="allowanceForm.amount = 500" class="px-2.5 py-1 bg-surface-container-low hover:bg-primary/10 hover:text-primary rounded-lg text-xs font-bold border border-border-base transition-colors">NT$ 500</button>
                            <button type="button" @click="allowanceForm.amount = 1000" class="px-2.5 py-1 bg-surface-container-low hover:bg-primary/10 hover:text-primary rounded-lg text-xs font-bold border border-border-base transition-colors">NT$ 1,000</button>
                        </div>
                    </div>

                    <!-- 4. 發放項目與快捷標籤 -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">{{ __('auto.0512') }}</label>
                        <input type="text" 
                               name="description" 
                               x-model="allowanceForm.description" 
                               required 
                               placeholder="{{ __('auto.0122') }}" 
                               class="w-full bg-surface-pure border border-border-base rounded-xl px-3.5 py-2.5 text-sm font-semibold text-text-primary focus:outline-none focus:border-primary shadow-sm">
                        
                        <!-- Quick Description Tags -->
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            <button type="button" @click="allowanceForm.description = '每月定期零用錢'" class="px-2.5 py-1 bg-surface-container-low hover:bg-category-mint/10 hover:text-category-mint rounded-lg text-xs font-medium border border-border-base transition-colors">📅 每月定期零用錢</button>
                            <button type="button" @click="allowanceForm.description = '幫忙洗碗與倒垃圾獎勵'" class="px-2.5 py-1 bg-surface-container-low hover:bg-category-mint/10 hover:text-category-mint rounded-lg text-xs font-medium border border-border-base transition-colors">🧹 家事獎勵</button>
                            <button type="button" @click="allowanceForm.description = '期中考表現優異獎金'" class="px-2.5 py-1 bg-surface-container-low hover:bg-category-mint/10 hover:text-category-mint rounded-lg text-xs font-medium border border-border-base transition-colors">📝 學習進步獎</button>
                        </div>
                    </div>

                    <!-- 5. 備註 -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">{{ __('auto.0290') }}</label>
                        <textarea name="note" rows="2" placeholder="{{ __('auto.0298') }}" class="w-full bg-surface-pure border border-border-base rounded-xl p-3 text-xs text-text-primary focus:outline-none focus:border-primary shadow-sm"></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="pt-3 border-t border-border-base flex gap-3">
                        <button type="button" @click="isAllowanceModalOpen = false" class="flex-1 py-2.5 px-4 rounded-xl border border-border-base font-bold text-sm text-on-surface-variant hover:bg-surface-container transition-colors">{{ __('common.cancel') }}</button>
                        <button type="submit" class="flex-1 py-2.5 px-4 rounded-xl bg-gradient-to-r from-primary to-category-mint font-bold text-sm text-white hover:opacity-95 transition-all shadow-sm active:scale-95">
                            確認發放
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function childWalletPage() {
            return {
                isAllowanceModalOpen: false,
                allowanceForm: {
                    child_user_id: '{{ $selectedChild?->id ?? "" }}',
                    amount: '200',
                    description: '每月定期零用錢',
                },
                openAllowanceModal(child) {
                    if (child) {
                        this.allowanceForm.child_user_id = child.id;
                    }
                    this.isAllowanceModalOpen = true;
                }
            };
        }
    </script>
</x-app-layout>
