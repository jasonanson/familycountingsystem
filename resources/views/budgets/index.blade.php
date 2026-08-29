<x-app-layout>
    
    <x-slot name="title">{{ __('auto.0288') }}</x-slot>

    <!-- Custom CSS to perfectly align with Google Stitch 3.1 & 3.2 -->
    <style>
        .progress-bar-container {
            position: relative;
            height: 12px;
            background-color: #eee7e0;
            border-radius: 999px;
            overflow: hidden;
        }
        .progress-bar-fill {
            height: 100%;
            border-radius: 999px;
            transition: width 0.4s ease;
        }
        .chip-radio:checked + label {
            background-color: #14b8a6 !important;
            color: #ffffff !important;
            border-color: #14b8a6 !important;
        }
        .form-input:focus-within {
            border-color: #14b8a6;
            box-shadow: 0 0 0 2px rgba(20, 184, 166, 0.2);
        }
    </style>

    <div x-data="budgetManagement()" class="space-y-6">

        <!-- Top Controls: Month Switcher & Action Buttons -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-surface-pure p-4 sm:px-6 sm:py-4 rounded-2xl border border-border-base shadow-sm">
            <!-- Month Selector -->
            <div class="flex items-center gap-2 w-full sm:w-auto justify-between sm:justify-start">
                <a href="{{ route('budgets.index', ['month' => $prevMonth]) }}" 
                   class="w-10 h-10 rounded-xl flex items-center justify-center border border-border-base hover:bg-surface-container-high text-on-surface-variant transition-colors active:scale-95"
                   title="{{ __('auto.0082') }}">
                    <span class="material-symbols-outlined text-[22px]">chevron_left</span>
                </a>
                
                <form action="{{ route('budgets.index') }}" method="GET" class="flex items-center gap-2">
                    <div class="relative flex items-center">
                        <span class="material-symbols-outlined absolute left-3 text-primary text-[20px] pointer-events-none">calendar_month</span>
                        <input type="month" 
                               name="month" 
                               value="{{ $selectedMonth }}" 
                               onchange="this.form.submit()"
                               class="pl-9 pr-3 py-2 bg-background-warm border border-border-base rounded-xl font-bold text-text-primary text-base focus:border-primary focus:ring-1 focus:ring-primary cursor-pointer transition-colors shadow-inner">
                    </div>
                </form>

                <a href="{{ route('budgets.index', ['month' => $nextMonth]) }}" 
                   class="w-10 h-10 rounded-xl flex items-center justify-center border border-border-base hover:bg-surface-container-high text-on-surface-variant transition-colors active:scale-95"
                   title="{{ __('auto.0084') }}">
                    <span class="material-symbols-outlined text-[22px]">chevron_right</span>
                </a>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2.5 w-full sm:w-auto">
                <a href="{{ route('reports.export.pdf.budget', ['month' => $selectedMonth]) }}"
                   target="_blank"
                   class="flex-1 sm:flex-initial flex items-center justify-center gap-2 h-11 px-4 rounded-xl bg-danger hover:bg-danger text-white font-bold text-sm transition-all active:scale-95 shadow-sm"
                   title="{{ __('auto.0217') }}">
                    <span class="material-symbols-outlined text-[18px]">picture_as_pdf</span>
                    <span>匯出 PDF</span>
                </a>
                <button type="button"
                        onclick="window.location='{{ route('export.csv', ['month' => $selectedMonth]) }}'"
                        class="flex-1 sm:flex-initial flex items-center justify-center gap-2 h-11 px-4 rounded-xl border border-border-base bg-surface-pure hover:bg-surface-container-high text-on-surface-variant font-bold text-sm transition-all active:scale-95 shadow-sm"
                        title="{{ __('auto.0214') }}">
                    <span class="material-symbols-outlined text-[18px]">file_download</span>
                    <span>{{ __('auto.0214') }}</span>
                </button>
                @if(auth()->user()->canEditCurrentFamily())
                    <button type="button"
                            @click="openCopyModal()"
                            class="flex-1 sm:flex-initial flex items-center justify-center gap-2 h-11 px-4 rounded-xl border border-border-base bg-surface-pure hover:bg-surface-container-high text-on-surface-variant font-bold text-sm transition-all active:scale-95 shadow-sm">
                        <span class="material-symbols-outlined text-[18px]">content_copy</span>
                        <span>{{ __('auto.0617') }}</span>
                    </button>

                    <button type="button"
                            @click="openBudgetModal()"
                            class="flex-1 sm:flex-initial flex items-center justify-center gap-2 h-11 px-5 rounded-xl bg-primary-container text-on-primary font-bold text-sm hover:opacity-90 active:scale-95 transition-all shadow-[0_4px_12px_rgba(20,184,166,0.25)]">
                        <span class="material-symbols-outlined text-[20px]">add_circle</span>
                        <span>{{ __('auto.0380') }}</span>
                    </button>
                @else
                    @include('partials.read-only-notice')
                @endif
            </div>
        </div>

        <!-- Google Stitch 3.1: Current Month Summary Card (Top) -->
        <section class="bg-surface-pure rounded-2xl border border-border-base p-6 md:p-8 shadow-[0_4px_16px_rgba(28,25,23,0.03)] relative overflow-hidden">
            <!-- Background Watermark Accent -->
            <div class="absolute -right-8 -bottom-8 opacity-5 text-primary pointer-events-none">
                <span class="material-symbols-outlined text-[180px]">account_balance_wallet</span>
            </div>

            <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-6 relative z-10">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="px-2.5 py-0.5 rounded-full bg-primary/10 text-primary text-xs font-bold">
                            {{ \Carbon\Carbon::parse($selectedMonth.'-01')->format('Y 年 m 月') }}
                        </span>
                        <h2 class="text-sm md:text-base font-semibold text-on-surface-variant">{{ __('auto.0280') }}</h2>
                    </div>
                    <div class="text-3xl md:text-5xl font-black text-text-primary tracking-tight">
                        NT$ {{ number_format($totalBudgetAmount) }}
                    </div>
                </div>

                <div class="mt-4 md:mt-0 text-left md:text-right w-full md:w-auto">
                    <div class="text-sm md:text-base font-bold text-on-surface-variant mb-1">
                        已使用 <span class="text-text-primary font-mono font-black text-lg">NT$ {{ number_format($totalSpentAmount) }}</span> / NT$ {{ number_format($totalBudgetAmount) }}
                        <span class="ml-1 text-primary font-bold">({{ $totalUsagePercent }}%)</span>
                    </div>
                    <div class="text-xs text-on-surface-variant">
                        剩餘可用額度：
                        <span class="font-bold {{ ($totalBudgetAmount - $totalSpentAmount) >= 0 ? 'text-success' : 'text-danger' }}">
                            NT$ {{ number_format($totalBudgetAmount - $totalSpentAmount) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Dynamic Gradient Progress Bar -->
            <div class="progress-bar-container bg-surface-container-high h-4 md:h-5 shadow-inner relative z-10">
                <div class="progress-bar-fill {{ $overallHealthStatus === 'danger' ? 'bg-danger' : ($overallHealthStatus === 'warning' ? 'bg-warning' : 'bg-primary-container') }}" 
                     style="width: {{ min(100, $totalUsagePercent) }}%;"></div>
            </div>

            <!-- Status / Warning Context & Indicator Badge -->
            <div class="mt-5 flex flex-wrap items-center justify-between gap-3 relative z-10 border-t border-border-base/60 pt-4">
                <div class="flex items-center gap-2">
                    @if($overallHealthStatus === 'healthy')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-success/10 text-success text-xs font-bold border border-success/20">
                            <span class="material-symbols-outlined text-[16px]">check_circle</span>
                            <span>{{ $overallHealthText }}</span>
                        </span>
                    @elseif($overallHealthStatus === 'warning')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-warning/10 text-warning text-xs font-bold border border-warning/20">
                            <span class="material-symbols-outlined text-[16px]">warning</span>
                            <span>{{ $overallHealthText }}</span>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-danger/10 text-danger text-xs font-bold border border-danger/20 animate-pulse">
                            <span class="material-symbols-outlined text-[16px]">error</span>
                            <span>{{ $overallHealthText }}</span>
                        </span>
                    @endif
                    <span class="text-xs text-on-surface-variant hidden sm:inline">全期起訖：{{ \Carbon\Carbon::parse($selectedMonth.'-01')->startOfMonth()->format('Y/m/d') }} ~ {{ \Carbon\Carbon::parse($selectedMonth.'-01')->endOfMonth()->format('Y/m/d') }}</span>
                </div>

                <div class="flex items-center gap-3 text-xs text-on-surface-variant">
                    <span class="inline-flex items-center gap-1">
                        <span class="w-2.5 h-2.5 rounded-full bg-primary-container"></span> 正常 (&lt;80%)
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <span class="w-2.5 h-2.5 rounded-full bg-warning"></span> 警戒 (80-99%)
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <span class="w-2.5 h-2.5 rounded-full bg-danger"></span> 超支 (≥100%)
                    </span>
                </div>
            </div>
        </section>

        <!-- Google Stitch 3.1: Category Budget Breakdown Cards -->
        <section class="space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-2xl">pie_chart</span>
                    <h3 class="text-lg md:text-xl font-bold text-text-primary tracking-tight">{{ __('auto.0232') }}</h3>
                </div>
                <span class="text-xs text-on-surface-variant">共 {{ count($categoryCards ?? []) }} 個支出類別</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @forelse(($categoryCards ?? []) as $card)
                    <div class="bg-surface-pure rounded-2xl border {{ $card['is_over_100'] ? 'border-danger/40 bg-danger/[0.01]' : ($card['is_over_80'] ? 'border-warning/40' : 'border-border-base') }} p-5 shadow-[0_4px_12px_rgba(28,25,23,0.02)] relative overflow-hidden group hover:shadow-[0_8px_24px_rgba(28,25,23,0.06)] transition-all">
                        <!-- Left colored vertical bar (Google Stitch 3.1 standard) -->
                        <div class="absolute left-0 top-0 bottom-0 w-1.5" style="background-color: {{ $card['color'] }};"></div>

                        <div class="flex items-start justify-between gap-3 mb-3.5 pl-2.5">
                            <!-- Category Icon & Name -->
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-xl flex items-center justify-center shadow-sm flex-shrink-0"
                                     style="background-color: {{ $card['color'] }}20; color: {{ $card['color'] }};">
                                    <span class="material-symbols-outlined text-2xl" style="font-variation-settings: 'FILL' 1;">
                                        {{ \App\Support\IconHelper::name($card["icon"] ?? null, 'category') }}
                                    </span>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-base font-bold text-text-primary">{{ $card['name'] }}</h4>
                                        <span class="px-2 py-0.5 rounded-md bg-surface-container-high text-on-surface-variant text-[11px] font-semibold">
                                            {{ $card['scope_label'] }}
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-on-surface-variant mt-0.5">
                                        預算配額：NT$ {{ number_format($card['budget_amount']) }}
                                    </div>
                                </div>
                            </div>

                            @if(auth()->user()->canEditCurrentFamily())
                            <!-- Action Buttons (Hover / Visible on mobile) -->
                            <div class="flex items-center gap-1 opacity-100 sm:opacity-0 group-hover:opacity-100 transition-opacity">
                                <button type="button" 
                                        @click="openSingleEdit({{ json_encode($card) }})"
                                        class="p-1.5 text-on-surface-variant hover:text-primary hover:bg-surface-container-high rounded-lg transition-colors"
                                        title="{{ __('auto.0592') }}">
                                    <span class="material-symbols-outlined text-[18px]">edit</span>
                                </button>
                                
                                @if($card['id'])
                                    <form action="{{ route('budgets.destroy', $card['id']) }}" method="POST" onsubmit="return confirm('確定要移除此分類預算設定嗎？');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-danger hover:bg-danger/10 rounded-lg transition-colors" title="{{ __('common.delete') }}">
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                        </button>
                                    </form>
                                @endif
                            </div>
                            @endif
                        </div>

                        <!-- Amount & Percentage -->
                        <div class="pl-2.5 space-y-2">
                            <div class="flex justify-between items-baseline">
                                <div class="text-xs font-medium {{ $card['is_over_100'] ? 'text-danger font-bold' : ($card['is_over_80'] ? 'text-warning font-bold' : 'text-on-surface-variant') }}">
                                    已用 NT$ {{ number_format($card['spent_amount']) }} / NT$ {{ number_format($card['budget_amount']) }}
                                </div>
                                <div class="text-lg font-black {{ $card['is_over_100'] ? 'text-danger' : ($card['is_over_80'] ? 'text-warning' : 'text-text-primary') }}">
                                    {{ $card['percentage'] }}%
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="progress-bar-container h-2.5">
                                <div class="progress-bar-fill" 
                                     style="width: {{ min(100, $card['percentage']) }}%; background-color: {{ $card['is_over_100'] ? '#EF4444' : ($card['is_over_80'] ? '#F59E0B' : $card['color']) }};"></div>
                            </div>

                            <!-- Alert Badges -->
                            <div class="flex items-center gap-1.5 pt-1">
                                @if($card['is_over_100'])
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-danger/10 text-danger text-[11px] font-bold border border-danger/20">
                                        <span class="material-symbols-outlined text-[13px]">error</span>
                                        已超支
                                    </span>
                                @elseif($card['is_over_80'])
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-warning/10 text-warning text-[11px] font-bold border border-warning/20">
                                        <span class="material-symbols-outlined text-[13px]">warning</span>
                                        已超過 80% 警示線
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded bg-surface-container-high text-on-surface-variant text-[11px] font-medium">
                                        80% 警示
                                    </span>
                                    <span class="px-2 py-0.5 rounded bg-surface-container-high text-on-surface-variant text-[11px] font-medium">
                                        100% 警示
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-12 text-center bg-surface-pure rounded-2xl border border-dashed border-border-base">
                        <span class="material-symbols-outlined text-4xl text-on-surface-variant/40 mb-2">account_balance_wallet</span>
                        <p class="text-sm font-semibold text-on-surface-variant">{{ __('auto.0534') }}</p>
                        @if(auth()->user()->canEditCurrentFamily())
                            <button type="button" @click="openBudgetModal()" class="mt-3 px-4 py-2 bg-primary-container text-on-primary text-xs font-bold rounded-xl shadow-sm hover:opacity-90 transition-opacity">
                                + 立即設定預算
                            </button>
                        @endif
                    </div>
                @endforelse
            </div>
        </section>

        <!-- Google Stitch 3.2 Modal: 新增/編輯預算 Modal (Full Flow) -->
        <div x-show="budgetModalOpen" 
             x-cloak 
             class="fixed inset-0 z-50 overflow-y-auto" 
             aria-labelledby="modal-title" 
             role="dialog" 
             aria-modal="true">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" 
                 @click="budgetModalOpen = false"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"></div>

            <!-- Modal Dialog -->
            <div class="flex min-h-full items-center justify-center p-3 sm:p-4 text-center">
                <div class="relative transform overflow-hidden rounded-3xl bg-background-warm text-left shadow-2xl transition-all w-full max-w-2xl border border-border-base my-8"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                    
                    <!-- Header -->
                    <div class="sticky top-0 z-20 bg-surface-pure/95 backdrop-blur-md px-6 py-4 border-b border-border-base flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                                <span class="material-symbols-outlined text-[22px]">tune</span>
                            </div>
                            <h3 class="text-lg font-bold text-text-primary" id="modal-title">
                                編輯與分配預算
                            </h3>
                        </div>
                        <button type="button" 
                                @click="budgetModalOpen = false" 
                                class="w-8 h-8 rounded-full flex items-center justify-center text-on-surface-variant hover:bg-surface-container-high transition-colors">
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <!-- Form Content -->
                    <form action="{{ route('budgets.store') }}" method="POST" id="budgetForm" class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
                        @csrf
                        <input type="hidden" name="month" value="{{ $selectedMonth }}">

                        <!-- Section 1: 基本設定 (Google Stitch 3.2) -->
                        <section class="bg-surface-pure rounded-2xl border border-border-base p-5 shadow-sm space-y-4">
                            <div class="flex items-center gap-2 border-b border-border-base pb-3">
                                <span class="material-symbols-outlined text-primary-container" style="font-variation-settings: 'FILL' 1;">tune</span>
                                <h4 class="text-base font-bold text-text-primary">{{ __('auto.0252') }}</h4>
                            </div>

                            <!-- Budget Name -->
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-on-surface-variant">{{ __('auto.0730') }}</label>
                                <div class="form-input flex items-center border border-border-base rounded-xl h-11 px-3.5 bg-surface-pure transition-all">
                                    <input type="text" 
                                           name="name" 
                                           value="{{ \Carbon\Carbon::parse($selectedMonth.'-01')->format('Y年m月') }} 家庭總預算"
                                           placeholder="{{ __('auto.0130') }}" 
                                           class="w-full outline-none bg-transparent text-sm font-medium text-text-primary">
                                </div>
                            </div>

                            <!-- Scope Selection Chips -->
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-on-surface-variant">{{ __('auto.0688') }}</label>
                                <div class="flex flex-wrap gap-2">
                                    <input type="radio" id="scope_family" name="scope" value="family" class="chip-radio hidden" checked>
                                    <label for="scope_family" class="cursor-pointer border border-border-base rounded-full px-4 py-1.5 text-xs font-bold transition-all hover:bg-surface-container-highest">
                                        全家庭
                                    </label>

                                    <input type="radio" id="scope_category" name="scope" value="category" class="chip-radio hidden">
                                    <label for="scope_category" class="cursor-pointer border border-border-base rounded-full px-4 py-1.5 text-xs font-bold transition-all hover:bg-surface-container-highest flex items-center gap-1">
                                        分類預算
                                    </label>

                                    <input type="radio" id="scope_user" name="scope" value="user" class="chip-radio hidden">
                                    <label for="scope_user" class="cursor-pointer border border-border-base rounded-full px-4 py-1.5 text-xs font-bold transition-all hover:bg-surface-container-highest">
                                        個人分配
                                    </label>
                                </div>
                            </div>

                            <!-- Period Cycle Selection Chips -->
                            <div class="space-y-2" x-data="{ cycle: 'month' }">
                                <label class="text-xs font-bold text-on-surface-variant">{{ __('auto.0732') }}</label>
                                <div class="grid grid-cols-4 gap-2">
                                    <input type="radio" id="cycle_month" name="period_type" value="month" class="chip-radio hidden" x-model="cycle">
                                    <label for="cycle_month" class="text-center cursor-pointer border border-border-base rounded-xl py-2 text-xs font-bold transition-all hover:bg-surface-container-highest">
                                        月度
                                    </label>

                                    <input type="radio" id="cycle_quarter" name="period_type" value="quarter" class="chip-radio hidden" x-model="cycle">
                                    <label for="cycle_quarter" class="text-center cursor-pointer border border-border-base rounded-xl py-2 text-xs font-bold transition-all hover:bg-surface-container-highest">
                                        季度
                                    </label>

                                    <input type="radio" id="cycle_year" name="period_type" value="year" class="chip-radio hidden" x-model="cycle">
                                    <label for="cycle_year" class="text-center cursor-pointer border border-border-base rounded-xl py-2 text-xs font-bold transition-all hover:bg-surface-container-highest">
                                        年度
                                    </label>

                                    <input type="radio" id="cycle_custom" name="period_type" value="custom" class="chip-radio hidden" x-model="cycle">
                                    <label for="cycle_custom" class="text-center cursor-pointer border border-border-base rounded-xl py-2 text-xs font-bold transition-all hover:bg-surface-container-highest">{{ __('auto.0610') }}</label>
                                </div>

                                <!-- Custom Date Range if custom selected -->
                                <div x-show="cycle === 'custom'" class="grid grid-cols-2 gap-3 pt-2">
                                    <div>
                                        <label class="text-[11px] text-on-surface-variant font-medium">{{ __('auto.0654') }}</label>
                                        <input type="date" name="period_start" value="{{ \Carbon\Carbon::parse($selectedMonth.'-01')->startOfMonth()->format('Y-m-d') }}" class="w-full text-xs border border-border-base rounded-xl p-2 bg-surface-pure mt-1">
                                    </div>
                                    <div>
                                        <label class="text-[11px] text-on-surface-variant font-medium">{{ __('field.end_date') }}</label>
                                        <input type="date" name="period_end" value="{{ \Carbon\Carbon::parse($selectedMonth.'-01')->endOfMonth()->format('Y-m-d') }}" class="w-full text-xs border border-border-base rounded-xl p-2 bg-surface-pure mt-1">
                                    </div>
                                </div>
                            </div>

                            <!-- Total Budget Amount Input -->
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-on-surface-variant">{{ __('auto.0169') }}</label>
                                <div class="form-input flex items-center border border-border-base rounded-xl h-14 px-4 bg-surface-pure transition-all">
                                    <span class="text-xl font-black text-primary mr-2">NT$</span>
                                    <input type="number" 
                                           name="amount" 
                                           x-model.number="formTotalAmount"
                                           placeholder="50000" 
                                           step="100" 
                                           required
                                           class="flex-1 outline-none bg-transparent text-2xl font-black text-text-primary w-full">
                                </div>
                            </div>
                        </section>

                        <!-- Section 2: 分類配額設定 (Category Quota Allocation) -->
                        <section class="bg-surface-pure rounded-2xl border border-border-base p-5 shadow-sm space-y-4">
                            <div class="flex items-center justify-between border-b border-border-base pb-3">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">category</span>
                                    <h4 class="text-base font-bold text-text-primary">{{ __('auto.0585') }}</h4>
                                </div>
                                <button type="button" 
                                        @click="autoDistribute()" 
                                        class="text-xs font-bold text-primary hover:underline flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[15px]">auto_fix_high</span>
                                    平均分配
                                </button>
                            </div>

                            <p class="text-xs text-on-surface-variant">{{ __('auto.0475') }}</p>

                            <!-- Category List Input Grid -->
                            <div class="space-y-3">
                                <template x-for="(cat, index) in categoryInputs" :key="cat.id">
                                    <div class="flex items-center justify-between gap-3 p-2.5 rounded-xl bg-background-warm border border-border-base/70">
                                        <div class="flex items-center gap-2.5 min-w-[120px]">
                                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm font-bold"
                                                 :style="'background-color:' + cat.color + '20; color:' + cat.color">
                                                <span class="material-symbols-outlined text-[18px]" x-text="cat.icon"></span>
                                            </div>
                                            <span class="text-xs font-bold text-text-primary" x-text="cat.name"></span>
                                        </div>

                                        <div class="flex items-center gap-1.5 flex-1 max-w-[200px]">
                                            <span class="text-xs font-bold text-on-surface-variant">NT$</span>
                                            <input type="number" 
                                                   :name="'categories[' + cat.id + ']'" 
                                                   x-model.number="cat.amount" 
                                                   @input="calcAllocated()"
                                                   placeholder="0" 
                                                   step="100" 
                                                   class="w-full text-right py-1.5 px-2 bg-surface-pure border border-border-base rounded-lg text-xs font-bold text-text-primary focus:border-primary">
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Allocation Status Summary -->
                            <div class="bg-surface-container-high/40 rounded-xl p-3.5 flex justify-between items-center text-xs font-bold">
                                <div>
                                    <span class="text-on-surface-variant">{{ __('auto.0307') }}</span>
                                    <span class="text-text-primary font-mono" x-text="'NT$ ' + totalAllocated.toLocaleString()"></span>
                                </div>
                                <div>
                                    <span class="text-on-surface-variant">{{ __('auto.0413') }}</span>
                                    <span :class="formTotalAmount - totalAllocated >= 0 ? 'text-success' : 'text-danger'" 
                                          class="font-mono"
                                          x-text="'NT$ ' + (formTotalAmount - totalAllocated).toLocaleString()"></span>
                                </div>
                            </div>
                        </section>

                        <!-- Section 3: 超支警示設定 (Google Stitch 3.2) -->
                        <section class="bg-surface-pure rounded-2xl border border-border-base p-5 shadow-sm space-y-4" x-data="{ enableAlert: true }">
                            <div class="flex items-center justify-between border-b border-border-base pb-3">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-warning" style="font-variation-settings: 'FILL' 1;">notifications_active</span>
                                    <h4 class="text-base font-bold text-text-primary">{{ __('auto.0655') }}</h4>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="enableAlert" class="sr-only peer">
                                    <div class="w-11 h-6 bg-surface-dim peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-border-base after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-container"></div>
                                </label>
                            </div>

                            <p class="text-xs text-on-surface-variant">{{ __('auto.0497') }}</p>

                            <!-- Threshold Selection -->
                            <div class="flex flex-wrap gap-2 pt-1" x-show="enableAlert">
                                <label class="cursor-pointer">
                                    <input type="checkbox" name="alert_thresholds[]" value="50" class="peer hidden">
                                    <span class="inline-block border border-border-base rounded-full px-3.5 py-1.5 text-xs font-bold peer-checked:bg-primary-container/15 peer-checked:text-primary peer-checked:border-primary/40 hover:bg-surface-container-highest transition-all">
                                        50% 提醒
                                    </span>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="checkbox" name="alert_thresholds[]" value="80" checked class="peer hidden">
                                    <span class="inline-block border border-border-base rounded-full px-3.5 py-1.5 text-xs font-bold peer-checked:bg-primary-container/15 peer-checked:text-primary peer-checked:border-primary/40 hover:bg-surface-container-highest transition-all">
                                        80% 警戒
                                    </span>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="checkbox" name="alert_thresholds[]" value="100" checked class="peer hidden">
                                    <span class="inline-block border border-border-base rounded-full px-3.5 py-1.5 text-xs font-bold peer-checked:bg-primary-container/15 peer-checked:text-primary peer-checked:border-primary/40 hover:bg-surface-container-highest transition-all">
                                        100% 超支
                                    </span>
                                </label>
                            </div>
                        </section>

                        <!-- Section 4: 進階設定 (Expandable Collapsible) -->
                        <section class="bg-surface-pure rounded-2xl border border-border-base shadow-sm overflow-hidden" x-data="{ advancedOpen: false }">
                            <button type="button" 
                                    @click="advancedOpen = !advancedOpen"
                                    class="w-full flex items-center justify-between p-5 hover:bg-surface-container-lowest transition-colors">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-on-surface-variant">settings_suggest</span>
                                    <h4 class="text-base font-bold text-text-primary">{{ __('auto.0685') }}</h4>
                                </div>
                                <span class="material-symbols-outlined text-on-surface-variant transition-transform duration-200" :class="advancedOpen ? 'rotate-180' : ''">
                                    expand_more
                                </span>
                            </button>

                            <div x-show="advancedOpen" x-collapse class="px-5 pb-5 space-y-4 border-t border-border-base pt-4">
                                <!-- Rollover Switch -->
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-xs font-bold text-text-primary">未用完是否滾入下月 (Rollover)</div>
                                        <div class="text-[11px] text-on-surface-variant mt-0.5">將本期未使用的預算結餘自動遞延至下月額度</div>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="rollover" value="1" class="sr-only peer">
                                        <div class="w-11 h-6 bg-surface-dim peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-border-base after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-container"></div>
                                    </label>
                                </div>

                                <!-- Note -->
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-on-surface-variant">{{ __('field.note_placeholder') }}</label>
                                    <textarea name="notes" rows="2" placeholder="{{ __('auto.0470') }}" class="w-full border border-border-base rounded-xl p-3 bg-surface-pure text-xs text-text-primary focus:border-primary"></textarea>
                                </div>
                            </div>
                        </section>

                        <!-- Footer Actions -->
                        <div class="sticky bottom-0 bg-surface-pure/95 backdrop-blur-md pt-3 pb-1 -mx-6 px-6 border-t border-border-base flex gap-3 z-20">
                            <button type="button" 
                                    @click="budgetModalOpen = false" 
                                    class="flex-1 h-11 rounded-xl border border-border-base font-bold text-sm text-text-primary hover:bg-surface-container-high transition-colors">{{ __('common.cancel') }}</button>
                            <button type="submit" 
                                    class="flex-1 h-11 rounded-xl bg-primary-container font-bold text-sm text-on-primary shadow-[0_4px_12px_rgba(20,184,166,0.25)] hover:opacity-90 active:scale-[0.98] transition-all">
                                儲存預算設定
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Copy Last Month Confirmation Modal -->
        <div x-show="copyModalOpen" 
             x-cloak 
             class="fixed inset-0 z-50 overflow-y-auto" 
             role="dialog" 
             aria-modal="true">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" 
                 @click="copyModalOpen = false"></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center">
                <div class="relative transform overflow-hidden rounded-3xl bg-surface-pure p-6 text-left shadow-2xl transition-all w-full max-w-md border border-border-base">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-2xl">content_copy</span>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-text-primary">{{ __('auto.0618') }}</h3>
                            <p class="text-xs text-on-surface-variant">{{ __('auto.0331') }}</p>
                        </div>
                    </div>

                    <p class="text-sm text-text-primary mb-6 leading-relaxed">
                        系統將讀取 <strong class="text-primary">{{ \Carbon\Carbon::parse($selectedMonth.'-01')->subMonth()->format('Y年m月') }}</strong> 的家庭與分類預算金額，並自動覆蓋至 <strong class="text-primary">{{ \Carbon\Carbon::parse($selectedMonth.'-01')->format('Y年m月') }}</strong>。確定要繼續嗎？
                    </p>

                    <form action="{{ route('budgets.copy') }}" method="POST">
                        @csrf
                        <input type="hidden" name="month" value="{{ $selectedMonth }}">
                        <div class="flex gap-3">
                            <button type="button" 
                                    @click="copyModalOpen = false" 
                                    class="flex-1 h-11 rounded-xl border border-border-base font-bold text-sm text-text-primary hover:bg-surface-container-high transition-colors">{{ __('common.cancel') }}</button>
                            <button type="submit" 
                                    class="flex-1 h-11 rounded-xl bg-primary-container font-bold text-sm text-on-primary shadow-sm hover:opacity-90 transition-opacity">
                                確定一鍵複製
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Single Category Edit Modal -->
        <div x-show="singleEditOpen" 
             x-cloak 
             class="fixed inset-0 z-50 overflow-y-auto" 
             role="dialog" 
             aria-modal="true">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" 
                 @click="singleEditOpen = false"></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center">
                <div class="relative transform overflow-hidden rounded-3xl bg-surface-pure p-6 text-left shadow-2xl transition-all w-full max-w-md border border-border-base">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0"
                             :style="'background-color:' + singleData.color + '20; color:' + singleData.color">
                            <span class="material-symbols-outlined text-2xl" x-text="singleData.icon"></span>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-text-primary">
                                編輯「<span x-text="singleData.name"></span>」預算
                            </h3>
                            <p class="text-xs text-on-surface-variant">{{ \Carbon\Carbon::parse($selectedMonth.'-01')->format('Y年m月') }}</p>
                        </div>
                    </div>

                    <form action="{{ route('budgets.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <input type="hidden" name="month" value="{{ $selectedMonth }}">
                        <input type="hidden" name="scope" value="family">
                        <input type="hidden" name="period_type" value="month">
                        <input type="hidden" name="amount" value="{{ $totalBudgetAmount }}">

                        <div>
                            <label class="text-xs font-bold text-on-surface-variant">{{ __('auto.0733') }}</label>
                            <div class="form-input flex items-center border border-border-base rounded-xl h-12 px-3.5 bg-surface-pure mt-1">
                                <span class="font-bold text-sm text-on-surface-variant mr-2">NT$</span>
                                <input type="number" 
                                       :name="'categories[' + singleData.category_id + ']'" 
                                       x-model.number="singleData.budget_amount"
                                       required
                                       step="100"
                                       class="w-full outline-none bg-transparent font-bold text-lg text-text-primary">
                            </div>
                        </div>

                        <div class="flex gap-3 pt-2">
                            <button type="button" 
                                    @click="singleEditOpen = false" 
                                    class="flex-1 h-11 rounded-xl border border-border-base font-bold text-sm text-text-primary hover:bg-surface-container-high transition-colors">{{ __('common.cancel') }}</button>
                            <button type="submit" 
                                    class="flex-1 h-11 rounded-xl bg-primary-container font-bold text-sm text-on-primary shadow-sm hover:opacity-90 transition-opacity">
                                儲存修改
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <!-- Alpine.js Page Controller Script -->
    <script>
        window.budgetManagement = function budgetManagement() {
            return {
                budgetModalOpen: false,
                copyModalOpen: false,
                singleEditOpen: false,
                formTotalAmount: {{ $totalBudgetAmount }},
                totalAllocated: 0,
                categoryInputs: [
                    @foreach(($categoryCards ?? []) as $card)
                        {
                            id: {{ $card['category_id'] }},
                            name: "{{ $card['name'] }}",
                            icon: "{{ \App\Support\IconHelper::name($card["icon"] ?? null, 'category') }}",
                            color: "{{ $card['color'] }}",
                            amount: {{ $card['budget_amount'] ?: 0 }}
                        },
                    @endforeach
                ],
                singleData: {
                    id: null,
                    category_id: null,
                    name: '',
                    color: '#006b5f',
                    icon: 'category',
                    budget_amount: 0
                },
                init() {
                    this.calcAllocated();
                },
                openBudgetModal() {
                    this.calcAllocated();
                    this.budgetModalOpen = true;
                },
                openCopyModal() {
                    this.copyModalOpen = true;
                },
                openSingleEdit(card) {
                    this.singleData = {
                        id: card.id,
                        category_id: card.category_id,
                        name: card.name,
                        color: card.color,
                        icon: card.icon,
                        budget_amount: card.budget_amount
                    };
                    this.singleEditOpen = true;
                },
                calcAllocated() {
                    this.totalAllocated = this.categoryInputs.reduce((sum, item) => sum + (Number(item.amount) || 0), 0);
                },
                autoDistribute() {
                    if (!this.formTotalAmount || this.categoryInputs.length === 0) return;
                    const perCat = Math.floor(this.formTotalAmount / this.categoryInputs.length / 100) * 100;
                    this.categoryInputs.forEach(item => {
                        item.amount = perCat;
                    });
                    this.calcAllocated();
                }
            }
        }
    </script>
</x-app-layout>
