@extends('layouts.app')

@section('content')
<div x-data="customValuePromoter()" class="space-y-6">
    <!-- Header Title & Banner -->
    <div class="bg-surface-pure p-6 rounded-2xl shadow-sm border border-border-base space-y-3">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-black text-primary flex items-center gap-2">
                <span class="material-symbols-outlined text-3xl">settings_suggest</span>
                自訂值升格控制台
            </h1>
            <span class="px-3 py-1 bg-primary/10 text-primary text-xs font-bold rounded-full">{{ __('auto.0294') }}</span>
        </div>
        <p class="text-sm text-on-surface-variant leading-relaxed">
            成員於記帳時選擇「例外優先」並輸入自訂商家、類別或標籤提案時，系統會自動收集至此控制台。家長可審核內容並進行「一鍵升格」，將其標準化為全家共享之正式家庭分類或標籤。
        </p>
    </div>

    <!-- Section Tabs -->
    <div class="flex border-b border-border-base space-x-4">
        <button @click="activeTab = 'promotions'" :class="{ 'border-primary text-primary font-black': activeTab === 'promotions', 'border-transparent text-on-surface-variant hover:text-on-surface': activeTab !== 'promotions' }" class="pb-3 px-4 text-base border-b-2 font-bold transition-colors flex items-center gap-2 cursor-pointer">
            <span class="material-symbols-outlined">how_to_reg</span>
            <span>待審核與升格提案 ({{ $promotions->where('status', 'pending')->count() }} 筆待處理)</span>
        </button>
        <button @click="activeTab = 'payees'" :class="{ 'border-primary text-primary font-black': activeTab === 'payees', 'border-transparent text-on-surface-variant hover:text-on-surface': activeTab !== 'payees' }" class="pb-3 px-4 text-base border-b-2 font-bold transition-colors flex items-center gap-2 cursor-pointer">
            <span class="material-symbols-outlined">storefront</span>
            <span>記帳自訂商家統計 ({{ $transactionCustomPayees->count() }})</span>
        </button>
    </div>

    <!-- Tab 1: Custom Value Promotions -->
    <div x-show="activeTab === 'promotions'" class="space-y-4">
        <div class="bg-surface-pure rounded-2xl border border-border-base overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-on-surface">
                    <thead class="bg-surface-container-low text-xs uppercase tracking-wider text-on-surface-variant border-b border-border-base">
                        <tr>
                            <th class="px-6 py-4">{{ __('auto.0612') }}</th>
                            <th class="px-6 py-4">{{ __('auto.0361') }}</th>
                            <th class="px-6 py-4">{{ __('auto.0359') }}</th>
                            <th class="px-6 py-4">{{ __('auto.0544') }}</th>
                            <th class="px-6 py-4">{{ __('auto.0360') }}</th>
                            <th class="px-6 py-4 text-right">{{ __('auto.0297') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-base/60">
                        @forelse($promotions as $item)
                            <tr class="hover:bg-surface-container/50 transition-colors">
                                <td class="px-6 py-4 font-bold text-primary text-base">
                                    {{ $item->proposed_value }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($item->field_type === 'category')
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-success/10 text-success border border-success/50/20">{{ __('auto.0193') }}</span>
                                    @elseif($item->field_type === 'tag')
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-primary/10 text-primary border border-primary/30/20">{{ __('auto.0433') }}</span>
                                    @else
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-category-lavender/10 text-category-lavender border border-category-lavender/20">{{ __('auto.0318') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 font-semibold text-on-surface-variant">
                                    {{ $item->proposedBy?->name ?? '系統成員' }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($item->status === 'pending')
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-warning/10 text-warning border border-warning/50/20 animate-pulse">⏳ 待家長審核</span>
                                    @elseif($item->status === 'approved')
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-success/15 text-success border border-success/20">✅ 已升格正式</span>
                                    @else
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-danger/10 text-danger border border-danger/20">❌ 已駁回</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs font-mono text-on-surface-variant">
                                    {{ $item->created_at?->format('Y-m-d H:i') }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @if($item->status === 'pending')
                                        <div class="flex items-center justify-end gap-2">
                                            <button @click="openPromoteModal({{ json_encode($item) }})" type="button" class="px-3 py-1.5 bg-primary text-white text-xs font-bold rounded-xl hover:bg-primary/90 transition-colors shadow-sm cursor-pointer flex items-center gap-1">
                                                <span class="material-symbols-outlined text-sm">upgrade</span>
                                                <span>{{ __('auto.0080') }}</span>
                                            </button>
                                            <form action="{{ route('custom_values.reject', $item->id) }}" method="POST" onsubmit="return confirm('確定要駁回此提案嗎？');" class="inline">
                                                @csrf
                                                <button type="submit" class="px-3 py-1.5 bg-danger/10 text-danger text-xs font-bold rounded-xl hover:bg-danger/20 transition-colors border border-danger/20 cursor-pointer flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-sm">close</span>
                                                    <span>{{ __('auto.0743') }}</span>
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-xs text-on-surface-variant italic">{{ __('auto.0308') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-on-surface-variant">
                                    <span class="material-symbols-outlined text-4xl text-on-surface-variant/40 mb-1">done_all</span>
                                    <p class="text-sm font-bold">{{ __('auto.0538') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 2: Transaction Custom Payees -->
    <div x-show="activeTab === 'payees'" class="space-y-4" x-cloak>
        <div class="bg-surface-pure rounded-2xl border border-border-base overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-on-surface">
                    <thead class="bg-surface-container-low text-xs uppercase tracking-wider text-on-surface-variant border-b border-border-base">
                        <tr>
                            <th class="px-6 py-4">{{ __('auto.0611') }}</th>
                            <th class="px-6 py-4">{{ __('auto.0625') }}</th>
                            <th class="px-6 py-4">{{ __('auto.0405') }}</th>
                            <th class="px-6 py-4 text-right">{{ __('auto.0219') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-base/60">
                        @forelse($transactionCustomPayees as $payee)
                            <tr class="hover:bg-surface-container/50 transition-colors">
                                <td class="px-6 py-4 font-bold text-on-surface text-base flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary">store</span>
                                    <span>{{ $payee->payee_custom }}</span>
                                </td>
                                <td class="px-6 py-4 font-mono font-bold text-primary">
                                    {{ $payee->usage_count }} 次
                                </td>
                                <td class="px-6 py-4 text-xs font-mono text-on-surface-variant">
                                    {{ $payee->last_used_at }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button @click="openPayeePromoteModal('{{ $payee->payee_custom }}')" type="button" class="px-3.5 py-1.5 bg-primary/10 text-primary text-xs font-bold rounded-xl hover:bg-primary/20 transition-colors border border-primary/20 cursor-pointer inline-flex items-center gap-1">
                                        <span class="material-symbols-outlined text-sm">upgrade</span>
                                        <span>{{ __('auto.0221') }}</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-8 text-center text-on-surface-variant">
                                    <span class="material-symbols-outlined text-4xl text-on-surface-variant/40 mb-1">storefront</span>
                                    <p class="text-sm font-bold">{{ __('auto.0542') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Promote Modal -->
    <div x-show="isPromoteModalOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
         x-cloak>
        <div @click.outside="isPromoteModalOpen = false" class="bg-surface-pure w-full max-w-lg rounded-2xl shadow-xl border border-border-base overflow-hidden">
            <div class="px-6 py-4 bg-surface-container-low border-b border-border-base flex justify-between items-center">
                <h3 class="text-lg font-bold text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined">upgrade</span>
                    <span>{{ __('auto.0081') }}</span>
                </h3>
                <button @click="isPromoteModalOpen = false" type="button" class="text-on-surface-variant hover:text-on-surface">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form :action="promoteUrl" method="POST" class="p-6 space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-bold text-on-surface mb-1">升格目標類型 <span class="text-danger">*</span></label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center gap-2 p-3 rounded-xl border border-border-base cursor-pointer hover:bg-surface-container" :class="{ 'border-primary bg-primary/5': promoteData.target_type === 'category' }">
                            <input type="radio" name="target_type" value="category" x-model="promoteData.target_type" class="text-primary focus:ring-primary">
                            <div>
                                <div class="font-bold text-sm">家庭分類</div>
                                <div class="text-[11px] text-on-surface-variant">建立為可選的主/子分類</div>
                            </div>
                        </label>
                        <label class="flex items-center gap-2 p-3 rounded-xl border border-border-base cursor-pointer hover:bg-surface-container" :class="{ 'border-primary bg-primary/5': promoteData.target_type === 'tag' }">
                            <input type="radio" name="target_type" value="tag" x-model="promoteData.target_type" class="text-primary focus:ring-primary">
                            <div>
                                <div class="font-bold text-sm">正式標籤</div>
                                <div class="text-[11px] text-on-surface-variant">建立為全家可套用標籤</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Final Name -->
                <div>
                    <label class="block text-sm font-bold text-on-surface mb-1">升格名稱 <span class="text-danger">*</span></label>
                    <input type="text" name="name" x-model="promoteData.name" required class="w-full px-3.5 py-2.5 rounded-xl border border-border-base bg-surface-bright text-on-surface focus:outline-none focus:border-primary">
                </div>

                <!-- Category Specific Options -->
                <div x-show="promoteData.target_type === 'category'" class="space-y-4 pt-2 border-t border-border-base/50">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-on-surface mb-1">{{ __('auto.0376') }}</label>
                            <select name="category_type" x-model="promoteData.category_type" class="w-full px-3.5 py-2.5 rounded-xl border border-border-base bg-surface-bright text-on-surface focus:outline-none focus:border-primary">
                                <option value="expense">{{ __('auto.0368') }}</option>
                                <option value="income">{{ __('auto.0371') }}</option>
                                <option value="both">{{ __('auto.0716') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-on-surface mb-1">{{ __('category_page.parent') }}</label>
                            <select name="parent_id" x-model="promoteData.parent_id" class="w-full px-3.5 py-2.5 rounded-xl border border-border-base bg-surface-bright text-on-surface focus:outline-none focus:border-primary">
                                <option value="">-- 無 (獨立主分類) --</option>
                                @foreach($parentCategories as $parent)
                                    <option value="{{ $parent->id }}">📂 {{ $parent->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-on-surface mb-1">{{ __('auto.0248') }}</label>
                        <input type="text" name="icon" x-model="promoteData.icon" placeholder="label, store, local_offer" class="w-full px-3.5 py-2 rounded-xl border border-border-base bg-surface-bright text-on-surface text-sm">
                    </div>
                </div>

                <!-- Color Palette -->
                <div>
                    <label class="block text-sm font-bold text-on-surface mb-1">{{ __('auto.0096') }}</label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="color" x-model="promoteData.color" class="w-10 h-10 rounded-lg border border-border-base cursor-pointer">
                        <input type="text" x-model="promoteData.color" class="w-full px-3 py-2 rounded-xl border border-border-base bg-surface-bright text-on-surface text-xs font-mono">
                    </div>
                </div>

                <div class="pt-4 border-t border-border-base flex justify-end gap-3">
                    <button @click="isPromoteModalOpen = false" type="button" class="px-4 py-2.5 rounded-xl border border-border-base text-on-surface-variant font-bold hover:bg-surface-container transition-colors">{{ __('common.cancel') }}</button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-primary text-white font-bold hover:bg-primary/90 transition-colors shadow-md">
                        確認升格
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function customValuePromoter() {
    return {
        activeTab: 'promotions',
        isPromoteModalOpen: false,
        promoteUrl: '',
        promoteData: {
            target_type: 'category',
            name: '',
            category_type: 'expense',
            parent_id: '',
            icon: 'label',
            color: '#006b5f'
        },
        openPromoteModal(item) {
            this.promoteUrl = `/custom-values/${item.id}/promote`;
            this.promoteData = {
                target_type: item.field_type === 'tag' ? 'tag' : 'category',
                name: item.proposed_value,
                category_type: 'expense',
                parent_id: '',
                icon: 'label',
                color: '#006b5f'
            };
            this.isPromoteModalOpen = true;
        },
        openPayeePromoteModal(payeeName) {
            this.promoteUrl = `/custom-values/${encodeURIComponent(payeeName)}/promote`;
            this.promoteData = {
                target_type: 'tag',
                name: payeeName,
                category_type: 'expense',
                parent_id: '',
                icon: 'store',
                color: '#3B82F6'
            };
            this.isPromoteModalOpen = true;
        }
    }
}
</script>
@endsection
