<x-app-layout>
    <x-slot name="title">{{ __('tx_page.title_full') }}</x-slot>

    <div x-data="{
        showViewer: false,
        activeTx: null,
        showSplitModal: false,
        activeSplit: null,
        openSplitDetail(splitData) {
            this.activeSplit = splitData;
            this.showSplitModal = true;
        }
    }" class="space-y-6">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-text-primary flex items-center gap-2">
                    <span>📝 帳務明細與 CSV 報表</span>
                </h1>
                <p class="text-sm text-on-surface-variant mt-1">{{ __('auto.0430') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('export.csv') }}" class="flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-surface-pure hover:bg-surface-container text-primary font-bold text-sm border border-primary/30 shadow-sm transition-all">
                    <span class="material-symbols-outlined text-[18px]">download</span>
                    <span>{{ __('auto.0215') }}</span>
                </a>
                @if(auth()->user()->canEditCurrentFamily())
                    <a href="{{ route('transactions.create') }}" class="flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-primary hover:bg-primary/90 text-on-primary font-bold text-sm shadow-md transition-all">
                        <span class="material-symbols-outlined text-[18px]">add_circle</span>
                        <span>{{ __('auto.0390') }}</span>
                    </a>
                @else
                    @include('partials.read-only-notice')
                @endif
                
                <a href="{{ route('transactions.calendar') }}" class="flex items-center gap-1.5 px-4 py-2.5 bg-primary/10 text-primary hover:bg-primary/20 rounded-xl text-sm font-bold transition-all border border-primary/30">
                    <span class="material-symbols-outlined text-base">calendar_month</span>
                    <span>{{ __('auto.0197') }}</span>
                </a>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <form method="GET" action="{{ route('transactions.index') }}" class="bg-surface-pure border border-border-base rounded-xl p-4 shadow-[0_4px_12px_rgba(28,25,23,0.05)] flex flex-col md:flex-row gap-3 items-center justify-between">
            <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                <a href="{{ route('transactions.index', array_merge(request()->query(), ['type' => 'all'])) }}"
                   class="px-3 py-1.5 rounded-lg text-sm font-bold transition-colors {{ $typeFilter === 'all' ? 'bg-primary text-on-primary' : 'bg-surface-container text-on-surface-variant hover:bg-surface-variant' }}">
                    全部
                </a>
                <a href="{{ route('transactions.index', array_merge(request()->query(), ['type' => 'expense'])) }}"
                   class="px-3 py-1.5 rounded-lg text-sm font-bold transition-colors {{ $typeFilter === 'expense' ? 'bg-danger text-white' : 'bg-surface-container text-on-surface-variant hover:bg-surface-variant' }}">
                    💸 支出
                </a>
                <a href="{{ route('transactions.index', array_merge(request()->query(), ['type' => 'income'])) }}"
                   class="px-3 py-1.5 rounded-lg text-sm font-bold transition-colors {{ $typeFilter === 'income' ? 'bg-success text-white' : 'bg-surface-container text-on-surface-variant hover:bg-surface-variant' }}">
                    💰 收入
                </a>
            </div>

            <div class="flex flex-wrap md:flex-nowrap items-center gap-2 w-full md:w-auto">
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="bg-background-warm border border-border-base text-sm rounded-xl px-3 py-1.5 text-on-surface focus:outline-none focus:border-primary">
                <span class="text-sm text-on-surface-variant">~</span>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="bg-background-warm border border-border-base text-sm rounded-xl px-3 py-1.5 text-on-surface focus:outline-none focus:border-primary">
                
                <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('auto.0365') }}" 
                       class="bg-background-warm border border-border-base text-sm rounded-xl px-3 py-1.5 text-on-surface focus:outline-none focus:border-primary flex-1 md:w-48">
                
                <button type="submit" class="px-3.5 py-1.5 bg-surface-container hover:bg-surface-variant text-sm font-bold rounded-xl text-on-surface">
                    🔍 搜尋
                </button>
            </div>
        </form>

        <!-- Transactions Table -->
        <div class="bg-surface-pure border border-border-base rounded-xl overflow-hidden shadow-[0_4px_12px_rgba(28,25,23,0.05)]">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-on-surface">
                    <thead class="bg-surface-container text-on-surface-variant font-bold border-b border-border-base">
                        <tr>
                            <th class="px-4 py-3">{{ __('field.type') }}</th>
                            <th class="px-4 py-3">{{ __('auto.0192') }}</th>
                            <th class="px-4 py-3">{{ __('auto.0705') }}</th>
                            <th class="px-4 py-3">{{ __('auto.0190') }}</th>
                            <th class="px-4 py-3">{{ __('account_page.title') }}</th>
                            <th class="px-4 py-3">{{ __('auto.0519') }}</th>
                            <th class="px-4 py-3">{{ __('auto.0628') }}</th>
                            <th class="px-4 py-3">{{ __('auto.0395') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('tx_page.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-base">
                        @forelse($transactions as $tx)
                            <tr class="hover:bg-surface-container/30 transition-colors">
                                <td class="px-4 py-3 font-bold">
                                    <span class="px-2 py-0.5 rounded-full text-xs {{ $tx->type === 'expense' ? 'bg-danger/10 text-danger border border-danger/20' : ($tx->type === 'income' ? 'bg-success/10 text-success border border-success/20' : 'bg-primary/10 text-primary border border-primary/20') }}">
                                        {{ $tx->type === 'expense' ? '支出' : ($tx->type === 'income' ? '收入' : '轉帳') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-bold text-on-surface">
                                        {{ $tx->category?->name ?? ($tx->type_custom ?: '未分類自訂') }}
                                    </div>
                                    @if($tx->description)
                                        <div class="text-xs text-on-surface-variant">{{ $tx->description }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-bold text-base {{ $tx->type === 'expense' ? 'text-danger' : 'text-success' }}">
                                    {{ $tx->type === 'expense' ? '-' : '+' }} NT$ {{ number_format($tx->amount) }}
                                </td>
                                <td class="px-4 py-3">
                                    @if(!empty($tx->split_with) && isset($tx->split_with['members']))
                                        <button type="button" @click="openSplitDetail({{ json_encode($tx->split_with) }})" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-primary/10 hover:bg-primary/20 text-primary font-bold text-xs border border-primary/20 transition-colors" title="{{ __('auto.0427') }}">
                                            <span class="material-symbols-outlined text-[14px]">call_split</span>
                                            <span>{{ count($tx->split_with['members']) }}人分攤</span>
                                        </button>
                                    @else
                                        <span class="text-xs text-on-surface-variant">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-on-surface-variant">
                                    {{ $tx->account?->name ?? '無' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if(isset($tx->custom_fields['attachment']))
                                        <button type="button" @click="activeTx = {
                                            attachmentUrl: '{{ url($tx->custom_fields['attachment']) }}',
                                            type: '{{ $tx->type }}',
                                            amount: '{{ $tx->amount }}',
                                            category: '{{ addslashes($tx->category?->name ?? ($tx->type_custom ?: '未分類')) }}',
                                            account: '{{ addslashes($tx->account?->name ?? '無') }}',
                                            occurred_at: '{{ $tx->occurred_at->format('Y-m-d H:i') }}',
                                            user: '{{ addslashes($tx->user?->name ?? '') }}',
                                            payee: '{{ addslashes($tx->payee_custom ?? '') }}',
                                            description: '{{ addslashes($tx->description ?? '') }}'
                                        }; showViewer = true;" class="inline-flex items-center gap-1 text-xs text-primary font-bold hover:underline bg-primary/10 px-2.5 py-1 rounded-lg border border-primary/20 hover:bg-primary/20 transition-all">
                                            <span class="material-symbols-outlined text-[16px]">receipt_long</span>
                                            <span>{{ __('auto.0438') }}</span>
                                        </button>
                                    @else
                                        <span class="text-xs text-on-surface-variant">{{ __('auto.0479') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-xs font-bold text-on-surface">{{ $tx->user->name ?? '系統' }}</div>
                                    @if($tx->payeeUser)
                                        <div class="text-[11px] text-on-surface-variant">➡️ {{ $tx->payeeUser->name }}</div>
                                    @elseif($tx->payee_custom)
                                        <div class="text-[11px] text-on-surface-variant">➡️ {{ $tx->payee_custom }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-on-surface-variant whitespace-nowrap">
                                    {{ $tx->occurred_at ? $tx->occurred_at->format('Y-m-d H:i') : '-' }}
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    @if(auth()->user()->canEditCurrentFamily())
                                        <div class="inline-flex items-center gap-1">
                                            <a href="{{ route('transactions.edit', $tx) }}" class="p-1 rounded text-primary hover:bg-primary/10 transition-colors" title="編輯這筆交易">
                                                <span class="material-symbols-outlined text-[18px]">edit</span>
                                            </a>
                                            <form action="{{ route('transactions.destroy', $tx) }}" method="POST" class="inline" onsubmit="return confirm('確定要刪除這筆交易紀錄嗎？')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1 rounded text-danger hover:bg-danger/10 transition-colors" title="{{ __('common.delete') }}">
                                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-on-surface-variant text-sm">
                                    查無相關交易紀錄。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($transactions->hasPages())
                <div class="p-4 border-t border-border-base bg-surface-container-low">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>


        <!-- Split Expense Detail Modal -->
        <div x-show="showSplitModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-on-surface/40 backdrop-blur-sm flex items-center justify-center p-4">
            <div @click.outside="showSplitModal = false" class="bg-surface-pure rounded-3xl max-w-md w-full p-6 shadow-2xl border border-border-base space-y-4">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-bold text-text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">call_split</span>
                        <span>{{ __('auto.0254') }}</span>
                    </h3>
                    <button @click="showSplitModal = false" class="text-on-surface-variant hover:text-text-primary">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <template x-if="activeSplit">
                    <div class="space-y-3">
                        <div class="p-3 bg-background-warm rounded-2xl flex justify-between items-center text-xs">
                            <div>
                                <span class="text-on-surface-variant">{{ __('auto.0597') }}</span>
                                <strong class="text-base text-text-primary">NT$ <span x-text="Number(activeSplit.total_amount || 0).toLocaleString()"></span></strong>
                            </div>
                            <span class="px-2.5 py-1 rounded-full bg-primary/10 text-primary font-bold text-xs" x-text="activeSplit.mode === 'equal' ? '均等分攤' : (activeSplit.mode === 'amount' ? '自訂金額' : '自訂比例')"></span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase text-on-surface-variant mb-2">{{ __('auto.0231') }}</label>
                            <div class="space-y-2">
                                <template x-for="mem in (activeSplit.members || [])" :key="mem.user_id">
                                    <div class="flex items-center justify-between p-2.5 rounded-xl border border-border-base bg-surface-pure text-xs">
                                        <div class="flex items-center gap-2">
                                            <div class="w-7 h-7 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center font-bold text-xs">
                                                <span x-text="(mem.name || '').substring(0, 1)"></span>
                                            </div>
                                            <span class="font-bold text-text-primary" x-text="mem.name"></span>
                                        </div>
                                        <div class="text-right">
                                            <div class="font-bold text-primary">NT$ <span x-text="Number(mem.amount || 0).toLocaleString()"></span></div>
                                            <div class="text-[10px] text-on-surface-variant" x-text="mem.ratio + '%'"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

                <div class="flex justify-end pt-3 border-t border-border-base">
                    <button type="button" @click="showSplitModal = false" class="px-5 py-2 bg-surface-container-high hover:bg-surface-container text-text-primary rounded-xl text-sm font-bold">{{ __('common.close') }}</button>
                </div>
            </div>
        </div>

        <!-- Attachment Viewer Modal -->
        @include('attachments.viewer')

    </div>
</x-app-layout>
