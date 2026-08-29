<x-app-layout>
    <x-slot name="title">{{ __('auto.0681') }}</x-slot>

    <div x-data="{ 
        showCreateModal: false, 
        showEditModal: false,
        editBill: {
            id: null,
            name: '',
            amount: '',
            category_id: '',
            account_id: '',
            cycle: 'monthly',
            next_run_at: '',
            alert_days_before: 3,
            auto_create: false,
            note: ''
        },
        openEdit(bill) {
            this.editBill = {
                id: bill.id,
                name: bill.template?.name || '',
                amount: bill.template?.amount || '',
                category_id: bill.template?.category_id || '',
                account_id: bill.template?.account_id || '',
                cycle: bill.cycle || 'monthly',
                next_run_at: bill.next_run_at ? bill.next_run_at.substring(0, 10) : '',
                alert_days_before: (bill.alert_days_before && bill.alert_days_before[0]) ? bill.alert_days_before[0] : 3,
                auto_create: Boolean(bill.auto_create),
                note: bill.template?.note || ''
            };
            this.showEditModal = true;
        }
    }" class="space-y-6">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-2xl">receipt_long</span>
                    <span>{{ __('auto.0681') }}</span>
                </h1>
                <p class="text-sm text-on-surface-variant mt-1">{{ __('auto.0574') }}</p>
            </div>
            <button @click="showCreateModal = true" class="bg-primary hover:bg-primary-container text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-md transition-all hover:scale-[1.02] flex items-center gap-2 self-start sm:self-auto">
                <span class="material-symbols-outlined text-[20px]">add</span>
                <span>{{ __('auto.0382') }}</span>
            </button>
        </div>

        <!-- Bento Summary Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Estimated Monthly Total -->
            <div class="bg-surface-pure border border-border-base rounded-2xl p-5 shadow-sm border-l-4 border-l-primary flex items-center justify-between">
                <div>
                    <div class="text-xs font-bold uppercase text-on-surface-variant">本月預估固定支出</div>
                    <div class="text-2xl font-bold text-text-primary mt-1">NT$ {{ number_format($monthlyEstimated ?? 0) }}</div>
                    <p class="text-xs text-on-surface-variant mt-1">{{ __('auto.0235') }}</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-2xl">payments</span>
                </div>
            </div>

            <!-- Total Bills Tracked -->
            <div class="bg-surface-pure border border-border-base rounded-2xl p-5 shadow-sm border-l-4 border-l-category-sky flex items-center justify-between">
                <div>
                    <div class="text-xs font-bold uppercase text-on-surface-variant">目前追蹤帳單數</div>
                    <div class="text-2xl font-bold text-text-primary mt-1">{{ $totalBills ?? 0 }} <span class="text-sm font-normal text-on-surface-variant">項</span></div>
                    <p class="text-xs text-on-surface-variant mt-1">{{ __('auto.0168') }}</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-category-sky/10 flex items-center justify-center text-category-sky">
                    <span class="material-symbols-outlined text-2xl">autorenew</span>
                </div>
            </div>

            <!-- Upcoming / Alerts -->
            <div class="bg-surface-pure border border-border-base rounded-2xl p-5 shadow-sm border-l-4 {{ ($upcomingBills ?? collect())->count() > 0 ? 'border-l-warning' : 'border-l-success' }} flex items-center justify-between">
                <div>
                    <div class="text-xs font-bold uppercase text-on-surface-variant">7天內即將繳費</div>
                    <div class="text-2xl font-bold {{ ($upcomingBills ?? collect())->count() > 0 ? 'text-warning' : 'text-success' }} mt-1">
                        {{ ($upcomingBills ?? collect())->count() }} <span class="text-sm font-normal text-on-surface-variant">項</span>
                    </div>
                    <p class="text-xs text-on-surface-variant mt-1">
                        @if(($upcomingBills ?? collect())->count() > 0)
                            <span class="text-danger font-bold">⚠️ 請留意出款帳戶餘額</span>
                        @else
                            目前無即將扣款之急迫帳單
                        @endif
                    </p>
                </div>
                <div class="w-12 h-12 rounded-2xl {{ ($upcomingBills ?? collect())->count() > 0 ? 'bg-warning/10 text-warning' : 'bg-success/10 text-success' }} flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">notifications_active</span>
                </div>
            </div>
        </div>

        <!-- Bills Grid List -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-bold text-text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-xl">event_repeat</span>
                    <span>{{ __('auto.0244') }}</span>
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse(($recurringBills ?? []) as $bill)
                    @php
                        $template = $bill->template ?? [];
                        $amount = (float)($template['amount'] ?? 0);
                        $name = $template['name'] ?? '未命名帳單';
                        $catId = $template['category_id'] ?? null;
                        $category = ($categories ?? collect())->firstWhere('id', $catId);
                        $accId = $template['account_id'] ?? null;
                        $account = ($accounts ?? collect())->firstWhere('id', $accId);
                        
                        $diffDays = null;
                        $isUrgent = false;
                        if ($bill->next_run_at) {
                            $diffDays = \Carbon\Carbon::now()->diffInDays($bill->next_run_at, false);
                            $isUrgent = ($diffDays >= 0 && $diffDays <= 3);
                        }
                    @endphp
                    <div class="bg-surface-pure border border-border-base rounded-2xl p-5 shadow-sm hover:shadow-md transition-all flex flex-col justify-between space-y-4 relative overflow-hidden">
                        <!-- Top Ribbon Alert if Urgent -->
                        @if($isUrgent)
                            <div class="absolute top-0 left-0 right-0 h-1.5 bg-danger animate-pulse"></div>
                        @endif

                        <div class="space-y-3">
                            <!-- Card Header -->
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-3">
                                    <div class="w-11 h-11 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold text-lg">
                                        <span class="material-symbols-outlined text-xl">{{ \App\Support\IconHelper::name($category?->icon ?? null, 'receipt') }}</span>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-base text-text-primary">{{ $name }}</h3>
                                        <div class="text-xs text-on-surface-variant flex items-center gap-1.5 mt-0.5">
                                            <span>{{ $category?->name ?: '固定支出' }}</span>
                                            @if($account)
                                                <span>•</span>
                                                <span class="text-primary font-medium">{{ $account->name }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-primary">NT$ {{ number_format($amount) }}</div>
                                    <span class="text-[11px] px-2 py-0.5 rounded-full bg-surface-container font-semibold text-on-surface-variant">
                                        {{ $bill->cycle === 'yearly' ? '每年' : ($bill->cycle === 'quarterly' ? '每季' : '每月') }}
                                    </span>
                                </div>
                            </div>

                            <!-- Next Run & Status Indicator -->
                            <div class="p-3 bg-background-warm rounded-xl space-y-2 text-xs">
                                <div class="flex justify-between items-center">
                                    <span class="text-on-surface-variant">{{ __('auto.0089') }}</span>
                                    <strong class="font-mono text-text-primary text-sm">
                                        {{ $bill->next_run_at ? $bill->next_run_at->format('Y-m-d') : '未定' }}
                                    </strong>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-on-surface-variant">{{ __('auto.0203') }}</span>
                                    @if($diffDays !== null)
                                        @if($diffDays < 0)
                                            <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-danger/10 text-danger">⚠️ 已過期 {{ abs($diffDays) }} 天</span>
                                        @elseif($diffDays <= 3)
                                            <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-danger/10 text-danger border border-danger/30">⚠️ {{ $diffDays }} 天後到期 (即將扣款)</span>
                                        @elseif($diffDays <= 7)
                                            <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-warning/10 text-warning">{{ $diffDays }} 天後</span>
                                        @else
                                            <span class="text-on-surface-variant font-medium">{{ $diffDays }} 天後</span>
                                        @endif
                                    @else
                                        <span class="text-on-surface-variant">-</span>
                                    @endif
                                </div>
                                <div class="flex justify-between items-center pt-1 border-t border-border-base/60">
                                    <span class="text-on-surface-variant">{{ __('auto.0627') }}</span>
                                    @if($bill->auto_create)
                                        <span class="text-success font-bold flex items-center gap-0.5">
                                            <span class="material-symbols-outlined text-[14px]">auto_mode</span> 到期自動記帳
                                        </span>
                                    @else
                                        <span class="text-on-surface-variant flex items-center gap-0.5">
                                            <span class="material-symbols-outlined text-[14px]">touch_app</span> 手動確認
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Card Actions -->
                        <div class="pt-3 border-t border-border-base flex items-center justify-between gap-2">
                            <!-- Record Now Form -->
                            <form action="{{ route('recurring-bills.record-now', $bill) }}" method="POST" class="inline-block" onsubmit="return confirm('確定要立即為【{{ addslashes($name) }}】記錄一筆 NT$ {{ number_format($amount) }} 支出嗎？')">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 rounded-xl bg-primary/10 hover:bg-primary text-primary hover:text-white text-xs font-bold transition-all flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[16px]">bolt</span>
                                    <span>{{ __('auto.0560') }}</span>
                                </button>
                            </form>

                            <div class="flex items-center gap-1.5">
                                <button @click="openEdit({{ json_encode($bill) }})" class="p-1.5 rounded-lg border border-border-base hover:bg-surface-container text-on-surface-variant hover:text-text-primary text-xs transition-colors" title="{{ __('auto.0591') }}">
                                    <span class="material-symbols-outlined text-[18px]">edit</span>
                                </button>

                                <form action="{{ route('recurring-bills.destroy', $bill) }}" method="POST" class="inline-block" onsubmit="return confirm('確定要刪除固定帳單【{{ addslashes($name) }}】嗎？')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg border border-danger/20 text-danger hover:bg-danger/10 text-xs transition-colors" title="{{ __('auto.0200') }}">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full bg-surface-pure border border-border-base rounded-2xl py-16 text-center text-on-surface-variant shadow-sm space-y-3">
                        <span class="material-symbols-outlined text-4xl text-on-surface-variant/50">receipt_long</span>
                        <p class="text-sm font-medium">{{ __('auto.0528') }}</p>
                        <button @click="showCreateModal = true" class="px-4 py-2 bg-primary text-white text-xs font-bold rounded-xl shadow-sm hover:bg-primary-container">
                            + 立即新增第一筆固定帳單
                        </button>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Modal: Create Recurring Bill -->
        <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-on-surface/40 backdrop-blur-sm flex items-center justify-center p-4">
            <div @click.outside="showCreateModal = false" class="bg-surface-pure rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-border-base space-y-4">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-bold text-text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">add_card</span>
                        <span>{{ __('auto.0383') }}</span>
                    </h3>
                    <button @click="showCreateModal = false" class="text-on-surface-variant hover:text-text-primary">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <form action="{{ route('recurring-bills.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-text-primary uppercase mb-1">帳單名稱 <span class="text-danger">*</span></label>
                        <input type="text" name="name" required placeholder="{{ __('auto.0135') }}" class="w-full px-3.5 py-2.5 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-text-primary uppercase mb-1">預估金額 (NT$) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" required min="1" step="any" placeholder="{{ __('auto.0124') }}" class="w-full px-3.5 py-2.5 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-text-primary uppercase mb-1">扣款週期 <span class="text-danger">*</span></label>
                            <select name="cycle" class="w-full px-3.5 py-2.5 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                                <option value="monthly">{{ __('auto.0457') }}</option>
                                <option value="quarterly">{{ __('auto.0451') }}</option>
                                <option value="yearly">{{ __('auto.0452') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-text-primary uppercase mb-1">{{ __('category_page.expense_cat') }}</label>
                            <select name="category_id" class="w-full px-3.5 py-2.5 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                                <option value="">-- 選擇分類 --</option>
                                @foreach(($categories ?? []) as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-text-primary uppercase mb-1">{{ __('auto.0189') }}</label>
                            <select name="account_id" class="w-full px-3.5 py-2.5 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                                <option value="">-- 預設帳戶 --</option>
                                @foreach(($accounts ?? []) as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name }} (NT$ {{ number_format($acc->balance) }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-text-primary uppercase mb-1">下次/首次扣款日 <span class="text-danger">*</span></label>
                            <input type="date" name="next_run_at" required value="{{ date('Y-m-d') }}" class="w-full px-3.5 py-2.5 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-text-primary uppercase mb-1">{{ __('auto.0358') }}</label>
                            <select name="alert_days_before" class="w-full px-3.5 py-2.5 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                                <option value="3" selected>{{ __('auto.0355') }}</option>
                                <option value="5">{{ __('auto.0356') }}</option>
                                <option value="7">{{ __('auto.0357') }}</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-text-primary uppercase mb-1">{{ __('field.note_placeholder') }}</label>
                        <input type="text" name="note" placeholder="{{ __('auto.0134') }}" class="w-full px-3.5 py-2.5 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" name="auto_create" id="auto_create_new" value="1" class="rounded text-primary focus:ring-primary h-4 w-4">
                        <label for="auto_create_new" class="text-sm font-medium text-text-primary">{{ __('auto.0393') }}</label>
                    </div>

                    <div class="flex justify-end gap-3 pt-3 border-t border-border-base">
                        <button type="button" @click="showCreateModal = false" class="px-4 py-2 border border-border-base rounded-xl text-sm text-on-surface-variant hover:bg-surface-container">{{ __('common.cancel') }}</button>
                        <button type="submit" class="px-5 py-2 bg-primary text-white rounded-xl text-sm font-bold hover:bg-primary-container shadow-sm">{{ __('auto.0161') }}</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal: Edit Recurring Bill -->
        <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-on-surface/40 backdrop-blur-sm flex items-center justify-center p-4">
            <div @click.outside="showEditModal = false" class="bg-surface-pure rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-border-base space-y-4">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-bold text-text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">edit</span>
                        <span>{{ __('auto.0588') }}</span>
                    </h3>
                    <button @click="showEditModal = false" class="text-on-surface-variant hover:text-text-primary">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <form :action="'{{ url('recurring-bills') }}/' + editBill.id" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-bold text-text-primary uppercase mb-1">帳單名稱 <span class="text-danger">*</span></label>
                        <input type="text" name="name" x-model="editBill.name" required class="w-full px-3.5 py-2.5 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-text-primary uppercase mb-1">預估金額 (NT$) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" x-model="editBill.amount" required min="1" step="any" class="w-full px-3.5 py-2.5 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-text-primary uppercase mb-1">扣款週期 <span class="text-danger">*</span></label>
                            <select name="cycle" x-model="editBill.cycle" class="w-full px-3.5 py-2.5 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                                <option value="monthly">{{ __('auto.0457') }}</option>
                                <option value="quarterly">{{ __('auto.0451') }}</option>
                                <option value="yearly">{{ __('auto.0452') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-text-primary uppercase mb-1">{{ __('category_page.expense_cat') }}</label>
                            <select name="category_id" x-model="editBill.category_id" class="w-full px-3.5 py-2.5 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                                <option value="">-- 選擇分類 --</option>
                                @foreach(($categories ?? []) as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-text-primary uppercase mb-1">{{ __('auto.0189') }}</label>
                            <select name="account_id" x-model="editBill.account_id" class="w-full px-3.5 py-2.5 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                                <option value="">-- 預設帳戶 --</option>
                                @foreach(($accounts ?? []) as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-text-primary uppercase mb-1">下次扣款日 <span class="text-danger">*</span></label>
                            <input type="date" name="next_run_at" x-model="editBill.next_run_at" required class="w-full px-3.5 py-2.5 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-text-primary uppercase mb-1">{{ __('auto.0358') }}</label>
                            <select name="alert_days_before" x-model="editBill.alert_days_before" class="w-full px-3.5 py-2.5 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                                <option value="3">{{ __('auto.0354') }}</option>
                                <option value="5">{{ __('auto.0356') }}</option>
                                <option value="7">{{ __('auto.0357') }}</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-text-primary uppercase mb-1">{{ __('field.note_placeholder') }}</label>
                        <input type="text" name="note" x-model="editBill.note" class="w-full px-3.5 py-2.5 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" name="auto_create" id="auto_create_edit" value="1" x-model="editBill.auto_create" class="rounded text-primary focus:ring-primary h-4 w-4">
                        <label for="auto_create_edit" class="text-sm font-medium text-text-primary">{{ __('auto.0392') }}</label>
                    </div>

                    <div class="flex justify-end gap-3 pt-3 border-t border-border-base">
                        <button type="button" @click="showEditModal = false" class="px-4 py-2 border border-border-base rounded-xl text-sm text-on-surface-variant hover:bg-surface-container">{{ __('common.cancel') }}</button>
                        <button type="submit" class="px-5 py-2 bg-primary text-white rounded-xl text-sm font-bold hover:bg-primary-container shadow-sm">{{ __('auto.0401') }}</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
