@extends('layouts.app')

@section('content')
<div x-data="accountManager()" class="space-y-8 font-['Microsoft_JhengHei']">
    
    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-[#FFFFFF] p-6 rounded-2xl border border-[#E7E5E4] shadow-sm">
        <div>
            <div class="flex items-center gap-2 text-[#006b5f]">
                <span class="material-symbols-outlined text-3xl">account_balance</span>
                <h1 class="text-2xl font-bold tracking-tight text-on-surface">{{ __('account_page.management') }}</h1>
            </div>
            <p class="text-xs text-on-surface-variant mt-1">{{ __('auto.0170') }}</p>
        </div>

        <div class="flex items-center gap-3">
            @if(auth()->user()->canEditCurrentFamily())
            <!-- Transfer Button -->
            <button @click="openTransferModal()" 
                    type="button"
                    class="px-4 py-2.5 rounded-xl border border-[#E7E5E4] bg-[#FFFFFF] hover:bg-[#FAFAF9] text-[#006b5f] font-bold text-sm flex items-center gap-2 shadow-sm transition-all cursor-pointer hover:border-[#006b5f]/40">
                <span class="material-symbols-outlined text-xl">swap_horiz</span>
                <span>{{ __('auto.0166') }}</span>
            </button>

            <!-- Add Account Button -->
            <button @click="openCreateAccountModal()" 
                    type="button"
                    class="px-4 py-2.5 rounded-xl bg-[#006b5f] hover:bg-[#00574d] text-white font-bold text-sm flex items-center gap-2 shadow-md hover:shadow-lg transition-all cursor-pointer">
                <span class="material-symbols-outlined text-xl">add</span>
                <span>{{ __('account_page.new') }}</span>
            </button>
        </div>
            @else
                @include('partials.read-only-notice')
            @endif
        </div>
    </div>

    <!-- 1. 頁面頂部資產總覽卡片 (Asset Overview Cards) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <!-- 當前家庭總資產 (Total Assets) -->
        <div class="bg-[#FFFFFF] border border-[#E7E5E4] rounded-2xl p-5 shadow-sm relative overflow-hidden flex flex-col justify-between hover:border-[#006b5f]/30 transition-all">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-xl bg-[#006b5f]/10 text-[#006b5f] flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl">account_balance_wallet</span>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider block">{{ __('auto.0496') }}</span>
                        <span class="text-[11px] text-on-surface-variant/70">{{ __('auto.0344') }}</span>
                    </div>
                </div>
                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#006b5f]/10 text-[#006b5f] border border-[#006b5f]/20">
                    {{ $accounts->where('balance', '>', 0)->count() }} 個帳戶
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl lg:text-3xl font-black text-[#006b5f] tracking-tight">
                    NT$ {{ number_format($totalAssets, 2) }}
                </div>
            </div>
        </div>

        <!-- 總負債 (Total Liabilities) -->
        <div class="bg-[#FFFFFF] border border-[#E7E5E4] rounded-2xl p-5 shadow-sm relative overflow-hidden flex flex-col justify-between hover:border-danger/30 transition-all">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-xl bg-danger/10 text-danger flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl">credit_card_off</span>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider block">{{ __('auto.0598') }}</span>
                        <span class="text-[11px] text-on-surface-variant/70">{{ __('auto.0142') }}</span>
                    </div>
                </div>
                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-danger/10 text-danger border border-danger/20">
                    {{ $accounts->where('balance', '<', 0)->count() }} 筆應付
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl lg:text-3xl font-black text-danger tracking-tight">
                    NT$ {{ number_format($totalLiabilities, 2) }}
                </div>
            </div>
        </div>

        <!-- 淨資產 (Net Assets) -->
        <div class="bg-[#FFFFFF] border border-[#E7E5E4] rounded-2xl p-5 shadow-sm relative overflow-hidden flex flex-col justify-between hover:border-[#006b5f]/40 transition-all">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-xl bg-success/10 text-success flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl">savings</span>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider block">{{ __('auto.0468') }}</span>
                        <span class="text-[11px] text-on-surface-variant/70">{{ __('auto.0600') }}</span>
                    </div>
                </div>
                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold {{ $netAssets >= 0 ? 'bg-success/10 text-success border border-success/20' : 'bg-danger/10 text-danger border border-danger/20' }}">
                    {{ $netAssets >= 0 ? '財務健康' : '負債過高' }}
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl lg:text-3xl font-black {{ $netAssets >= 0 ? 'text-on-surface' : 'text-danger' }} tracking-tight">
                    NT$ {{ number_format($netAssets, 2) }}
                </div>
            </div>
        </div>
    </div>

    <!-- 2. 帳戶列表網格 (Account Grid) -->
    <div class="space-y-4">
        <div class="flex items-center justify-between px-1">
            <h2 class="text-lg font-bold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-xl text-[#006b5f]">grid_view</span>
                <span>{{ __('auto.0320') }}</span>
                <span class="text-xs font-semibold text-on-surface-variant bg-[#E7E5E4]/60 px-2 py-0.5 rounded-full">
                    {{ $accounts->count() }}
                </span>
            </h2>
        </div>

        @if($accounts->isEmpty())
            <div class="bg-[#FFFFFF] border border-[#E7E5E4] rounded-2xl p-12 text-center space-y-3 shadow-sm">
                <div class="w-16 h-16 rounded-full bg-[#FAFAF9] border border-[#E7E5E4] flex items-center justify-center mx-auto text-on-surface-variant/50">
                    <span class="material-symbols-outlined text-3xl">account_balance</span>
                </div>
                <h3 class="text-base font-bold text-on-surface">{{ __('auto.0530') }}</h3>
                <p class="text-xs text-on-surface-variant max-w-sm mx-auto">{{ __('auto.0744') }}</p>
                @if(auth()->user()->canEditCurrentFamily())
                    <button @click="openCreateAccountModal()" type="button" class="mt-2 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#006b5f] text-white text-xs font-bold shadow hover:bg-[#00574d] transition-colors">
                        <span class="material-symbols-outlined text-sm">add</span>
                        立即新增帳戶
                    </button>
                @endif
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($accounts as $account)
                    @php
                        // 類型與 Icon 對應 (Material Symbols)
                        $iconMap = [
                            'cash' => 'payments',
                            'bank' => 'account_balance',
                            'credit' => 'credit_card',
                            'ewallet' => 'wallet',
                            'custom' => 'tune',
                        ];
                        $typeLabelMap = [
                            'cash' => '現金',
                            'bank' => '銀行帳戶',
                            'credit' => '信用卡',
                            'ewallet' => '電子錢包',
                            'custom' => $account->type_custom ?: '自訂帳戶',
                        ];
                        
                        $rawIcon = $account->icon ?: ($iconMap[$account->type] ?? 'payments');
                        $displayIcon = \App\Support\IconHelper::name($rawIcon, 'payments');
                        $displayTypeLabel = $typeLabelMap[$account->type] ?? '帳戶';
                        $accountColor = $account->color ?: '#006b5f';
                    @endphp

                    <div class="bg-[#FFFFFF] border border-[#E7E5E4] rounded-2xl p-5 shadow-sm hover:shadow-md transition-all flex flex-col justify-between relative overflow-hidden group border-t-4"
                         style="border-top-color: {{ $accountColor }}">
                        
                        <!-- Card Top Info -->
                        <div>
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <div class="flex items-center gap-3">
                                    <!-- Icon badge -->
                                    <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0 shadow-sm"
                                         style="background-color: {{ $accountColor }}18; color: {{ $accountColor }};">
                                        <span class="material-symbols-outlined text-2xl">{{ $displayIcon }}</span>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-base text-on-surface line-clamp-1 group-hover:text-[#006b5f] transition-colors">
                                            {{ $account->name }}
                                        </h3>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-[11px] font-semibold text-on-surface-variant">
                                                {{ $displayTypeLabel }}
                                            </span>
                                            <span class="text-[10px] px-1.5 py-0.2 rounded font-mono font-bold bg-[#FAFAF9] text-on-surface-variant border border-[#E7E5E4]">
                                                {{ $account->currency }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                @if(auth()->user()->canEditCurrentFamily())
                                <!-- Quick Transfer Trigger -->
                                <button @click="openTransferModal({{ $account->id }})"
                                        type="button"
                                        title="{{ __('auto.0109') }}"
                                        class="p-1.5 rounded-lg text-on-surface-variant hover:text-[#006b5f] hover:bg-[#FAFAF9] border border-transparent hover:border-[#E7E5E4] transition-all cursor-pointer">
                                    <span class="material-symbols-outlined text-lg">swap_horiz</span>
                                </button>
                                                            @endif
</div>

                            <!-- Balance Display -->
                            <div class="my-4 pt-3 border-t border-[#E7E5E4]/60">
                                <div class="text-[11px] text-on-surface-variant/70 font-semibold mb-0.5">目前餘額</div>
                                <div class="text-2xl font-black tracking-tight {{ $account->balance < 0 ? 'text-danger' : 'text-on-surface' }}">
                                    <span class="text-sm font-semibold text-on-surface-variant mr-1">{{ $account->currency }}</span>
                                    {{ number_format($account->balance, 2) }}
                                </div>
                            </div>
                        </div>

                        @if(auth()->user()->canEditCurrentFamily())
                        <!-- Card Action Buttons -->
                        <div class="pt-3 border-t border-[#E7E5E4] flex items-center justify-between text-xs">
                            <button @click="openEditAccountModal({{ json_encode($account) }})"
                                    type="button"
                                    class="px-3 py-1.5 rounded-lg text-on-surface-variant hover:text-[#006b5f] hover:bg-[#FAFAF9] font-bold flex items-center gap-1.5 transition-colors cursor-pointer border border-transparent hover:border-[#E7E5E4]">
                                <span class="material-symbols-outlined text-base">edit</span>
                                <span>{{ __('common.edit') }}</span>
                            </button>

                            <form action="{{ route('accounts.destroy', $account->id) }}"
                                  method="POST"
                                  onsubmit="return confirm('確定要刪除帳戶「{{ $account->name }}」嗎？此動作將一併清理關聯資料。')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="px-3 py-1.5 rounded-lg text-danger hover:bg-danger/10 font-bold flex items-center gap-1.5 transition-colors cursor-pointer border border-transparent hover:border-danger/20">
                                    <span class="material-symbols-outlined text-base">delete</span>
                                    <span>{{ __('common.delete') }}</span>
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- 3. 新增 / 編輯帳戶 Modal (Add / Edit Account Modal) -->
    <div x-show="accountModalOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
         x-cloak>

        <div @click.outside="accountModalOpen = false" 
             class="bg-[#FFFFFF] border border-[#E7E5E4] rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-5 relative max-h-[90vh] overflow-y-auto">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-3 border-b border-[#E7E5E4]">
                <div class="flex items-center gap-2 text-[#006b5f]">
                    <span class="material-symbols-outlined text-2xl" x-text="isEditing ? 'edit' : 'add_circle'"></span>
                    <h3 class="text-lg font-bold text-on-surface" x-text="isEditing ? '編輯帳戶資料' : '新增家庭帳戶'"></h3>
                </div>
                <button @click="accountModalOpen = false" type="button" class="text-on-surface-variant hover:text-on-surface p-1 rounded-lg hover:bg-[#FAFAF9] transition-colors">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <!-- Form -->
            <form :action="accountActionUrl" method="POST" class="space-y-4">
                @csrf
                <template x-if="isEditing">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <!-- 帳戶名稱 -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        帳戶名稱 <span class="text-danger">*</span>
                    </label>
                    <input type="text" 
                           name="name" 
                           x-model="accountForm.name" 
                           required 
                           placeholder="{{ __('auto.0136') }}"
                           class="w-full px-3.5 py-2.5 bg-[#FFFFFF] border border-[#E7E5E4] rounded-xl text-sm font-semibold focus:outline-none focus:border-[#006b5f] focus:ring-1 focus:ring-[#006b5f]">
                </div>

                <!-- 帳戶類型 (Grid Selection with Material Icons) -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        帳戶類型 <span class="text-danger">*</span>
                    </label>
                    <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" name="type" value="cash" x-model="accountForm.type" class="sr-only">
                            <div class="p-2.5 rounded-xl border text-center transition-all flex flex-col items-center gap-1"
                                 :class="accountForm.type === 'cash' ? 'border-[#006b5f] bg-[#006b5f]/10 text-[#006b5f] font-bold' : 'border-[#E7E5E4] bg-[#FFFFFF] text-on-surface-variant hover:bg-[#FAFAF9]'">
                                <span class="material-symbols-outlined text-xl">payments</span>
                                <span class="text-xs">{{ __('account.type.cash') }}</span>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input type="radio" name="type" value="bank" x-model="accountForm.type" class="sr-only">
                            <div class="p-2.5 rounded-xl border text-center transition-all flex flex-col items-center gap-1"
                                 :class="accountForm.type === 'bank' ? 'border-[#006b5f] bg-[#006b5f]/10 text-[#006b5f] font-bold' : 'border-[#E7E5E4] bg-[#FFFFFF] text-on-surface-variant hover:bg-[#FAFAF9]'">
                                <span class="material-symbols-outlined text-xl">account_balance</span>
                                <span class="text-xs">{{ __('account.type.bank') }}</span>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input type="radio" name="type" value="credit" x-model="accountForm.type" class="sr-only">
                            <div class="p-2.5 rounded-xl border text-center transition-all flex flex-col items-center gap-1"
                                 :class="accountForm.type === 'credit' ? 'border-[#006b5f] bg-[#006b5f]/10 text-[#006b5f] font-bold' : 'border-[#E7E5E4] bg-[#FFFFFF] text-on-surface-variant hover:bg-[#FAFAF9]'">
                                <span class="material-symbols-outlined text-xl">credit_card</span>
                                <span class="text-xs">{{ __('account.type.credit_card') }}</span>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input type="radio" name="type" value="ewallet" x-model="accountForm.type" class="sr-only">
                            <div class="p-2.5 rounded-xl border text-center transition-all flex flex-col items-center gap-1"
                                 :class="accountForm.type === 'ewallet' ? 'border-[#006b5f] bg-[#006b5f]/10 text-[#006b5f] font-bold' : 'border-[#E7E5E4] bg-[#FFFFFF] text-on-surface-variant hover:bg-[#FAFAF9]'">
                                <span class="material-symbols-outlined text-xl">wallet</span>
                                <span class="text-xs">{{ __('account.type.ewallet') }}</span>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input type="radio" name="type" value="custom" x-model="accountForm.type" class="sr-only">
                            <div class="p-2.5 rounded-xl border text-center transition-all flex flex-col items-center gap-1"
                                 :class="accountForm.type === 'custom' ? 'border-[#006b5f] bg-[#006b5f]/10 text-[#006b5f] font-bold' : 'border-[#E7E5E4] bg-[#FFFFFF] text-on-surface-variant hover:bg-[#FAFAF9]'">
                                <span class="material-symbols-outlined text-xl">tune</span>
                                <span class="text-xs">{{ __('auto.0610') }}</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- 自訂類型名稱 (Custom Type Name) -->
                <div x-show="accountForm.type === 'custom'" x-transition>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        自訂類型名稱
                    </label>
                    <input type="text" 
                           name="type_custom" 
                           x-model="accountForm.type_custom" 
                           placeholder="{{ __('auto.0137') }}"
                           class="w-full px-3.5 py-2.5 bg-[#FFFFFF] border border-[#E7E5E4] rounded-xl text-sm font-semibold focus:outline-none focus:border-[#006b5f]">
                </div>

                <!-- 金額餘額與幣別 -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                            目前餘額 <span class="text-danger">*</span>
                        </label>
                        <input type="number" 
                               step="0.01" 
                               name="balance" 
                               x-model="accountForm.balance" 
                               required 
                               placeholder="0.00"
                               class="w-full px-3.5 py-2.5 bg-[#FFFFFF] border border-[#E7E5E4] rounded-xl text-sm font-semibold focus:outline-none focus:border-[#006b5f]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">{{ __('account.currency') }}</label>
                        <select name="currency" 
                                x-model="accountForm.currency" 
                                class="w-full px-3 py-2.5 bg-[#FFFFFF] border border-[#E7E5E4] rounded-xl text-sm font-semibold focus:outline-none focus:border-[#006b5f]">
                            <option value="TWD">TWD (新台幣)</option>
                            <option value="USD">USD (美元)</option>
                            <option value="JPY">JPY (日圓)</option>
                            <option value="EUR">EUR (歐元)</option>
                            <option value="HKD">HKD (港幣)</option>
                            <option value="CNY">CNY (人民幣)</option>
                        </select>
                    </div>
                </div>

                <!-- 顏色邊條選擇 (Color Picker) -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        顏色邊條標籤
                    </label>
                    <div class="flex items-center gap-2 flex-wrap">
                        <template x-for="color in presetColors" :key="color">
                            <button @click="accountForm.color = color" 
                                    type="button"
                                    class="w-7 h-7 rounded-full transition-transform cursor-pointer border-2"
                                    :class="accountForm.color === color ? 'scale-125 border-on-surface shadow-md' : 'border-transparent hover:scale-110'"
                                    :style="`background-color: ${color}`"></button>
                        </template>
                        <input type="color" 
                               name="color" 
                               x-model="accountForm.color" 
                               class="w-8 h-8 rounded-lg cursor-pointer border border-[#E7E5E4] bg-transparent">
                    </div>
                </div>

                <!-- Icon 選擇 (Icon Picker with Material Symbols) -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        帳戶識別 Icon
                    </label>
                    <input type="hidden" name="icon" :value="accountForm.icon">
                    <div class="grid grid-cols-4 sm:grid-cols-8 gap-2">
                        <template x-for="item in presetIcons" :key="item.icon">
                            <button @click="accountForm.icon = item.icon" 
                                    type="button"
                                    class="p-2 rounded-xl border flex flex-col items-center justify-center transition-all cursor-pointer"
                                    :class="accountForm.icon === item.icon ? 'border-[#006b5f] bg-[#006b5f]/10 text-[#006b5f]' : 'border-[#E7E5E4] bg-[#FFFFFF] text-on-surface-variant hover:bg-[#FAFAF9]'">
                                <span class="material-symbols-outlined text-xl" x-text="item.icon"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="pt-4 border-t border-[#E7E5E4] flex items-center justify-end gap-3">
                    <button @click="accountModalOpen = false" 
                            type="button" 
                            class="px-4 py-2 rounded-xl text-sm font-bold text-on-surface-variant hover:bg-[#FAFAF9] border border-[#E7E5E4] transition-colors cursor-pointer">{{ __('common.cancel') }}</button>
                    <button type="submit" 
                            class="px-5 py-2 rounded-xl text-sm font-bold bg-[#006b5f] hover:bg-[#00574d] text-white shadow transition-colors cursor-pointer flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-lg">check</span>
                        <span x-text="isEditing ? '儲存變更' : '建立帳戶'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 4. 帳戶內部轉帳 Modal (Transfer Modal) -->
    <div x-show="transferModalOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
         x-cloak>

        <div @click.outside="transferModalOpen = false" 
             class="bg-[#FFFFFF] border border-[#E7E5E4] rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-5 relative">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-3 border-b border-[#E7E5E4]">
                <div class="flex items-center gap-2 text-[#006b5f]">
                    <span class="material-symbols-outlined text-2xl">swap_horiz</span>
                    <h3 class="text-lg font-bold text-on-surface">{{ __('auto.0319') }}</h3>
                </div>
                <button @click="transferModalOpen = false" type="button" class="text-on-surface-variant hover:text-on-surface p-1 rounded-lg hover:bg-[#FAFAF9] transition-colors">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <!-- Transfer Form -->
            <form action="{{ route('accounts.transfer') }}" method="POST" class="space-y-4">
                @csrf

                <!-- 轉出與轉入帳戶選擇 -->
                <div class="space-y-3 bg-[#FAFAF9] p-3.5 rounded-xl border border-[#E7E5E4]">
                    <!-- 轉出帳戶 (from_account_id) -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">
                            轉出帳戶 (From) <span class="text-danger">*</span>
                        </label>
                        <select name="from_account_id" 
                                x-model="transferForm.from_account_id" 
                                required 
                                class="w-full px-3 py-2 bg-[#FFFFFF] border border-[#E7E5E4] rounded-lg text-sm font-semibold text-on-surface focus:outline-none focus:border-[#006b5f]">
                            <option value="" disabled>-- 請選擇轉出帳戶 --</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">
                                    {{ $acc->name }} (餘額: {{ $acc->currency }} {{ number_format($acc->balance, 2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Transfer Direction Indicator Icon -->
                    <div class="text-center text-[#006b5f] py-0.5">
                        <span class="material-symbols-outlined text-xl rotate-90 sm:rotate-0">arrow_downward</span>
                    </div>

                    <!-- 轉入帳戶 (to_account_id) -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">
                            轉入帳戶 (To) <span class="text-danger">*</span>
                        </label>
                        <select name="to_account_id" 
                                x-model="transferForm.to_account_id" 
                                required 
                                class="w-full px-3 py-2 bg-[#FFFFFF] border border-[#E7E5E4] rounded-lg text-sm font-semibold text-on-surface focus:outline-none focus:border-[#006b5f]">
                            <option value="" disabled>-- 請選擇轉入帳戶 --</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">
                                    {{ $acc->name }} (餘額: {{ $acc->currency }} {{ number_format($acc->balance, 2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Same Account Warning -->
                <div x-show="transferForm.from_account_id && transferForm.to_account_id && transferForm.from_account_id == transferForm.to_account_id" 
                     class="p-2.5 rounded-xl bg-danger/10 border border-danger/20 text-danger text-xs font-bold flex items-center gap-2" 
                     x-cloak>
                    <span class="material-symbols-outlined text-base">warning</span>
                    <span>{{ __('auto.0665') }}</span>
                </div>

                <!-- 金額 (Amount) -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        轉帳金額 <span class="text-danger">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-2.5 text-sm font-bold text-on-surface-variant">NT$</span>
                        <input type="number" 
                               step="0.01" 
                               min="0.01" 
                               name="amount" 
                               x-model="transferForm.amount" 
                               required 
                               placeholder="0.00"
                               class="w-full pl-12 pr-3.5 py-2.5 bg-[#FFFFFF] border border-[#E7E5E4] rounded-xl text-sm font-bold text-on-surface focus:outline-none focus:border-[#006b5f] focus:ring-1 focus:ring-[#006b5f]">
                    </div>
                </div>

                <!-- 轉帳日期 (occurred_at) -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        轉帳日期 <span class="text-danger">*</span>
                    </label>
                    <input type="date" 
                           name="occurred_at" 
                           x-model="transferForm.occurred_at" 
                           required 
                           class="w-full px-3.5 py-2.5 bg-[#FFFFFF] border border-[#E7E5E4] rounded-xl text-sm font-semibold focus:outline-none focus:border-[#006b5f]">
                </div>

                <!-- 備註 (description) -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        轉帳備註
                    </label>
                    <input type="text" 
                           name="description" 
                           x-model="transferForm.description" 
                           placeholder="{{ __('auto.0133') }}"
                           class="w-full px-3.5 py-2.5 bg-[#FFFFFF] border border-[#E7E5E4] rounded-xl text-sm font-semibold focus:outline-none focus:border-[#006b5f]">
                </div>

                <!-- Modal Footer -->
                <div class="pt-4 border-t border-[#E7E5E4] flex items-center justify-end gap-3">
                    <button @click="transferModalOpen = false" 
                            type="button" 
                            class="px-4 py-2 rounded-xl text-sm font-bold text-on-surface-variant hover:bg-[#FAFAF9] border border-[#E7E5E4] transition-colors cursor-pointer">{{ __('common.cancel') }}</button>
                    <button type="submit" 
                            :disabled="!transferForm.from_account_id || !transferForm.to_account_id || transferForm.from_account_id == transferForm.to_account_id || !transferForm.amount"
                            class="px-5 py-2 rounded-xl text-sm font-bold bg-[#006b5f] hover:bg-[#00574d] disabled:opacity-50 disabled:cursor-not-allowed text-white shadow transition-all cursor-pointer flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-lg">send</span>
                        <span>{{ __('auto.0555') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function accountManager() {
    return {
        // Modal States
        accountModalOpen: false,
        isEditing: false,
        transferModalOpen: false,

        accounts: @js($accounts),

        // Account Form
        accountForm: {
            id: null,
            name: '',
            type: 'cash',
            type_custom: '',
            balance: 0,
            currency: 'TWD',
            color: '#006b5f',
            icon: 'payments'
        },

        // Transfer Form
        transferForm: {
            from_account_id: '',
            to_account_id: '',
            amount: '',
            occurred_at: '{{ date("Y-m-d") }}',
            description: ''
        },

        // Color & Icon Presets
        presetColors: ['#006b5f', '#10B981', '#3B82F6', '#6366F1', '#8B5CF6', '#EC4899', '#F59E0B', '#EF4444', '#64748B'],
        presetIcons: [
            { icon: 'payments', label: '現金' },
            { icon: 'account_balance', label: '銀行' },
            { icon: 'credit_card', label: '信用卡' },
            { icon: 'wallet', label: '電子錢包' },
            { icon: 'savings', label: '儲蓄' },
            { icon: 'paid', label: '收入' },
            { icon: 'account_balance_wallet', label: '錢包' },
            { icon: 'currency_exchange', label: '外幣' }
        ],

        // Open Add Account Modal
        openCreateAccountModal() {
            this.isEditing = false;
            this.accountForm = {
                id: null,
                name: '',
                type: 'cash',
                type_custom: '',
                balance: 0,
                currency: 'TWD',
                color: '#006b5f',
                icon: 'payments'
            };
            this.accountModalOpen = true;
        },

        // Open Edit Account Modal
        openEditAccountModal(account) {
            this.isEditing = true;
            this.accountForm = {
                id: account.id,
                name: account.name,
                type: account.type || 'cash',
                type_custom: account.type_custom || '',
                balance: account.balance,
                currency: account.currency || 'TWD',
                color: account.color || '#006b5f',
                icon: account.icon || 'account_balance'
            };
            this.accountModalOpen = true;
        },

        // Open Transfer Modal
        openTransferModal(fromAccountId = null) {
            const firstId = fromAccountId || (this.accounts.length > 0 ? this.accounts[0].id : '');
            let secondId = '';
            if (this.accounts.length > 1) {
                const target = this.accounts.find(a => a.id != firstId);
                if (target) secondId = target.id;
            }

            this.transferForm = {
                from_account_id: firstId,
                to_account_id: secondId,
                amount: '',
                occurred_at: '{{ date("Y-m-d") }}',
                description: ''
            };
            this.transferModalOpen = true;
        },

        get accountActionUrl() {
            return this.isEditing ? `/accounts/${this.accountForm.id}` : '{{ route("accounts.store") }}';
        }
    };
}
</script>
@endsection
