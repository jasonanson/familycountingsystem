<x-app-layout>
    <x-slot name="title">{{ __('auto.0390') }}</x-slot>

    <div class="max-w-2xl mx-auto space-y-6" x-data="transactionCreateForm()">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}"
                   class="w-10 h-10 rounded-2xl bg-surface-pure border border-border-base text-on-surface-variant flex items-center justify-center hover:bg-surface-container hover:text-primary transition-colors shadow-sm"
                   title="{{ __('auto.0669') }}">
                    <span class="material-symbols-outlined text-xl">arrow_back</span>
                </a>
                <div>
                    <h1 class="text-2xl font-black text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-2xl">edit_note</span>
                        <span>{{ __('auto.0624') }}</span>
                    </h1>
                    <p class="text-xs text-on-surface-variant mt-0.5">{{ __('auto.0658') }}</p>
                </div>
            </div>
        </div>

        @if(session('error'))
            <div class="bg-danger/10 text-danger border border-danger/20 p-4 rounded-2xl text-sm font-bold flex items-center gap-2">
                <span class="material-symbols-outlined text-xl shrink-0">error</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if(isset($errors) && $errors->any())
            <div class="bg-danger/10 text-danger border border-danger/20 p-4 rounded-2xl text-sm font-medium space-y-1">
                <div class="font-bold flex items-center gap-2">
                    <span class="material-symbols-outlined text-xl shrink-0">warning</span>
                    <span>{{ __('auto.0639') }}</span>
                </div>
                <ul class="list-disc list-inside pl-2 text-xs">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Form Card --}}
        <div class="bg-surface-pure border border-border-base rounded-2xl shadow-sm p-6 sm:p-8 space-y-6">
            <form action="{{ route('transactions.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf

                {{-- 1. 交易類型切換 (支出 / 收入 / 轉帳) --}}
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">{{ __('auto.0105') }}</label>
                    <input type="hidden" name="type" x-model="type">
                    <div class="grid grid-cols-3 gap-2 p-1 bg-surface-container rounded-2xl border border-border-base">
                        <button type="button" @click="setType('expense')"
                                :class="type === 'expense' ? 'bg-danger text-white shadow-md font-bold' : 'text-on-surface-variant hover:text-on-surface font-medium'"
                                class="py-2.5 px-3 rounded-xl text-sm flex items-center justify-center gap-1.5 transition-all cursor-pointer">
                            <span class="material-symbols-outlined text-[18px]">shopping_cart</span>
                            <span>{{ __('child_page.withdraw') }}</span>
                        </button>
                        <button type="button" @click="setType('income')"
                                :class="type === 'income' ? 'bg-success text-white shadow-md font-bold' : 'text-on-surface-variant hover:text-on-surface font-medium'"
                                class="py-2.5 px-3 rounded-xl text-sm flex items-center justify-center gap-1.5 transition-all cursor-pointer">
                            <span class="material-symbols-outlined text-[18px]">savings</span>
                            <span>{{ __('direction.in') }}</span>
                        </button>
                        <button type="button" @click="setType('transfer')"
                                :class="type === 'transfer' ? 'bg-primary text-white shadow-md font-bold' : 'text-on-surface-variant hover:text-on-surface font-medium'"
                                class="py-2.5 px-3 rounded-xl text-sm flex items-center justify-center gap-1.5 transition-all cursor-pointer">
                            <span class="material-symbols-outlined text-[18px]">swap_horiz</span>
                            <span>{{ __('account_page.transfer') }}</span>
                        </button>
                    </div>
                </div>

                {{-- 2. 金額輸入與快捷標籤 --}}
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                        金額 (NT$) <span class="text-danger">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant font-bold text-base pointer-events-none">NT$</span>
                        <input type="number" step="any" name="amount" required x-model.number="amount"
                               class="w-full pl-14 pr-4 py-3 bg-background-warm border border-border-base rounded-xl text-2xl font-black text-on-surface focus:outline-none focus:border-primary shadow-sm"
                               placeholder="0">
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click="addAmount(50)" class="px-3 py-1.5 text-xs font-bold rounded-full bg-surface-container hover:bg-surface-variant text-on-surface border border-border-base">+ NT$ 50</button>
                        <button type="button" @click="addAmount(100)" class="px-3 py-1.5 text-xs font-bold rounded-full bg-surface-container hover:bg-surface-variant text-on-surface border border-border-base">+ NT$ 100</button>
                        <button type="button" @click="addAmount(200)" class="px-3 py-1.5 text-xs font-bold rounded-full bg-surface-container hover:bg-surface-variant text-on-surface border border-border-base">+ NT$ 200</button>
                        <button type="button" @click="addAmount(500)" class="px-3 py-1.5 text-xs font-bold rounded-full bg-surface-container hover:bg-surface-variant text-on-surface border border-border-base">+ NT$ 500</button>
                        <button type="button" @click="amount = 0" class="px-3 py-1.5 text-xs font-bold rounded-full bg-surface-container hover:bg-surface-variant text-on-surface-variant border border-border-base">清除</button>
                    </div>
                </div>

                {{-- 3. 記帳日期與時間 --}}
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">{{ __('auto.0396') }}</label>
                    <input type="datetime-local" name="occurred_at" value="{{ old('occurred_at', isset($transaction) ? $transaction->occurred_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}"
                           class="w-full bg-surface-pure border border-border-base rounded-xl px-4 py-3 text-sm font-medium text-on-surface focus:outline-none focus:border-primary shadow-sm cursor-pointer">
                </div>

                {{-- 4. 消費項目分類 --}}
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">{{ __('auto.0192') }}</label>
                    <select name="category_id" class="w-full bg-surface-pure border border-border-base rounded-xl px-4 py-3 text-sm font-semibold text-on-surface focus:outline-none focus:border-primary shadow-sm cursor-pointer">
                        <option value="">-- 請選擇分類 --</option>
                        <template x-if="type === 'expense' || type === 'transfer'">
                            <optgroup label="支出分類">
                                @foreach($expenseCategories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id', isset($transaction) ? $transaction->category_id : '') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                    @foreach($cat->children ?? [] as $sub)
                                        <option value="{{ $sub->id }}" {{ old('category_id', isset($transaction) ? $transaction->category_id : '') == $sub->id ? 'selected' : '' }}>&nbsp;&nbsp;↳ {{ $sub->name }}</option>
                                    @endforeach
                                @endforeach
                            </optgroup>
                        </template>
                        <template x-if="type === 'income'">
                            <optgroup label="收入分類">
                                @foreach($incomeCategories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id', isset($transaction) ? $transaction->category_id : '') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                    @foreach($cat->children ?? [] as $sub)
                                        <option value="{{ $sub->id }}" {{ old('category_id', isset($transaction) ? $transaction->category_id : '') == $sub->id ? 'selected' : '' }}>&nbsp;&nbsp;↳ {{ $sub->name }}</option>
                                    @endforeach
                                @endforeach
                            </optgroup>
                        </template>
                    </select>
                    <input type="text" name="category_id_custom" x-show="false" placeholder="自訂分類" class="hidden">
                </div>

                {{-- 5. 扣款 / 入帳帳戶 --}}
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                        <span x-text="type === 'income' ? '存入帳戶 / 零用錢包' : '扣款帳戶 / 錢包'"></span>
                    </label>
                    <select name="account_id" class="w-full bg-surface-pure border border-border-base rounded-xl px-4 py-3 text-sm font-semibold text-on-surface focus:outline-none focus:border-primary shadow-sm cursor-pointer">
                        <option value="">-- 請選擇帳戶 (預設錢包) --</option>
                        @forelse($accounts as $account)
                            <option value="{{ $account->id }}" {{ old('account_id', isset($transaction) ? $transaction->account_id : '') == $account->id ? 'selected' : '' }}>{{ $account->name }} (餘額 NT$ {{ number_format($account->balance) }})</option>
                        @empty
                            <option value="" disabled>{{ __('auto.0304') }}</option>
                        @endforelse
                    </select>
                </div>

                {{-- 6. 代墊者 / 消費對象 --}}
                @php
                    $payeeSelected = old('payee_user_id', isset($transaction) ? ($transaction->payee_user_id ?? ($transaction->payee_custom ? 'custom' : '')) : '');
                    $payeeCustomValue = old('payee_custom', isset($transaction) ? ($transaction->payee_custom ?? '') : '');
                @endphp
                <x-select-with-other
                    name="payee_user_id"
                    customName="payee_custom"
                    label="代墊者 / 消費對象"
                    :options="($familyMembers ?? collect())->pluck('name', 'id')->toArray()"
                    :selected="$payeeSelected"
                    :customValue="$payeeCustomValue"
                    customPlaceholder="請輸入外人或店家名稱" />

                {{-- 7. 分帳/拆帳 (僅支出類型) --}}
                <div x-show="type === 'expense'" x-cloak class="p-4 bg-background-warm rounded-2xl border border-border-base space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer font-bold text-sm text-text-primary">
                            <input type="checkbox" name="is_split" value="1" x-model="isSplit" class="rounded text-primary focus:ring-primary h-4 w-4">
                            <span>👥 由多人分攤此筆支出 (分帳/拆帳)</span>
                        </label>
                    </div>

                    <div x-show="isSplit" x-cloak class="space-y-3 pt-2 border-t border-border-base">
                        <div class="flex gap-1.5 bg-surface-pure p-1 rounded-xl border border-border-base text-xs font-bold">
                            <button type="button" @click="splitMode = 'equal'" :class="splitMode === 'equal' ? 'bg-primary text-white shadow-sm' : 'text-on-surface-variant hover:bg-surface-container'" class="flex-1 py-1.5 rounded-lg transition-all">
                                均等分攤 (Equal)
                            </button>
                            <button type="button" @click="splitMode = 'amount'" :class="splitMode === 'amount' ? 'bg-primary text-white shadow-sm' : 'text-on-surface-variant hover:bg-surface-container'" class="flex-1 py-1.5 rounded-lg transition-all">
                                自訂金額 (Amount)
                            </button>
                            <button type="button" @click="splitMode = 'ratio'" :class="splitMode === 'ratio' ? 'bg-primary text-white shadow-sm' : 'text-on-surface-variant hover:bg-surface-container'" class="flex-1 py-1.5 rounded-lg transition-all">
                                自訂比例 (Ratio)
                            </button>
                        </div>
                        <input type="hidden" name="split_mode" :value="splitMode">

                        <div class="space-y-2">
                            <label class="block text-[11px] font-bold uppercase text-on-surface-variant">{{ __('auto.0692') }}</label>
                            @foreach(($familyMembers ?? []) as $mem)
                                <div class="flex items-center justify-between p-2 rounded-xl bg-surface-pure border border-border-base text-xs">
                                    <label class="flex items-center gap-2 cursor-pointer font-medium text-text-primary">
                                        <input type="checkbox" name="split_members[]" value="{{ $mem->id }}"
                                               :checked="selectedMembers.includes({{ $mem->id }})"
                                               @change="toggleMember({{ $mem->id }})"
                                               class="rounded text-primary focus:ring-primary h-4 w-4">
                                        <span>{{ $mem->name }}</span>
                                    </label>

                                    <div x-show="splitMode === 'equal'" class="text-right font-bold text-primary">
                                        <span x-show="selectedMembers.includes({{ $mem->id }})">
                                            NT$ <span x-text="amount > 0 && selectedMembers.length > 0 ? Math.round(amount / selectedMembers.length) : 0"></span>
                                        </span>
                                        <span x-show="!selectedMembers.includes({{ $mem->id }})" class="text-on-surface-variant/50 font-normal">不分攤</span>
                                    </div>

                                    <div x-show="splitMode === 'amount'" class="flex items-center gap-1">
                                        <span class="text-xs text-on-surface-variant">NT$</span>
                                        <input type="number" step="any" min="0"
                                               name="split_amounts[{{ $mem->id }}]"
                                               :disabled="!selectedMembers.includes({{ $mem->id }})"
                                               placeholder="0"
                                               class="w-20 px-2 py-1 border border-border-base rounded-lg text-right text-xs focus:outline-none focus:border-primary disabled:opacity-40">
                                    </div>

                                    <div x-show="splitMode === 'ratio'" class="flex items-center gap-1">
                                        <input type="number" step="any" min="0" max="100"
                                               name="split_ratios[{{ $mem->id }}]"
                                               :disabled="!selectedMembers.includes({{ $mem->id }})"
                                               placeholder="0"
                                               class="w-16 px-2 py-1 border border-border-base rounded-lg text-right text-xs focus:outline-none focus:border-primary disabled:opacity-40">
                                        <span class="text-xs text-on-surface-variant">%</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- 8. 附件上傳 --}}
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">{{ __('auto.0518') }}</label>
                    <input type="file" name="attachment" accept="image/*,.pdf"
                           class="w-full text-xs text-on-surface-variant file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                </div>

                {{-- 9. 備註/描述 --}}
                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase text-on-surface">{{ __('auto.0152') }}</label>
                    <input type="text" name="description" x-model="description" placeholder="{{ __('auto.0129') }}"
                           class="w-full bg-background-warm border border-border-base text-sm rounded-xl px-3.5 py-2 focus:outline-none focus:border-primary">
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border-base">
                    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}"
                       class="px-5 py-2.5 rounded-xl border border-border-base text-sm font-bold text-on-surface-variant hover:bg-surface-container transition-colors">
                        取消
                    </a>
                    <button type="submit"
                            class="px-6 py-2.5 rounded-xl text-sm font-bold text-white shadow-md transition-all active:scale-95 cursor-pointer flex items-center gap-1.5"
                            :class="type === 'expense' ? 'bg-danger hover:bg-danger/90' : (type === 'income' ? 'bg-success hover:bg-success/90' : 'bg-primary hover:bg-primary/90')">
                        <span class="material-symbols-outlined text-[18px]">{{ isset($transaction) ? 'save' : 'check' }}</span>
                        <span>{{ isset($transaction) ? '儲存修改' : __('auto.0162') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        window.transactionCreateForm = function transactionCreateForm() {
            const editTx = @json(isset($transaction) ? $transaction : null);
            const splitModeInitial = editTx && editTx.split_with ? (editTx.split_with.mode || 'equal') : 'equal';
            const isSplitInitial = !!(editTx && editTx.split_with);
            const selectedMembersInitial = editTx && editTx.split_with && Array.isArray(editTx.split_with.members)
                ? editTx.split_with.members.map(m => m.user_id)
                : @json(($familyMembers ?? collect())->pluck('id')->toArray());

            // 預先算好初值給 Alpine — 不要在 Blade 的 JSON directive 內引用 JS 變數,
            // 不然 Blade 會把 editTx 當 PHP 常數解析而炸 Undefined constant
            const typeInitial = @json(old('type', isset($transaction) ? $transaction->type : ($defaultType ?? request('type', 'expense'))));
            const amountInitial = @json(old('amount', isset($transaction) ? $transaction->amount : null));
            const descriptionInitial = @json(old('description', isset($transaction) ? ($transaction->description ?: null) : null));

            return {
                type: typeInitial,
                amount: amountInitial !== null ? amountInitial : '',
                description: descriptionInitial !== null ? descriptionInitial : '',
                // Split Expense state
                isSplit: isSplitInitial,
                splitMode: splitModeInitial,
                selectedMembers: selectedMembersInitial,
                customAmounts: {},
                customRatios: {},
                toggleMember(id) {
                    if (this.selectedMembers.includes(id)) {
                        if (this.selectedMembers.length > 1) {
                            this.selectedMembers = this.selectedMembers.filter(m => m !== id);
                        }
                    } else {
                        this.selectedMembers.push(id);
                    }
                },
                setType(t) {
                    this.type = t;
                },
                addAmount(val) {
                    const current = parseInt(this.amount) || 0;
                    this.amount = current + val;
                }
            };
        }
    </script>
</x-app-layout>
