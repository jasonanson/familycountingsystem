<x-app-layout>
    <x-slot name="title">{{ __('auto.0622') }}</x-slot>

    <div x-data="{ showModal: false }" class="space-y-6">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-2xl">sync</span>
                    <span>{{ __('auto.0623') }}</span>
                </h1>
                <p class="text-sm text-on-surface-variant mt-1">{{ __('auto.0674') }}</p>
            </div>
            @if(auth()->user()->canEditCurrentFamily())
                <button @click="showModal = true" class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-primary hover:bg-primary/90 text-white font-bold text-sm shadow-md transition-all hover:scale-[1.02]">
                    <span class="material-symbols-outlined text-lg">add</span>
                    <span>{{ __('auto.0388') }}</span>
                </button>
            @else
                @include('partials.read-only-notice')
            @endif
        </div>

        <!-- Summary Stat Bento Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-surface-pure border border-border-base rounded-2xl p-5 shadow-sm border-l-4 border-l-primary flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-on-surface-variant">活躍訂閱預估月開銷</div>
                    <div class="text-2xl font-bold text-primary mt-1">NT$ {{ number_format($monthlyTotal) }} <span class="text-sm font-normal text-on-surface-variant">/ 月</span></div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-2xl">payments</span>
                </div>
            </div>

            <div class="bg-surface-pure border border-border-base rounded-2xl p-5 shadow-sm border-l-4 border-l-primary-container flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-on-surface-variant">預估年化總支出</div>
                    <div class="text-2xl font-bold text-on-surface mt-1">NT$ {{ number_format($monthlyTotal * 12) }} <span class="text-sm font-normal text-on-surface-variant">/ 年</span></div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-primary-container/10 flex items-center justify-center text-primary-container">
                    <span class="material-symbols-outlined text-2xl">calendar_month</span>
                </div>
            </div>

            <div class="bg-surface-pure border border-border-base rounded-2xl p-5 shadow-sm border-l-4 border-l-warning flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-on-surface-variant">目前追蹤服務數</div>
                    <div class="text-2xl font-bold text-on-surface mt-1">{{ count($subscriptions) }} 項服務</div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-warning/10 flex items-center justify-center text-warning">
                    <span class="material-symbols-outlined text-2xl">subscriptions</span>
                </div>
            </div>
        </div>

        <!-- Subscriptions Grid List -->
        <div class="space-y-3">
            <h2 class="text-base font-bold text-on-surface">{{ __('auto.0412') }}</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($subscriptions as $sub)
                    <div class="bg-surface-pure border border-border-base rounded-2xl p-5 shadow-sm flex flex-col justify-between space-y-4 transition-all hover:-translate-y-0.5 {{ $sub->is_paused ? 'opacity-60 bg-background-warm' : '' }}">
                        
                        <div class="flex items-start justify-between">
                            <div class="space-y-1">
                                <h3 class="font-bold text-lg text-on-surface flex items-center gap-2">
                                    <span>{{ $sub->name }}</span>
                                    @if($sub->is_paused)
                                        <span class="text-xs bg-border-base text-on-surface-variant px-2 py-0.5 rounded-full font-normal">{{ __('sub_page.paused') }}</span>
                                    @endif
                                </h3>
                                <div class="text-sm text-on-surface-variant">
                                    週期：{{ $sub->cycle === 'yearly' ? '每年' : '每月' }} · 帳戶：{{ $sub->account?->name ?? '未指定' }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-bold text-primary">NT$ {{ number_format($sub->amount) }}</div>
                                <div class="text-xs text-on-surface-variant">/ {{ $sub->cycle === 'yearly' ? '年' : '月' }}</div>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-border-base flex items-center justify-between text-sm">
                            <div class="text-on-surface-variant">
                                下一期扣款：<strong class="text-on-surface font-mono">{{ $sub->next_billing_date }}</strong>
                            </div>
                            @if(auth()->user()->canEditCurrentFamily())
                            <div class="flex items-center gap-1.5">
                                @if(!$sub->is_paused)
                                    <form action="{{ route('subscriptions.convert', $sub) }}" method="POST">
                                        @csrf
                                        <button type="submit" title="{{ __('auto.0666') }}" class="px-2.5 py-1 bg-primary/10 hover:bg-primary/20 text-primary font-bold rounded-lg border border-primary/20 text-xs">
                                            一鍵轉支出
                                        </button>
                                    </form>
                                @endif

                                <form action="{{ route('subscriptions.toggle', $sub) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-2 py-1 bg-background-warm hover:bg-border-base text-on-surface rounded-lg border border-border-base text-xs">
                                        {{ $sub->is_paused ? '恢復' : '暫停' }}
                                    </button>
                                </form>

                                <form action="{{ route('subscriptions.destroy', $sub) }}" method="POST" onsubmit="return confirm('確定刪除此訂閱？');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-danger hover:underline text-xs p-1">{{ __('common.delete') }}</button>
                                </form>
                            </div>
                            @endif
                        </div>

                    </div>
                @empty
                    <div class="col-span-full bg-surface-pure border border-border-base rounded-2xl py-12 text-center text-sm text-on-surface-variant shadow-sm">
                        目前尚未建立任何訂閱服務,請聯絡家庭家長或管理員協助建立。
                    </div>
                @endforelse
            </div>
        </div>

        @if(auth()->user()->canEditCurrentFamily())
        <!-- Create Subscription Modal -->
        <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen p-4 text-center">
                <div x-show="showModal" x-transition.opacity @click="showModal = false" class="fixed inset-0 bg-on-surface/40 backdrop-blur-sm"></div>

                <div x-show="showModal" x-transition 
                     class="inline-block w-full max-w-lg bg-surface-pure border border-border-base rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all z-10 p-6 space-y-4">
                    
                    <div class="flex items-center justify-between pb-3 border-b border-border-base">
                        <h3 class="font-bold text-lg text-on-surface flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">add_box</span>
                            <span>{{ __('auto.0389') }}</span>
                        </h3>
                        <button @click="showModal = false" class="text-on-surface-variant hover:text-on-surface">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>

                    <form action="{{ route('subscriptions.store') }}" method="POST" class="space-y-4">
                        @csrf

                        <div class="space-y-1">
                            <label class="block text-sm font-bold text-on-surface-variant">訂閱名稱 <span class="text-danger">*</span></label>
                            <input type="text" name="name" required placeholder="{{ __('auto.0126') }}"
                                   class="w-full bg-background-warm border border-border-base text-on-surface text-base rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-primary">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="block text-sm font-bold text-on-surface-variant">金額 (TWD) <span class="text-danger">*</span></label>
                                <input type="number" name="amount" required placeholder="0"
                                       class="w-full bg-background-warm border border-border-base text-on-surface text-base rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-primary">
                            </div>
                            <div class="space-y-1">
                                <label class="block text-sm font-bold text-on-surface-variant">{{ __('auto.0347') }}</label>
                                <select name="cycle" class="w-full bg-background-warm border border-border-base text-on-surface text-base rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-primary">
                                    <option value="monthly">{{ __('limit.cycle.monthly') }}</option>
                                    <option value="yearly">{{ __('cycle.yearly') }}</option>
                                    <option value="weekly">{{ __('limit.cycle.weekly') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-sm font-bold text-on-surface-variant">下一期扣款日期 <span class="text-danger">*</span></label>
                            <input type="date" name="next_billing_date" value="{{ now()->format('Y-m-d') }}" required
                                   class="w-full bg-background-warm border border-border-base text-on-surface text-base rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-primary">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="block text-sm font-bold text-on-surface-variant">{{ __('auto.0346') }}</label>
                                <select name="account_id" class="w-full bg-background-warm border border-border-base text-on-surface text-base rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-primary">
                                    <option value="">-- 未指定 --</option>
                                    @foreach($accounts as $acc)
                                        <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-sm font-bold text-on-surface-variant">{{ __('category_page.expense_cat') }}</label>
                                <select name="category_id" class="w-full bg-background-warm border border-border-base text-on-surface text-base rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-primary">
                                    <option value="">-- 未指定 --</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-border-base">
                            <button type="button" @click="showModal = false" class="px-4 py-2 text-sm font-bold text-on-surface-variant hover:bg-background-warm rounded-xl">{{ __('common.cancel') }}</button>
                            <button type="submit" class="px-5 py-2.5 text-sm font-bold bg-primary hover:bg-primary/90 text-white rounded-xl shadow-md">
                                建立訂閱
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif

    </div>
</x-app-layout>
