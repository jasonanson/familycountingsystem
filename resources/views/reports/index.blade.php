@extends('layouts.app')

@section('content')
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="space-y-6">
    <!-- Header & Month Filter Controls -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-3xl">analytics</span>
                </div>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-primary tracking-tight">{{ __('auto.0645') }}</h1>
                    <p class="text-sm text-on-surface-variant font-medium">{{ __('auto.0251') }}</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 bg-surface-pure p-2 rounded-2xl border border-border-base shadow-sm">
            <form action="{{ route('reports.index') }}" method="GET" class="flex items-center gap-2">
                <label for="monthSelect" class="text-xs font-bold text-on-surface-variant pl-2 flex items-center gap-1">
                    <span class="material-symbols-outlined text-base text-primary">calendar_today</span>
                    選擇月份：
                </label>
                <input type="month" 
                       id="monthSelect" 
                       name="month" 
                       value="{{ $selectedMonth }}" 
                       onchange="this.form.submit()" 
                       class="bg-surface-container border border-border-base text-on-surface text-sm rounded-xl px-3 py-1.5 font-bold focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary cursor-pointer">
            </form>

            <a href="{{ route('reports.monthly', ['month' => $selectedMonth]) }}" 
               class="px-3.5 py-1.5 bg-primary hover:bg-primary/90 text-white rounded-xl text-xs font-bold transition-all shadow-sm flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">calendar_month</span>
                月度詳細分析
            </a>
        </div>
    </div>

    <!-- 頂部數據摘要卡片 (Top Summary Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- 本月總收入 -->
        <div class="bg-surface-pure rounded-2xl border border-border-base p-5 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="absolute -right-3 -top-3 w-20 h-20 bg-success/10 rounded-full blur-lg group-hover:scale-125 transition-transform"></div>
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold tracking-wider text-on-surface-variant uppercase">{{ __('auto.0423') }}</span>
                <span class="w-9 h-9 rounded-xl bg-success/10 text-success flex items-center justify-center">
                    <span class="material-symbols-outlined text-xl">trending_up</span>
                </span>
            </div>
            <div class="text-2xl sm:text-3xl font-black text-on-surface tracking-tight mb-2">
                <span class="text-base font-semibold text-on-surface-variant/70">$NT</span> {{ number_format($monthlyIncome) }}
            </div>
            <div class="flex items-center gap-1.5 text-xs text-success font-bold">
                <span class="material-symbols-outlined text-sm">arrow_upward</span>
                <span>{{ __('auto.0372') }}</span>
            </div>
        </div>

        <!-- 本月總支出 -->
        <div class="bg-surface-pure rounded-2xl border border-border-base p-5 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="absolute -right-3 -top-3 w-20 h-20 bg-danger/10 rounded-full blur-lg group-hover:scale-125 transition-transform"></div>
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold tracking-wider text-on-surface-variant uppercase">{{ __('auto.0422') }}</span>
                <span class="w-9 h-9 rounded-xl bg-danger/10 text-danger flex items-center justify-center">
                    <span class="material-symbols-outlined text-xl">trending_down</span>
                </span>
            </div>
            <div class="text-2xl sm:text-3xl font-black text-on-surface tracking-tight mb-2">
                <span class="text-base font-semibold text-on-surface-variant/70">$NT</span> {{ number_format($monthlyExpense) }}
            </div>
            <div class="flex items-center gap-1.5 text-xs text-danger font-bold">
                <span class="material-symbols-outlined text-sm">arrow_downward</span>
                <span>{{ __('auto.0491') }}</span>
            </div>
        </div>

        <!-- 當月結餘 -->
        <div class="bg-surface-pure rounded-2xl border border-border-base p-5 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="absolute -right-3 -top-3 w-20 h-20 bg-primary/10 rounded-full blur-lg group-hover:scale-125 transition-transform"></div>
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold tracking-wider text-on-surface-variant uppercase">{{ __('auto.0503') }}</span>
                <span class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-xl">account_balance_wallet</span>
                </span>
            </div>
            <div class="text-2xl sm:text-3xl font-black {{ $netBalance >= 0 ? 'text-primary' : 'text-danger' }} tracking-tight mb-2">
                <span class="text-base font-semibold text-on-surface-variant/70">$NT</span> {{ number_format($netBalance) }}
            </div>
            <div class="flex items-center gap-1.5 text-xs font-bold {{ $netBalance >= 0 ? 'text-primary' : 'text-danger' }}">
                <span class="material-symbols-outlined text-sm">{{ $netBalance >= 0 ? 'check_circle' : 'warning' }}</span>
                <span>{{ $netBalance >= 0 ? '收支平衡良好' : '支出超出收入' }}</span>
            </div>
        </div>

        <!-- 儲蓄率 -->
        <div class="bg-surface-pure rounded-2xl border border-border-base p-5 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="absolute -right-3 -top-3 w-20 h-20 bg-warning/10 rounded-full blur-lg group-hover:scale-125 transition-transform"></div>
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold tracking-wider text-on-surface-variant uppercase">{{ __('auto.0500') }}</span>
                <span class="w-9 h-9 rounded-xl bg-warning/10 text-warning flex items-center justify-center">
                    <span class="material-symbols-outlined text-xl">savings</span>
                </span>
            </div>
            <div class="text-2xl sm:text-3xl font-black text-on-surface tracking-tight mb-2">
                {{ $savingsRate }}<span class="text-lg font-bold text-warning ml-0.5">%</span>
            </div>
            <!-- Progress Bar -->
            <div class="w-full bg-surface-container rounded-full h-2 overflow-hidden border border-border-base">
                <div class="bg-warning h-2 rounded-full transition-all duration-500" style="width: {{ min(100, max(0, $savingsRate)) }}%"></div>
            </div>
        </div>
    </div>

    <!-- Main Visualizations Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- 圖表 1: 月度收支趨勢折線圖 - Occupies 2 Columns -->
        <div class="lg:col-span-2 bg-surface-pure rounded-2xl border border-border-base p-6 shadow-sm">
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-border-base">
                <div>
                    <h2 class="text-lg font-bold text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">show_chart</span>
                        近 6 個月收支趨勢
                    </h2>
                    <p class="text-xs text-on-surface-variant font-medium mt-0.5">{{ __('auto.0323') }}</p>
                </div>
                <div class="flex items-center gap-3 text-xs font-bold">
                    <span class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-success"></span> 收入
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-danger"></span> 支出
                    </span>
                </div>
            </div>
            <div class="h-72 w-full relative">
                <canvas id="trendLineChart"></canvas>
            </div>
        </div>

        <!-- 圖表 2: 分類支出比例環狀圖 - Occupies 1 Column -->
        <div class="bg-surface-pure rounded-2xl border border-border-base p-6 shadow-sm flex flex-col justify-between">
            <div>
                <div class="mb-4 pb-3 border-b border-border-base">
                    <h2 class="text-lg font-bold text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">pie_chart</span>
                        分類支出比例
                    </h2>
                    <p class="text-xs text-on-surface-variant font-medium mt-0.5">各消費類別支出佔比統計</p>
                </div>
                <div class="h-56 w-full relative flex items-center justify-center">
                    <canvas id="categoryDoughnutChart"></canvas>
                </div>
            </div>

            <!-- Custom Stitch Color Legend Badges -->
            <div class="grid grid-cols-4 gap-2 mt-4 pt-3 border-t border-border-base text-[11px] font-bold text-on-surface-variant">
                @foreach($stitchCategories as $name => $color)
                    <div class="flex items-center gap-1.5 truncate">
                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $color }};"></span>
                        <span class="truncate">{{ $name }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- 圖表 3: 家庭成員消費金額柱狀圖 -->
    <div class="bg-surface-pure rounded-2xl border border-border-base p-6 shadow-sm">
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-border-base">
            <div>
                <h2 class="text-lg font-bold text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">bar_chart</span>
                    家庭成員消費金額分析
                </h2>
                <p class="text-xs text-on-surface-variant font-medium mt-0.5">{{ __('auto.0463') }}</p>
            </div>
            <span class="text-xs font-semibold px-3 py-1 bg-surface-container border border-border-base rounded-full text-on-surface-variant">
                {{ count($memberExpenses) }} 位成員紀錄
            </span>
        </div>
        <div class="h-64 w-full relative">
            <canvas id="memberBarChart"></canvas>
        </div>
    </div>
</div>

<!-- Chart.js Initialization Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 全域 Chart.js 設定
    Chart.defaults.font.family = "'Microsoft JhengHei', sans-serif";
    Chart.defaults.color = '#57534E';

    // 1. 圖表 1: 月度收支趨勢折線圖 (Line Chart)
    const trendCtx = document.getElementById('trendLineChart').getContext('2d');
    
    // Gradient Backgrounds
    const incomeGradient = trendCtx.createLinearGradient(0, 0, 0, 300);
    incomeGradient.addColorStop(0, 'rgba(16, 185, 129, 0.25)');
    incomeGradient.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

    const expenseGradient = trendCtx.createLinearGradient(0, 0, 0, 300);
    expenseGradient.addColorStop(0, 'rgba(239, 68, 68, 0.25)');
    expenseGradient.addColorStop(1, 'rgba(239, 68, 68, 0.0)');

    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: @js($trendLabels),
            datasets: [
                {
                    label: '收入 (Income)',
                    data: @js($trendIncome),
                    borderColor: '#10B981',
                    backgroundColor: incomeGradient,
                    fill: true,
                    tension: 0.4,
                    cubicInterpolationMode: 'monotone',
                    borderWidth: 3,
                    pointBackgroundColor: '#10B981',
                    pointBorderColor: '#FFFFFF',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                },
                {
                    label: '支出 (Expense)',
                    data: @js($trendExpense),
                    borderColor: '#EF4444',
                    backgroundColor: expenseGradient,
                    fill: true,
                    tension: 0.4,
                    cubicInterpolationMode: 'monotone',
                    borderWidth: 3,
                    pointBackgroundColor: '#EF4444',
                    pointBorderColor: '#FFFFFF',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#1C1917',
                    padding: 12,
                    cornerRadius: 10,
                    callbacks: {
                        label: function(context) {
                            return ' ' + context.dataset.label + ': $NT ' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: { size: 12, weight: 'bold' }
                    }
                },
                y: {
                    grid: {
                        color: '#F5F5F4'
                    },
                    ticks: {
                        callback: function(val) { return '$' + val.toLocaleString(); },
                        font: { size: 11 }
                    }
                }
            }
        }
    });

    // 2. 圖表 2: 八大分類支出比例環狀圖 (Doughnut Chart)
    const catCtx = document.getElementById('categoryDoughnutChart').getContext('2d');
    const stitchColors = @js(array_values($stitchCategories));
    const catData = @js(array_values($categoryAmounts));
    const catLabels = @js(array_keys($categoryAmounts));

    new Chart(catCtx, {
        type: 'doughnut',
        data: {
            labels: catLabels,
            datasets: [{
                data: catData,
                backgroundColor: stitchColors,
                borderWidth: 2,
                borderColor: '#FFFFFF',
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1C1917',
                    padding: 12,
                    cornerRadius: 10,
                    callbacks: {
                        label: function(context) {
                            const value = context.parsed;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return ` ${context.label}: $NT ${value.toLocaleString()} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });

    // 3. 圖表 3: 家庭成員消費金額柱狀圖 (Bar Chart)
    const memberCtx = document.getElementById('memberBarChart').getContext('2d');
    const memberLabels = @js(array_keys($memberExpenses));
    const memberValues = @js(array_values($memberExpenses));

    new Chart(memberCtx, {
        type: 'bar',
        data: {
            labels: memberLabels,
            datasets: [{
                label: '消費金額',
                data: memberValues,
                backgroundColor: '#006b5f',
                hoverBackgroundColor: '#00574d',
                borderRadius: 8,
                barThickness: 36
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1C1917',
                    padding: 12,
                    cornerRadius: 10,
                    callbacks: {
                        label: function(context) {
                            return ' 消費總額: $NT ' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 12, weight: 'bold' } }
                },
                y: {
                    grid: { color: '#F5F5F4' },
                    ticks: {
                        callback: function(val) { return '$' + val.toLocaleString(); },
                        font: { size: 11 }
                    }
                }
            }
        }
    });
});
</script>
@endsection
