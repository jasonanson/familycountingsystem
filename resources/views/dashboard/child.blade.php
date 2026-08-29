<x-app-layout>
    <x-slot name="title">{{ __('auto.0337') }}</x-slot>

    <div class="space-y-6">

        @if($isRestrictedAdmin ?? false)
            <div class="bg-warning/15 border-2 border-warning/30 rounded-2xl p-4 flex items-start gap-3 text-on-surface shadow-sm">
                <span class="material-symbols-outlined text-warning text-2xl shrink-0 mt-0.5">lock_person</span>
                <div>
                    <h4 class="font-bold text-sm text-warning flex items-center gap-1.5">
                        <span>{{ __('auto.0583') }}</span>
                        <span class="px-2 py-0.2 bg-warning/20 text-warning text-[11px] font-bold rounded-md">{{ __('auto.0255') }}</span>
                    </h4>
                    <p class="text-xs text-on-surface-variant mt-0.5">
                        您目前正在檢視小孩專屬極簡面板畫面（檢視對象：<strong>{{ $targetChild->name ?? '孩童' }}</strong>）。依系統規範，管理員為大人身分，此面板僅供畫面檢視與預覽，無法直接由此執行小孩專屬記帳。大人記帳請由大人專屬面板進行。
                    </p>
                </div>
            </div>
        @endif

        <section class="flex items-center gap-3">
            <div class="w-16 h-16 rounded-full bg-gradient-to-br from-warning to-category-orange flex items-center justify-center text-3xl shadow-md">
                👋
            </div>
            <div>
                <h2 class="text-2xl font-black text-on-surface flex items-center gap-2">
                    <span>Hi, {{ $targetChild->name ?? $user->name }}！</span>
                    @if($isRestrictedAdmin ?? false)
                        <span class="text-xs font-semibold px-2 py-0.5 bg-warning/20 text-warning rounded-full border border-warning/30">{{ __('auto.0237') }}</span>
                    @endif
                </h2>
                <p class="text-sm text-on-surface-variant">今天是 {{ now()->format("Y 年 m 月 d 日") }}，家裡是「{{ $familyName }}」</p>
            </div>
        </section>

        <section class="bg-gradient-to-br from-success to-success rounded-3xl p-8 text-white shadow-xl">
            <div class="flex items-center gap-2 mb-2">
                <span class="material-symbols-outlined text-[28px]">savings</span>
                <span class="text-base font-bold opacity-90">我的零用錢</span>
            </div>
            <p class="text-5xl font-black tracking-tight">NT$ {{ number_format($balance) }}</p>
            <p class="text-sm opacity-90 mt-2">這個月家長給了我 NT$ {{ number_format($deposits ?? 0) }}，花了 NT$ {{ number_format($monthlySpent) }}</p>
        </section>

        @if ($limit)
            <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @if ($limit->per_transaction_max)
                    <div class="bg-surface-pure rounded-2xl p-5 border-2 border-border-base shadow-sm">
                        <div class="flex items-center gap-2 text-on-surface-variant">
                            <span class="material-symbols-outlined text-[20px]">receipt_long</span>
                            <span class="text-sm font-bold">{{ __('limit.single') }}</span>
                        </div>
                        <p class="text-2xl font-black mt-2">NT$ {{ number_format($limit->per_transaction_max) }}</p>
                        <p class="text-xs text-on-surface-variant mt-1">{{ __('auto.0459') }}</p>
                    </div>
                @endif
                @if ($dailyRemaining !== null)
                    <div class="bg-surface-pure rounded-2xl p-5 border-2 {{ $dailyRemaining < 50 ? "border-danger/30 bg-danger/10" : "border-success/20" }} shadow-sm">
                        <div class="flex items-center gap-2 {{ $dailyRemaining < 50 ? "text-danger" : "text-success" }}">
                            <span class="material-symbols-outlined text-[20px]">today</span>
                            <span class="text-sm font-bold">今天還能用</span>
                        </div>
                        <p class="text-2xl font-black mt-2 {{ $dailyRemaining < 50 ? "text-danger" : "text-success" }}">
                            NT$ {{ number_format($dailyRemaining) }}
                        </p>
                        <p class="text-xs text-on-surface-variant mt-1">已花 NT$ {{ number_format($dailySpent) }} / 上限 NT$ {{ number_format($limit->daily_max) }}</p>
                    </div>
                @endif
                @if ($weeklyRemaining !== null)
                    <div class="bg-surface-pure rounded-2xl p-5 border-2 {{ $weeklyRemaining < 100 ? "border-warning/30 bg-warning/10" : "border-success/20" }} shadow-sm">
                        <div class="flex items-center gap-2 {{ $weeklyRemaining < 100 ? "text-warning" : "text-success" }}">
                            <span class="material-symbols-outlined text-[20px]">date_range</span>
                            <span class="text-sm font-bold">本週還能用</span>
                        </div>
                        <p class="text-2xl font-black mt-2 {{ $weeklyRemaining < 100 ? "text-warning" : "text-success" }}">
                            NT$ {{ number_format($weeklyRemaining) }}
                        </p>
                        <p class="text-xs text-on-surface-variant mt-1">已花 NT$ {{ number_format($weeklySpent) }} / 上限 NT$ {{ number_format($limit->weekly_max) }}</p>
                    </div>
                @endif
                @if ($monthlyRemaining !== null)
                    <div class="bg-surface-pure rounded-2xl p-5 border-2 border-success/20 shadow-sm">
                        <div class="flex items-center gap-2 text-success">
                            <span class="material-symbols-outlined text-[20px]">calendar_month</span>
                            <span class="text-sm font-bold">本月剩餘</span>
                        </div>
                        <p class="text-2xl font-black mt-2 text-success">NT$ {{ number_format($monthlyRemaining) }}</p>
                        <p class="text-xs text-on-surface-variant mt-1">已花 NT$ {{ number_format($monthlySpent) }} / 上限 NT$ {{ number_format($limit->monthly_max) }}</p>
                    </div>
                @endif
            </section>
        @else
            <div class="bg-warning/10 border-2 border-warning/20 rounded-2xl p-5 text-center">
                <span class="material-symbols-outlined text-warning text-4xl">info</span>
                <p class="text-sm font-bold text-warning mt-2">{{ __('auto.0295') }}</p>
                <p class="text-xs text-warning mt-1">{{ __('auto.0637') }}</p>
            </div>
        @endif

        @if($isRestrictedAdmin ?? false)
            <div class="grid grid-cols-2 gap-3 opacity-70">
                <div class="bg-surface-pure border-2 border-dashed border-border-base rounded-2xl p-6 flex flex-col items-center gap-2 cursor-not-allowed select-none text-center shadow-sm">
                    <span class="material-symbols-outlined text-[40px] text-on-surface-variant/60">shopping_cart</span>
                    <span class="text-lg font-black text-on-surface-variant">記一筆支出</span>
                    <span class="text-xs px-2.5 py-0.5 bg-surface-container-high rounded-full text-on-surface-variant font-bold">🔒 管理員限制使用</span>
                </div>
                <div class="bg-surface-pure border-2 border-dashed border-border-base rounded-2xl p-6 flex flex-col items-center gap-2 cursor-not-allowed select-none text-center shadow-sm">
                    <span class="material-symbols-outlined text-[40px] text-on-surface-variant/60">redeem</span>
                    <span class="text-lg font-black text-on-surface-variant">記一筆收入</span>
                    <span class="text-xs px-2.5 py-0.5 bg-surface-container-high rounded-full text-on-surface-variant font-bold">🔒 管理員限制使用</span>
                </div>
            </div>
        @else
            <section class="grid grid-cols-2 gap-3">
                <a wire:navigate.hover href="{{ route("transactions.create", ["type" => "expense"]) }}"
                   class="bg-danger/15 hover:bg-danger/20 text-danger rounded-2xl p-6 flex flex-col items-center gap-2 transition-all shadow-sm">
                    <span class="material-symbols-outlined text-[40px]">shopping_cart</span>
                    <span class="text-lg font-black">記一筆支出</span>
                    <span class="text-xs opacity-75">{{ __('auto.0646') }}</span>
                </a>
                <a wire:navigate.hover href="{{ route("transactions.create", ["type" => "income"]) }}"
                   class="bg-success/15 hover:bg-success/20 text-success rounded-2xl p-6 flex flex-col items-center gap-2 transition-all shadow-sm">
                    <span class="material-symbols-outlined text-[40px]">redeem</span>
                    <span class="text-lg font-black">記一筆收入</span>
                    <span class="text-xs opacity-75">{{ __('auto.0373') }}</span>
                </a>
            </section>
        @endif

        @if ($goals->count() > 0)
            <section class="bg-surface-pure rounded-2xl p-card-padding border border-border-base shadow-sm">
                <h3 class="text-lg font-black text-on-surface mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-warning">flag</span>
                    我的存錢目標
                </h3>
                <div class="space-y-3">
                    @foreach ($goals as $g)
                        @php $pct = $g->target_amount > 0 ? min(100, round(((float)$g->current_amount / (float)$g->target_amount) * 100, 1)) : 0; @endphp
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-bold">{{ $g->name }}</span>
                                <span class="text-on-surface-variant">NT$ {{ number_format($g->current_amount) }} / {{ number_format($g->target_amount) }}</span>
                            </div>
                            <div class="h-3 bg-surface-container-high rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-warning to-warning rounded-full transition-all" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="bg-surface-pure rounded-2xl p-card-padding border border-border-base shadow-sm">
            <h3 class="text-lg font-black text-on-surface mb-3 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">history</span>
                我最近的紀錄
            </h3>
            @if ($recentTransactions->isEmpty())
                <div class="text-center py-10 text-on-surface-variant/70">
                    <span class="material-symbols-outlined text-5xl block mb-2">receipt_long</span>
                    還沒有任何紀錄
                </div>
            @else
                <div class="space-y-2">
                    @foreach ($recentTransactions as $tx)
                        <div class="flex items-center justify-between p-3 bg-surface rounded-xl">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl {{ $tx->type === "expense" ? "bg-danger/15 text-danger" : "bg-success/15 text-success" }} flex items-center justify-center">
                                    <span class="material-symbols-outlined text-xl">{{ $tx->type === "expense" ? "shopping_cart" : "redeem" }}</span>
                                </div>
                                <div>
                                    <p class="font-bold text-sm">{{ $tx->category->name ?? "其他" }}</p>
                                    <p class="text-xs text-on-surface-variant">{{ $tx->occurred_at?->format("m/d H:i") }}</p>
                                </div>
                            </div>
                            <span class="font-black {{ $tx->type === "expense" ? "text-danger" : "text-success" }}">
                                {{ $tx->type === "expense" ? "-" : "+" }}NT$ {{ number_format($tx->amount) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

    </div>
</x-app-layout>
