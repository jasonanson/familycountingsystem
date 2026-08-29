<x-app-layout>
    <x-slot name="title">{{ __('auto.0165') }}</x-slot>

    <div x-data="childLimitsPage()" class="space-y-8">
        <!-- Top Header & Banner -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-surface-pure border border-border-base rounded-2xl p-6 shadow-[0_4px_12px_rgba(28,25,23,0.03)]">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary flex-shrink-0 shadow-sm">
                    <span class="material-symbols-outlined text-[32px]">security</span>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-text-primary tracking-tight flex items-center gap-2">
                        <span>{{ $isParentOrAdmin ? '兒童消費範圍與限額管理' : '我的消費限額說明' }}</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $isParentOrAdmin ? 'bg-category-mint/15 text-primary border border-category-mint/30' : 'bg-primary/10 text-primary border border-primary/20' }}">
                            {{ $isParentOrAdmin ? '家長管理權限' : '兒童專屬' }}
                        </span>
                    </h1>
                    <p class="text-sm text-on-surface-variant mt-1">
                        @if($isParentOrAdmin)
                            為家庭中的每位兒童設定單筆上限、週期累積限制及零用錢比例，建立健康且自主的理財觀念。
                        @else
                            這是家長為你設定的零用錢消費保護上限，幫助你養成健康的儲蓄與花費習慣。如需調整請向家長提出討論喔！
                        @endif
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                @if($isParentOrAdmin)
                    <button type="button" 
                            @click="openCreateModal()" 
                            class="px-5 py-2.5 bg-primary text-on-primary font-bold rounded-xl hover:bg-primary/90 transition-all active:scale-95 shadow-sm flex items-center gap-2 text-sm cursor-pointer">
                        <span class="material-symbols-outlined text-lg">add</span>
                        <span>{{ __('auto.0474') }}</span>
                    </button>
                @else
                    <span class="px-3.5 py-2 bg-surface-container rounded-xl text-xs font-bold text-on-surface-variant flex items-center gap-1.5 border border-border-base">
                        <span class="material-symbols-outlined text-base text-primary">verified_user</span>
                        <span>{{ __('auto.0293') }}</span>
                    </span>
                @endif
            </div>
        </div>

        <!-- Limits Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($childrenWithLimits as $item)
                @php
                    $child = $item->child;
                    $hasLimit = $item->has_limit;
                    $limit = $item->limit;
                    $monthSpent = $item->month_spent;
                    $monthlyMax = $item->monthly_max;
                    $monthPercent = ($monthlyMax && $monthlyMax > 0) ? min(100, round(($monthSpent / $monthlyMax) * 100)) : 0;
                @endphp
                <div class="bg-surface-pure border border-border-base rounded-2xl p-6 shadow-[0_4px_12px_rgba(28,25,23,0.04)] hover:shadow-md transition-all relative overflow-hidden flex flex-col justify-between group">
                    <!-- Top Accent Color Bar -->
                    <div class="absolute top-0 left-0 right-0 h-1.5 {{ $hasLimit ? 'bg-category-mint' : 'bg-outline-variant/40' }}"></div>

                    <div>
                        <!-- Child Header Info -->
                        <div class="flex items-start justify-between mb-5">
                            <div class="flex items-center gap-3.5">
                                <div class="relative">
                                    @if(filled($child?->avatar_url))
                                        <img class="w-13 h-13 rounded-2xl object-cover border-2 border-primary/20 shadow-sm" 
                                             src="{{ asset($child->avatar_url) }}" 
                                             alt="{{ $child->name ?? '兒童' }}">
                                    @else
                                        <div class="w-13 h-13 rounded-2xl bg-gradient-to-br from-primary/15 to-category-mint/20 border border-primary/20 flex items-center justify-center text-primary font-bold text-xl shadow-sm">
                                            {{ mb_substr($child?->name ?? '兒', 0, 1) }}
                                        </div>
                                    @endif
                                    <span class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-surface-pure {{ $hasLimit ? 'bg-success' : 'bg-outline' }}" title="{{ $hasLimit ? '限額生效中' : '無限制' }}"></span>
                                </div>
                                <div>
                                    <h3 class="font-bold text-lg text-text-primary group-hover:text-primary transition-colors flex items-center gap-1.5">
                                        <span>{{ $child?->name ?? '未命名兒童' }}</span>
                                    </h3>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-xs font-mono text-on-surface-variant">@ {{ $child?->account ?? 'child' }}</span>
                                        <span class="px-2 py-0.2 rounded-md text-[11px] font-semibold {{ $hasLimit ? 'bg-success/10 text-success border border-success/20' : 'bg-surface-variant text-on-surface-variant' }}">
                                            {{ $hasLimit ? '限額保護中' : '自由消費' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Monthly Spending Summary & Progress Bar -->
                        <div class="bg-surface-container-low rounded-xl p-4 border border-border-base/60 mb-5">
                            <div class="flex justify-between items-center mb-1.5">
                                <span class="text-xs font-semibold text-on-surface-variant flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm text-primary">analytics</span>
                                    本月已花費
                                </span>
                                <span class="text-sm font-bold text-text-primary">
                                    NT$ {{ number_format($monthSpent) }}
                                    @if($monthlyMax)
                                        <span class="text-xs font-normal text-on-surface-variant">/ 上限 {{ number_format($monthlyMax) }}</span>
                                    @endif
                                </span>
                            </div>
                            
                            @if($monthlyMax && $monthlyMax > 0)
                                <div class="h-2.5 w-full bg-surface-variant rounded-full overflow-hidden mt-2">
                                    <div class="h-full rounded-full transition-all duration-500 {{ $monthPercent >= 90 ? 'bg-danger' : ($monthPercent >= 70 ? 'bg-warning' : 'bg-primary') }}" 
                                         style="width: {{ $monthPercent }}%;"></div>
                                </div>
                                <div class="flex justify-between items-center mt-1 text-[11px] font-medium text-on-surface-variant">
                                    <span>{{ __('budget_page.usage') }}</span>
                                    <span class="{{ $monthPercent >= 90 ? 'text-danger font-bold' : ($monthPercent >= 70 ? 'text-warning font-bold' : 'text-primary') }}">
                                        {{ $monthPercent }}%
                                    </span>
                                </div>
                            @else
                                <div class="text-xs text-on-surface-variant mt-1">未設定每月總花費上限</div>
                            @endif
                        </div>

                        <!-- Limit Breakdown Details -->
                        <div class="space-y-2.5">
                            <!-- 1. 單筆上限 -->
                            <div class="flex items-center justify-between p-2.5 rounded-xl hover:bg-surface-container/50 transition-colors border border-transparent hover:border-border-base/50">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-category-sky/15 text-category-sky flex items-center justify-center flex-shrink-0">
                                        <span class="material-symbols-outlined text-base">shopping_bag</span>
                                    </div>
                                    <span class="text-xs font-medium text-on-surface-variant">{{ __('auto.0241') }}</span>
                                </div>
                                <span class="text-xs font-bold {{ $item->per_transaction_max ? 'text-text-primary' : 'text-on-surface-variant' }}">
                                    {{ $item->per_transaction_max ? 'NT$ ' . number_format($item->per_transaction_max) : '無限制' }}
                                </span>
                            </div>

                            <!-- 2. 每日上限 -->
                            <div class="flex items-center justify-between p-2.5 rounded-xl hover:bg-surface-container/50 transition-colors border border-transparent hover:border-border-base/50">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-category-amber/15 text-category-amber flex items-center justify-center flex-shrink-0">
                                        <span class="material-symbols-outlined text-base">today</span>
                                    </div>
                                    <span class="text-xs font-medium text-on-surface-variant">{{ __('auto.0455') }}</span>
                                </div>
                                <span class="text-xs font-bold {{ $item->daily_max ? 'text-text-primary' : 'text-on-surface-variant' }}">
                                    {{ $item->daily_max ? 'NT$ ' . number_format($item->daily_max) : '無限制' }}
                                </span>
                            </div>

                            <!-- 3. 每週上限 -->
                            <div class="flex items-center justify-between p-2.5 rounded-xl hover:bg-surface-container/50 transition-colors border border-transparent hover:border-border-base/50">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-category-orange/15 text-category-orange flex items-center justify-center flex-shrink-0">
                                        <span class="material-symbols-outlined text-base">calendar_view_week</span>
                                    </div>
                                    <span class="text-xs font-medium text-on-surface-variant">{{ __('auto.0462') }}</span>
                                </div>
                                <span class="text-xs font-bold {{ $item->weekly_max ? 'text-text-primary' : 'text-on-surface-variant' }}">
                                    {{ $item->weekly_max ? 'NT$ ' . number_format($item->weekly_max) : '無限制' }}
                                </span>
                            </div>

                            <!-- 4. 每月上限 -->
                            <div class="flex items-center justify-between p-2.5 rounded-xl hover:bg-surface-container/50 transition-colors border border-transparent hover:border-border-base/50">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-category-rose/15 text-category-rose flex items-center justify-center flex-shrink-0">
                                        <span class="material-symbols-outlined text-base">calendar_month</span>
                                    </div>
                                    <span class="text-xs font-medium text-on-surface-variant">{{ __('auto.0458') }}</span>
                                </div>
                                <span class="text-xs font-bold {{ $item->monthly_max ? 'text-text-primary' : 'text-on-surface-variant' }}">
                                    {{ $item->monthly_max ? 'NT$ ' . number_format($item->monthly_max) : '無限制' }}
                                </span>
                            </div>

                            <!-- 5. 比例上限 -->
                            @if($item->ratio_of_pocket)
                                <div class="flex items-center justify-between p-2.5 rounded-xl hover:bg-surface-container/50 transition-colors border border-transparent hover:border-border-base/50">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-lg bg-category-lavender/15 text-category-lavender flex items-center justify-center flex-shrink-0">
                                            <span class="material-symbols-outlined text-base">percent</span>
                                        </div>
                                        <span class="text-xs font-medium text-on-surface-variant">{{ __('auto.0721') }}</span>
                                    </div>
                                    <span class="text-xs font-bold text-primary">
                                        {{ round($item->ratio_of_pocket) }}%
                                    </span>
                                </div>
                            @endif
                        </div>

                        <!-- Date range tag -->
                        @if($item->effective_from || $item->effective_to)
                            <div class="mt-4 pt-3 border-t border-border-base/60 flex items-center gap-1.5 text-[11px] text-on-surface-variant">
                                <span class="material-symbols-outlined text-sm">schedule</span>
                                <span>有效期限: {{ $item->effective_from?->format('Y-m-d') ?? '即日起' }} 至 {{ $item->effective_to?->format('Y-m-d') ?? '永久有效' }}</span>
                            </div>
                        @endif
                    </div>

                    <!-- Action Buttons -->
                    @if($isParentOrAdmin && auth()->user()->canEditCurrentFamily())
                        <div class="mt-6 pt-4 border-t border-border-base flex items-center gap-2">
                            <button type="button" 
                                    @click="openEditModal(@js($child), @js($limit))" 
                                    class="flex-1 py-2.5 px-3 bg-surface-container-low hover:bg-primary hover:text-white text-text-primary font-bold text-xs rounded-xl border border-border-base transition-all duration-200 flex items-center justify-center gap-1.5 cursor-pointer shadow-sm">
                                <span class="material-symbols-outlined text-base">tune</span>
                                <span>{{ __('auto.0594') }}</span>
                            </button>

                            @if($hasLimit && $limit)
                                <form action="{{ route('child-limits.destroy', $limit->id) }}" method="POST" onsubmit="return confirm('確定要解除 {{ $child?->name }} 的所有消費限額嗎？');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="py-2.5 px-3 bg-danger/10 hover:bg-danger text-danger hover:text-white font-bold text-xs rounded-xl border border-danger/20 transition-all duration-200 flex items-center justify-center gap-1 cursor-pointer" 
                                            title="{{ __('auto.0620') }}">
                                        <span class="material-symbols-outlined text-base">lock_open</span>
                                        <span>{{ __('auto.0619') }}</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    @else
                        <div class="mt-4 pt-3 border-t border-border-base flex items-center justify-between text-xs text-on-surface-variant font-medium">
                            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm text-primary">security</span> 家長安全保護中</span>
                            <span class="text-primary font-bold">{{ __('auto.0260') }}</span>
                        </div>
                    @endif
                </div>
            @empty
                <div class="col-span-full bg-surface-pure border border-border-base rounded-2xl p-12 text-center shadow-sm">
                    <div class="w-16 h-16 rounded-full bg-primary/10 text-primary flex items-center justify-center mx-auto mb-4">
                        <span class="material-symbols-outlined text-3xl">child_care</span>
                    </div>
                    <h3 class="font-bold text-lg text-text-primary mb-1">{{ __('auto.0274') }}</h3>
                    <p class="text-sm text-on-surface-variant max-w-md mx-auto mb-6">
                        請先至「家庭成員管理」將家庭成員設定為兒童角色，即可在此為每位孩子設定專屬消費限額。
                    </p>
                    @if($isParentOrAdmin)
                        <a href="{{ route('members.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-white font-bold rounded-xl hover:bg-primary/90 transition-all text-sm">
                            <span class="material-symbols-outlined text-base">group_add</span>
                            <span>{{ __('auto.0207') }}</span>
                        </a>
                    @endif
                </div>
            @endforelse
        </div>

        @if($isParentOrAdmin && auth()->user()->canEditCurrentFamily())
        <!-- Alpine.js 編輯/設定限額 Modal (Google Stitch 4.4 表單規範) -->
        <div x-show="isModalOpen" 
             x-cloak 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-on-surface/40 backdrop-blur-sm"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            
            <div @click.outside="closeModal()" 
                 class="bg-surface-pure border border-border-base rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-2">
                
                <!-- Modal Header (Stitch 4.4 TopAppBar) -->
                <div class="sticky top-0 bg-surface-bright/95 backdrop-blur-md px-6 py-4 border-b border-border-base flex items-center justify-between z-10">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold">
                            <span class="material-symbols-outlined text-xl">tune</span>
                        </div>
                        <h2 class="font-bold text-lg text-text-primary" x-text="form.child_name ? '編輯範圍 — ' + form.child_name : '設定兒童消費限額'"></h2>
                    </div>
                    <button type="button" @click="closeModal()" class="w-8 h-8 rounded-lg hover:bg-surface-container flex items-center justify-center text-on-surface-variant transition-colors">
                        <span class="material-symbols-outlined text-xl">close</span>
                    </button>
                </div>

                <!-- Form Content -->
                <form action="{{ route('child-limits.store') }}" method="POST" class="p-6 space-y-6">
                    @csrf
                    <input type="hidden" name="child_user_id" :value="form.child_user_id">

                    <!-- Child Selector (If not pre-selected or creating new) -->
                    <template x-if="!form.is_editing">
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">{{ __('auto.0691') }}</label>
                            <select x-model="form.child_user_id" @change="onChildChange()" required class="w-full bg-surface-pure border border-border-base rounded-xl px-3.5 py-2.5 text-sm font-semibold text-text-primary focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary shadow-sm">
                                <option value="">-- 請選擇兒童 --</option>
                                @foreach($children as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }} (@ {{ $c->account }})</option>
                                @endforeach
                            </select>
                        </div>
                    </template>

                    <!-- Limit Sections (Stitch 4.4 Specification) -->
                    <div class="space-y-4">
                        <!-- 1. 單筆上限 -->
                        <div class="bg-surface-container-low rounded-xl p-4 border border-border-base">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-category-sky/15 text-category-sky flex items-center justify-center">
                                        <span class="material-symbols-outlined text-lg">shopping_bag</span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-sm text-text-primary">{{ __('limit.single') }}</h4>
                                        <p class="text-[11px] text-on-surface-variant">{{ __('auto.0240') }}</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="toggles.per_transaction" class="sr-only peer">
                                    <div class="w-11 h-6 bg-surface-variant peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-on-surface-variant/30 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                </label>
                            </div>
                            <div x-show="toggles.per_transaction" x-collapse>
                                <div class="relative mt-2">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-bold text-on-surface-variant">NT$</span>
                                    <input type="number" 
                                           name="per_transaction_max" 
                                           x-model="form.per_transaction_max" 
                                           min="0" 
                                           step="1" 
                                           placeholder="{{ __('auto.0119') }}" 
                                           class="w-full pl-12 pr-4 py-2 bg-surface-pure border border-border-base rounded-xl font-bold text-sm text-text-primary focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary shadow-sm">
                                </div>
                                <p class="text-[11px] text-on-surface-variant mt-1.5">{{ __('auto.0656') }}</p>
                            </div>
                        </div>

                        <!-- 2. 每日上限 -->
                        <div class="bg-surface-container-low rounded-xl p-4 border border-border-base">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-category-amber/15 text-category-amber flex items-center justify-center">
                                        <span class="material-symbols-outlined text-lg">today</span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-sm text-text-primary">{{ __('auto.0453') }}</h4>
                                        <p class="text-[11px] text-on-surface-variant">{{ __('auto.0076') }}</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="toggles.daily" class="sr-only peer">
                                    <div class="w-11 h-6 bg-surface-variant peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-on-surface-variant/30 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                </label>
                            </div>
                            <div x-show="toggles.daily" x-collapse>
                                <div class="relative mt-2">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-bold text-on-surface-variant">NT$</span>
                                    <input type="number" 
                                           name="daily_max" 
                                           x-model="form.daily_max" 
                                           min="0" 
                                           step="1" 
                                           placeholder="{{ __('auto.0120') }}" 
                                           class="w-full pl-12 pr-4 py-2 bg-surface-pure border border-border-base rounded-xl font-bold text-sm text-text-primary focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary shadow-sm">
                                </div>
                            </div>
                        </div>

                        <!-- 3. 週期累積上限 (週 / 月) -->
                        <div class="bg-surface-container-low rounded-xl p-4 border border-border-base space-y-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-category-orange/15 text-category-orange flex items-center justify-center">
                                    <span class="material-symbols-outlined text-lg">calendar_month</span>
                                </div>
                                <h4 class="font-bold text-sm text-text-primary">{{ __('auto.0682') }}</h4>
                            </div>

                            <!-- Weekly -->
                            <div class="pl-2 space-y-2 border-l-2 border-primary/20">
                                <div class="flex items-center justify-between gap-3">
                                    <label class="flex items-center gap-2 text-xs font-semibold text-text-primary cursor-pointer">
                                        <input type="checkbox" x-model="toggles.weekly" class="rounded text-primary focus:ring-primary border-border-base">
                                        <span>{{ __('auto.0461') }}</span>
                                    </label>
                                    <div class="relative w-36" x-show="toggles.weekly">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 text-xs font-bold text-on-surface-variant">NT$</span>
                                        <input type="number" 
                                               name="weekly_max" 
                                               x-model="form.weekly_max" 
                                               min="0" 
                                               step="1" 
                                               placeholder="500" 
                                               class="w-full pl-9 pr-3 py-1.5 bg-surface-pure border border-border-base rounded-lg text-xs font-bold text-text-primary focus:outline-none focus:border-primary">
                                    </div>
                                </div>

                                <!-- Monthly -->
                                <div class="flex items-center justify-between gap-3 pt-2">
                                    <label class="flex items-center gap-2 text-xs font-semibold text-text-primary cursor-pointer">
                                        <input type="checkbox" x-model="toggles.monthly" class="rounded text-primary focus:ring-primary border-border-base">
                                        <span>{{ __('auto.0456') }}</span>
                                    </label>
                                    <div class="relative w-36" x-show="toggles.monthly">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 text-xs font-bold text-on-surface-variant">NT$</span>
                                        <input type="number" 
                                               name="monthly_max" 
                                               x-model="form.monthly_max" 
                                               min="0" 
                                               step="1" 
                                               placeholder="2000" 
                                               class="w-full pl-9 pr-3 py-1.5 bg-surface-pure border border-border-base rounded-lg text-xs font-bold text-text-primary focus:outline-none focus:border-primary">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4. 零用錢比例上限 (Slider) -->
                        <div class="bg-surface-container-low rounded-xl p-4 border border-border-base">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-category-lavender/15 text-category-lavender flex items-center justify-center">
                                        <span class="material-symbols-outlined text-lg">percent</span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-sm text-text-primary">{{ __('auto.0722') }}</h4>
                                        <p class="text-[11px] text-on-surface-variant">{{ __('auto.0402') }}</p>
                                    </div>
                                </div>
                                <span class="font-mono font-bold text-sm text-primary" x-text="(form.ratio_of_pocket || 100) + '%'"></span>
                            </div>
                            <div class="mt-2">
                                <input type="range" 
                                       name="ratio_of_pocket" 
                                       x-model="form.ratio_of_pocket" 
                                       min="10" 
                                       max="100" 
                                       step="5" 
                                       class="w-full h-2 bg-surface-variant rounded-lg appearance-none cursor-pointer accent-primary">
                                <div class="flex justify-between text-[10px] text-on-surface-variant mt-1">
                                    <span>{{ __('auto.0243') }}</span>
                                    <span>{{ __('auto.0324') }}</span>
                                    <span>{{ __('auto.0178') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- 5. 有效期限 -->
                        <div class="bg-surface-container-low rounded-xl p-4 border border-border-base">
                            <h4 class="font-bold text-sm text-text-primary mb-2 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-base text-primary">date_range</span>
                                <span>{{ __('auto.0409') }}</span>
                            </h4>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] text-on-surface-variant mb-1">{{ __('field.start_date') }}</label>
                                    <input type="date" 
                                           name="effective_from" 
                                           x-model="form.effective_from" 
                                           class="w-full bg-surface-pure border border-border-base rounded-lg px-3 py-1.5 text-xs text-text-primary focus:outline-none focus:border-primary">
                                </div>
                                <div>
                                    <label class="block text-[11px] text-on-surface-variant mb-1">{{ __('field.end_date') }}</label>
                                    <input type="date" 
                                           name="effective_to" 
                                           x-model="form.effective_to" 
                                           class="w-full bg-surface-pure border border-border-base rounded-lg px-3 py-1.5 text-xs text-text-primary focus:outline-none focus:border-primary">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Action Buttons (Stitch 4.4 Fixed Action Bar) -->
                    <div class="pt-4 border-t border-border-base flex gap-3">
                        <button type="button" 
                                @click="closeModal()" 
                                class="flex-1 py-2.5 px-4 rounded-xl border border-border-base font-bold text-sm text-on-surface-variant hover:bg-surface-container transition-colors">{{ __('common.cancel') }}</button>
                        <button type="submit" 
                                class="flex-1 py-2.5 px-4 rounded-xl bg-primary font-bold text-sm text-on-primary hover:bg-primary/90 transition-all shadow-sm active:scale-95">
                            儲存限額設定
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    </div>

    <script>
        function childLimitsPage() {
            return {
                isModalOpen: false,
                childrenList: @js($children ?? []),
                toggles: {
                    per_transaction: true,
                    daily: true,
                    weekly: true,
                    monthly: true,
                },
                form: {
                    is_editing: false,
                    child_user_id: '',
                    child_name: '',
                    per_transaction_max: '',
                    daily_max: '',
                    weekly_max: '',
                    monthly_max: '',
                    ratio_of_pocket: 80,
                    effective_from: '',
                    effective_to: '',
                },
                openCreateModal() {
                    this.form = {
                        is_editing: false,
                        child_user_id: this.childrenList.length > 0 ? this.childrenList[0].id : '',
                        child_name: this.childrenList.length > 0 ? this.childrenList[0].name : '',
                        per_transaction_max: '200',
                        daily_max: '300',
                        weekly_max: '500',
                        monthly_max: '2000',
                        ratio_of_pocket: 80,
                        effective_from: new Date().toISOString().slice(0, 10),
                        effective_to: '',
                    };
                    this.toggles.per_transaction = true;
                    this.toggles.daily = true;
                    this.toggles.weekly = true;
                    this.toggles.monthly = true;
                    this.isModalOpen = true;
                },
                openEditModal(child, limit) {
                    this.form = {
                        is_editing: true,
                        child_user_id: child ? child.id : '',
                        child_name: child ? child.name : '',
                        per_transaction_max: limit && limit.per_transaction_max ? limit.per_transaction_max : '',
                        daily_max: limit && limit.daily_max ? limit.daily_max : '',
                        weekly_max: limit && limit.weekly_max ? limit.weekly_max : '',
                        monthly_max: limit && limit.monthly_max ? limit.monthly_max : '',
                        ratio_of_pocket: limit && limit.ratio_of_pocket ? limit.ratio_of_pocket : 80,
                        effective_from: limit && limit.effective_from ? limit.effective_from.substring(0, 10) : '',
                        effective_to: limit && limit.effective_to ? limit.effective_to.substring(0, 10) : '',
                    };
                    this.toggles.per_transaction = Boolean(this.form.per_transaction_max);
                    this.toggles.daily = Boolean(this.form.daily_max);
                    this.toggles.weekly = Boolean(this.form.weekly_max);
                    this.toggles.monthly = Boolean(this.form.monthly_max);
                    this.isModalOpen = true;
                },
                onChildChange() {
                    const c = this.childrenList.find(item => item.id == this.form.child_user_id);
                    if (c) {
                        this.form.child_name = c.name;
                    }
                },
                closeModal() {
                    this.isModalOpen = false;
                }
            };
        }
    </script>
</x-app-layout>
