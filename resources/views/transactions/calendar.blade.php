<x-app-layout>
    <x-slot name="title">{{ __('auto.0101') }}</x-slot>

    <script>window.__dailyData = @json($dailyData);</script>

    <div x-data="{
    selectedDay: null,
    showDayDetail: false,
    dailyData: window.__dailyData || {},
    formatDate(s) {
        if (!s) return '';
        const parts = s.split('-');
        return parts[0] + ' 年 ' + parseInt(parts[1], 10) + ' 月 ' + parseInt(parts[2], 10) + ' 日';
    },
    formatAmount(n) {
        if (n === null || n === undefined || isNaN(n)) return '0';
        return Math.round(Number(n)).toLocaleString('en-US');
    }
}" class="min-h-screen bg-background-warm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            {{-- 頁面標題和月份切換 --}}
            <div class="bg-surface-pure rounded-2xl p-6 shadow-sm border border-border-base">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-primary/10 border border-primary/20 rounded-2xl flex items-center justify-center text-primary">
                            <span class="material-symbols-outlined text-3xl">calendar_month</span>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-on-surface">{{ $currentMonth->format('Y 年 m 月') }}</h1>
                            <p class="text-sm text-on-surface-variant mt-0.5">{{ __('auto.0196') }}</p>
                        </div>
                    </div>

                    {{-- 月份切換 --}}
                    <div class="flex items-center gap-2">
                        <a href="{{ route('transactions.calendar', ['month' => $prevMonth->format('Y-m')]) }}"
                           class="w-10 h-10 flex items-center justify-center bg-surface-container-low hover:bg-surface-container rounded-xl text-on-surface transition-colors">
                            <span class="material-symbols-outlined">chevron_left</span>
                        </a>
                        <a href="{{ route('transactions.calendar') }}"
                           class="px-4 py-2 bg-surface-container-low hover:bg-surface-container rounded-xl text-sm font-bold text-on-surface transition-colors">{{ __('time.this_month') }}</a>
                        <a href="{{ route('transactions.calendar', ['month' => $nextMonth->format('Y-m')]) }}"
                           class="w-10 h-10 flex items-center justify-center bg-surface-container-low hover:bg-surface-container rounded-xl text-on-surface transition-colors">
                            <span class="material-symbols-outlined">chevron_right</span>
                        </a>
                    </div>
                </div>

                {{-- 月份統計 --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6">
                    <div class="bg-surface-container-low rounded-xl p-3">
                        <div class="text-xs text-on-surface-variant">本月收入</div>
                        <div class="font-bold text-lg text-success mt-1">
                            {{ number_format($monthStats['income'], 0) }}
                        </div>
                    </div>
                    <div class="bg-surface-container-low rounded-xl p-3">
                        <div class="text-xs text-on-surface-variant">本月支出</div>
                        <div class="font-bold text-lg text-danger mt-1">
                            {{ number_format($monthStats['expense'], 0) }}
                        </div>
                    </div>
                    <div class="bg-surface-container-low rounded-xl p-3">
                        <div class="text-xs text-on-surface-variant">本月淨額</div>
                        <div class="font-bold text-lg mt-1 {{ $monthStats['net'] >= 0 ? 'text-primary' : 'text-danger' }}">
                            {{ number_format($monthStats['net'], 0) }}
                        </div>
                    </div>
                    <div class="bg-surface-container-low rounded-xl p-3">
                        <div class="text-xs text-on-surface-variant">交易筆數</div>
                        <div class="font-bold text-lg text-on-surface mt-1">
                            {{ $monthStats['count'] }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- 月曆主體 --}}
            <div class="bg-surface-pure rounded-2xl shadow-sm border border-border-base overflow-hidden">
                {{-- 星期標題 --}}
                <div class="grid grid-cols-7 bg-surface-container-low border-b border-border-base">
                    @foreach(['日','一','二','三','四','五','六'] as $idx => $weekday)
                        <div class="py-3 text-center text-xs font-bold {{ $idx === 0 ? 'text-danger' : ($idx === 6 ? 'text-primary' : 'text-on-surface-variant') }}">
                            周{{ $weekday }}
                        </div>
                    @endforeach
                </div>

                {{-- 月曆格子 --}}
                <div class="grid grid-cols-7">
                    @php
                        $firstDay = $currentMonth->copy()->startOfMonth();
                        $lastDay = $currentMonth->copy()->endOfMonth();
                        $firstDayWeekday = $firstDay->dayOfWeek; // 0=Sun, 6=Sat
                        $daysInMonth = $currentMonth->daysInMonth;
                        $today = now()->format('Y-m-d');
                        $cellIndex = 0;
                    @endphp

                    {{-- 上月補空格 --}}
                    @for($i = 0; $i < $firstDayWeekday; $i++)
                        <div class="aspect-square bg-surface-container-low/30 border-r border-b border-border-base last:border-r-0"></div>
                        @php $cellIndex++; @endphp
                    @endfor

                    {{-- 本月日期 --}}
                    @for($day = 1; $day <= $daysInMonth; $day++)
                        @php
                            $dateKey = $currentMonth->copy()->day($day)->format('Y-m-d');
                            $dayData = $dailyData[$dateKey] ?? ['income' => 0, 'expense' => 0, 'count' => 0, 'transactions' => collect()];
                            $hasData = $dayData['count'] > 0;
                            $isToday = $dateKey === $today;
                        @endphp

                        <div class="aspect-square border-r border-b border-border-base last:border-r-0 p-2 {{ $isToday ? 'bg-primary/5 ring-2 ring-primary ring-inset' : 'bg-surface-pure hover:bg-surface-container-low/50' }} transition-colors cursor-pointer overflow-hidden"
                             @click="selectedDay = '{{ $dateKey }}'; showDayDetail = true">
                            <div class="flex flex-col h-full">
                                <div class="flex items-start justify-between">
                                    <span class="text-sm font-bold {{ $isToday ? 'text-primary' : 'text-on-surface' }}">
                                        {{ $day }}
                                    </span>
                                    @if($hasData)
                                        <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                                    @endif
                                </div>

                                @if($hasData)
                                    <div class="mt-1 space-y-0.5 text-xs">
                                        @if($dayData['income'] > 0)
                                            <div class="text-success font-mono">+{{ number_format($dayData['income'], 0) }}</div>
                                        @endif
                                        @if($dayData['expense'] > 0)
                                            <div class="text-danger font-mono">-{{ number_format($dayData['expense'], 0) }}</div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                        @php $cellIndex++; @endphp
                    @endfor

                    {{-- 下月補空格 --}}
                    @php
                        $totalCells = $firstDayWeekday + $daysInMonth;
                        $remainingCells = (7 - ($totalCells % 7)) % 7;
                    @endphp
                    @for($i = 0; $i < $remainingCells; $i++)
                        <div class="aspect-square bg-surface-container-low/30 border-r border-b border-border-base last:border-r-0"></div>
                    @endfor
                </div>
            </div>

                        {{-- 當日明細 Modal --}}
            <div x-show="showDayDetail" x-cloak
                 @click.self="showDayDetail = false"
                 @keydown.escape.window="showDayDetail = false"
                 class="fixed inset-0 z-50 bg-on-surface/50 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="bg-surface-pure rounded-2xl shadow-2xl max-w-2xl w-full max-h-[80vh] overflow-hidden flex flex-col"
                     @click.stop>
                    <div class="flex items-center justify-between p-6 border-b border-border-base">
                        <div>
                            <h3 class="text-xl font-bold text-on-surface"
                                x-text="selectedDay ? formatDate(selectedDay) : ''"></h3>
                            <p class="text-sm text-on-surface-variant mt-0.5">
                                <template x-if="selectedDay && dailyData[selectedDay]">
                                    <span>
                                        收入 <span class="text-success font-bold" x-text="'+' + formatAmount(dailyData[selectedDay].income)"></span>
                                        支出 <span class="text-danger font-bold" x-text="'-' + formatAmount(dailyData[selectedDay].expense)"></span>
                                        <span class="ml-2" x-text="dailyData[selectedDay].count + ' 筆'"></span>
                                    </span>
                                </template>
                                <template x-if="!selectedDay || !dailyData[selectedDay]">
                                    <span>{{ __('auto.0477') }}</span>
                                </template>
                            </p>
                        </div>
                        <button @click="showDayDetail = false"
                                class="w-10 h-10 flex items-center justify-center bg-surface-container-low hover:bg-surface-container rounded-xl text-on-surface-variant transition-colors">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>

                    <div class="overflow-y-auto p-6 space-y-3">
                        <template x-if="selectedDay && dailyData[selectedDay] && dailyData[selectedDay].transactions.length > 0">
                            <div>
                                <template x-for="tx in dailyData[selectedDay].transactions" :key="tx.id">
                                    <div class="flex items-start gap-3 p-3 bg-surface-container-low rounded-xl">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                                             :class="tx.type === 'expense' ? 'bg-danger/15 text-danger' : 'bg-success/15 text-success'">
                                            <span class="material-symbols-outlined text-xl"
                                                  x-text="tx.type === 'expense' ? 'trending_down' : 'trending_up'"></span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="font-bold text-on-surface truncate"
                                                     x-text="tx.category_name || tx.type_custom || '未分類'"></div>
                                                <div class="font-mono font-bold text-base whitespace-nowrap"
                                                     :class="tx.type === 'expense' ? 'text-danger' : 'text-success'">
                                                    <span x-text="(tx.type === 'expense' ? '-' : '+') + ' NT$ ' + formatAmount(tx.amount)"></span>
                                                </div>
                                            </div>
                                            <template x-if="tx.payee_name || tx.description">
                                                <div class="text-xs text-on-surface-variant mt-0.5 truncate">
                                                    <template x-if="tx.payee_name">
                                                        <span>
                                                            <span class="material-symbols-outlined text-xs align-middle">person</span>
                                                            <span x-text="tx.payee_name"></span>
                                                        </span>
                                                    </template>
                                                    <template x-if="tx.description">
                                                        <span x-text="' ' + tx.description"></span>
                                                    </template>
                                                </div>
                                            </template>
                                            <div class="text-xs text-on-surface-variant mt-0.5">
                                                <span class="material-symbols-outlined text-xs align-middle">schedule</span>
                                                <span x-text="tx.occurred_time"></span>
                                                <template x-if="tx.account_name">
                                                    <span>
                                                        · <span class="material-symbols-outlined text-xs align-middle">account_balance_wallet</span>
                                                        <span x-text="tx.account_name"></span>
                                                    </span>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="!selectedDay || !dailyData[selectedDay] || dailyData[selectedDay].transactions.length === 0">
                            <div class="text-center text-on-surface-variant py-8">
                                <span class="material-symbols-outlined text-5xl block mb-2">event_busy</span>
                                這一天沒有任何交易紀錄
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- 圖例 --}}
            <div class="bg-surface-pure rounded-2xl p-6 shadow-sm border border-border-base">
                <div class="flex items-center gap-2 mb-3">
                    <span class="material-symbols-outlined text-primary text-xl">info</span>
                    <h2 class="text-sm font-bold text-on-surface">{{ __('auto.0247') }}</h2>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-primary"></span>
                        <span>{{ __('auto.0408') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded bg-primary/20 ring-2 ring-primary"></span>
                        <span>{{ __('date.today') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-success">+1000</span>
                        <span>{{ __('auto.0499') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-danger">-500</span>
                        <span>{{ __('auto.0498') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>