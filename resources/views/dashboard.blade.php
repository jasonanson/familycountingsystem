<x-app-layout>
    <x-slot name="title">{{ __('dashboard.title') }}</x-slot>

    <div x-data="{ showModal: false, activeTab: 'expense' }" class="space-y-6">

        <!-- Greeting Section -->
        <section class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-on-surface flex items-center gap-2">
                    <span>{{ __('dashboard.greeting', ['name' => auth()->user()->name]) }}</span>
                    <span class="material-symbols-outlined text-primary text-[24px]">front_hand</span>
                </h2>
                <p class="text-sm text-on-surface-variant mt-1">{{ now()->format(__('date.format')) }} · {{ __('dashboard.family') }}：<strong class="text-primary">{{ $family?->name ?? __('dashboard.default_family') }}</strong></p>
            </div>
            <button @click="showModal = true" class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-primary hover:bg-primary/90 text-on-primary font-bold text-base shadow-md transition-all hover:scale-105">
                <span class="material-symbols-outlined text-[18px]">add_circle</span>
                <span>{{ __('dashboard.quick_add') }}</span>
            </button>
        </section>

        <!-- Stat Cards (Bento) -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-grid-gap">
            <!-- Monthly Income -->
            <div class="bg-surface-pure rounded-xl p-card-padding shadow-[0_4px_12px_rgba(28,25,23,0.05)] border border-border-base flex flex-col justify-between h-32 relative overflow-hidden group">
                <div class="absolute -right-6 -top-6 w-24 h-24 bg-primary/5 rounded-full"></div>
                <p class="text-sm text-on-surface-variant flex items-center gap-2 z-10 font-medium">
                    <span class="material-symbols-outlined text-primary text-[18px]">trending_up</span>
                    {{ __('dashboard.monthly_income') }}
                </p>
                <div>
                    <p class="text-2xl font-bold text-primary z-10">NT$ {{ number_format($monthlyIncome) }}</p>
                    <p class="text-xs text-primary mt-1">{{ __('dashboard.real_time') }}</p>
                </div>
            </div>

            <!-- Monthly Expense -->
            <div class="bg-surface-pure rounded-xl p-card-padding shadow-[0_4px_12px_rgba(28,25,23,0.05)] border border-border-base flex flex-col justify-between h-32 relative overflow-hidden group">
                <div class="absolute -right-6 -top-6 w-24 h-24 bg-danger/5 rounded-full"></div>
                <p class="text-sm text-on-surface-variant flex items-center gap-2 z-10 font-medium">
                    <span class="material-symbols-outlined text-danger text-[18px]">trending_down</span>
                    {{ __('dashboard.monthly_expense') }}
                </p>
                <div>
                    <p class="text-2xl font-bold text-danger z-10">NT$ {{ number_format($monthlyExpense) }}</p>
                    <p class="text-xs text-danger mt-1">{{ __('dashboard.budget_usage', ['pct' => $budgetUsagePercent]) }}</p>
                </div>
            </div>

            <!-- Net Balance -->
            <div class="bg-surface-pure rounded-xl p-card-padding shadow-[0_4px_12px_rgba(28,25,23,0.05)] border border-border-base flex flex-col justify-between h-32 relative overflow-hidden group">
                <div class="absolute -right-6 -top-6 w-24 h-24 bg-success/5 rounded-full"></div>
                <p class="text-sm text-on-surface-variant flex items-center gap-2 z-10 font-medium">
                    <span class="material-symbols-outlined text-success text-[18px]">account_balance_wallet</span>
                    {{ __('dashboard.net_balance') }}
                </p>
                <div>
                    <p class="text-2xl font-bold text-success z-10">NT$ {{ number_format($netBalance) }}</p>
                    <p class="text-xs text-success mt-1 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">auto_awesome</span>
                        {{ __('dashboard.good') }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Budget Progress & Breakdown -->
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-grid-gap">
            <!-- Budget Progress Card -->
            <div class="bg-surface-pure rounded-xl p-card-padding shadow-[0_4px_12px_rgba(28,25,23,0.05)] border border-border-base flex flex-col gap-5">
                <div class="flex justify-between items-center">
                    <h3 class="font-bold text-lg text-on-surface">{{ __('dashboard.budget_pool') }}</h3>
                    <span class="text-sm text-primary font-semibold">{{ __('dashboard.target') }}： NT$ {{ number_format($poolAmount) }}</span>
                </div>

                <div class="space-y-4">
                    <div class="flex flex-col gap-2">
                        <div class="flex justify-between items-center text-sm">
                            <span class="font-medium text-on-surface">{{ __('dashboard.budget_consumption') }}</span>
                            <span class="font-bold text-on-surface-variant">{{ $budgetUsagePercent }}%</span>
                        </div>
                        <div class="w-full bg-surface-variant rounded-full h-2.5 overflow-hidden">
                            <div class="h-2.5 rounded-full transition-all duration-500 {{ $budgetUsagePercent >= 100 ? 'bg-danger' : ($budgetUsagePercent >= 80 ? 'bg-warning' : 'bg-primary') }}"
                                 style="width: {{ min(100, $budgetUsagePercent) }}%"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-2 text-sm text-on-surface-variant border-t border-border-base">
                        <div>{{ __('dashboard.spent') }}：<strong class="text-danger font-bold">NT$ {{ number_format($monthlyExpense) }}</strong></div>
                        <div>{{ __('dashboard.remaining') }}：<strong class="text-success font-bold">NT$ {{ number_format(max(0, $poolAmount - $monthlyExpense)) }}</strong></div>
                    </div>
                </div>
            </div>

            <!-- Breakdown Pie Representation -->
            <div class="bg-surface-pure rounded-xl p-card-padding shadow-[0_4px_12px_rgba(28,25,23,0.05)] border border-border-base flex items-center justify-around">
                <div class="flex justify-center items-center relative">
                    <div class="w-28 h-28 rounded-full border-8 border-surface-variant relative" style="border-right-color: #F97316; border-top-color: #60A5FA; border-left-color: #A78BFA; transform: rotate(45deg);"></div>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-xs text-on-surface-variant">{{ __('dashboard.this_month_expense') }}</span>
                        <span class="text-sm font-bold text-on-surface">NT$ {{ number_format($monthlyExpense) }}</span>
                    </div>
                </div>
                <div class="space-y-2">
                    <h3 class="font-bold text-base text-on-surface">{{ __('dashboard.expense_breakdown') }}</h3>
                    <ul class="space-y-1.5 text-sm text-on-surface-variant">
                        @forelse($expenseBreakdown as $index => $breakdown)
                            <li class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full {{ ['bg-category-orange', 'bg-category-sky', 'bg-category-lavender'][(int) $index % 3] ?? 'bg-primary' }}"></span>
                                {{ $breakdown->category?->name ?? ($breakdown->type_custom ?: __('dashboard.uncategorized')) }}
                            </li>
                        @empty
                            <li class="flex items-center gap-2 text-on-surface-variant text-xs">{{ __('dashboard.no_expense_data') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </section>

        <!-- Recent Transactions (Google Stitch Style) -->
        <section class="bg-surface-pure rounded-xl p-card-padding shadow-[0_4px_12px_rgba(28,25,23,0.05)] border border-border-base">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-lg text-on-surface">{{ __('dashboard.recent_tx') }}</h3>
                <a href="{{ route('transactions.index') }}" class="text-sm font-bold text-primary hover:underline">{{ __('dashboard.view_all_tx') }}</a>
            </div>

            <div class="space-y-2">
                @forelse($recentTransactions as $tx)
                    <div class="flex justify-between items-center py-3 border-l-4 border-primary bg-surface-container/40 rounded-r-lg px-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-primary/10 text-primary flex items-center justify-center">
                                <span class="material-symbols-outlined text-[18px]">
                                    {{ $tx->type === 'expense' ? 'shopping_cart' : ($tx->type === 'income' ? 'payments' : 'sync') }}
                                </span>
                            </div>
                            <div>
                                <p class="font-bold text-base text-on-surface">
                                    {{ $tx->category?->name ?? ($tx->type_custom ?: __('dashboard.custom_uncategorized')) }}
                                    @if($tx->payee_custom) <span class="text-sm text-on-surface-variant font-normal">({{ $tx->payee_custom }})</span> @endif
                                </p>
                                <p class="text-xs text-on-surface-variant">
                                    {{ $tx->occurred_at->format('Y-m-d H:i') }} · {{ __('dashboard.account_colon') }}{{ $tx->account?->name ?? __('dashboard.default_account') }}
                                </p>
                            </div>
                        </div>
                        <p class="font-bold text-base {{ $tx->type === 'expense' ? 'text-danger' : 'text-success' }}">
                            {{ $tx->type === 'expense' ? '-' : '+' }}NT$ {{ number_format($tx->amount) }}
                        </p>
                    </div>
                @empty
                    <div class="py-8 text-center text-sm text-on-surface-variant">
                        {{ __('dashboard.no_tx_yet') }}
                    </div>
                @endforelse
            </div>
        </section>

        @if(auth()->user()->canEditCurrentFamily())
        <!-- Quick Add Modal -->
        <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showModal" x-transition.opacity @click="showModal = false" class="fixed inset-0 bg-on-surface/40 backdrop-blur-sm"></div>

                <div x-show="showModal" x-transition
                     class="inline-block align-bottom bg-surface-pure border border-border-base rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">

                    <div class="flex items-center justify-between border-b border-border-base bg-surface-container p-2">
                        <div class="flex flex-1 gap-2">
                            <button type="button" @click="activeTab = 'expense'"
                                    :class="activeTab === 'expense' ? 'bg-danger text-white' : 'text-on-surface-variant hover:bg-surface-container-high'"
                                    class="flex-1 py-2 text-sm font-bold rounded-lg transition-all cursor-pointer">
                                💸 {{ __('tx_page.new_expense') }}
                            </button>
                            <button type="button" @click="activeTab = 'income'"
                                    :class="activeTab === 'income' ? 'bg-success text-white' : 'text-on-surface-variant hover:bg-surface-container-high'"
                                    class="flex-1 py-2 text-sm font-bold rounded-lg transition-all cursor-pointer">
                                💰 {{ __('tx_page.new_income') }}
                            </button>
                        </div>
                        <a href="{{ route('transactions.create') }}" class="ml-2 px-3 py-1.5 text-xs text-primary font-bold hover:underline flex items-center gap-1 shrink-0" title="{{ __('nav.transaction_create') }}">
                            <span>{{ __('action.full_record') }}</span>
                            <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                        </a>
                    </div>

                    <form action="{{ route('transactions.store') }}" method="POST" class="p-6 space-y-4">
                        @csrf
                        <input type="hidden" name="type" :value="activeTab">

                        <div class="space-y-1">
                            <label class="block text-sm font-bold text-on-surface">{{ __('field.amount') }}</label>
                            <input type="number" name="amount" step="1" required placeholder="{{ __('field.amount_placeholder') }}"
                                   class="w-full px-4 py-2.5 bg-background-warm border border-border-base rounded-xl text-base focus:outline-none focus:border-primary">
                            <input type="hidden" name="occurred_at" value="{{ now()->format('Y-m-d\TH:i') }}">
                        </div>

                        <div class="flex justify-end gap-2 pt-4">
                            <button type="button" @click="showModal = false" class="px-4 py-2 text-on-surface-variant hover:bg-surface-container rounded-xl transition-all">
                                {{ __('common.cancel') }}
                            </button>
                            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary/90 text-on-primary font-bold rounded-xl shadow-md transition-all">
                                {{ __('common.save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
        @endif
</x-app-layout>
