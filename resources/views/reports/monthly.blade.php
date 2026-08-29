@extends('layouts.app')

@section('content')
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="space-y-6">
    <!-- Navigation Header & Month Picker -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('reports.index', ['month' => $selectedMonth]) }}" 
               class="w-10 h-10 rounded-2xl bg-surface-pure border border-border-base text-on-surface-variant flex items-center justify-center hover:bg-surface-container hover:text-primary transition-colors shadow-sm"
               title="{{ __('auto.0671') }}">
                <span class="material-symbols-outlined text-xl">arrow_back</span>
            </a>
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-primary tracking-tight flex items-center gap-2">
                    <span>{{ $currentDate->format('Y 年 m 月') }} 月度詳細財務分析</span>
                </h1>
                <p class="text-sm text-on-surface-variant font-medium">{{ __('auto.0239') }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3 bg-surface-pure p-2 rounded-2xl border border-border-base shadow-sm">
            @php
                $prevMonth = $currentDate->copy()->subMonth()->format('Y-m');
                $nextMonth = $currentDate->copy()->addMonth()->format('Y-m');
            @endphp
            
            <a href="{{ route('reports.monthly', ['month' => $prevMonth]) }}" 
               class="p-2 rounded-xl bg-surface-container hover:bg-surface-container-high border border-border-base text-on-surface-variant transition-colors"
               title="{{ __('auto.0083') }}">
                <span class="material-symbols-outlined text-base">chevron_left</span>
            </a>

            <form action="{{ route('reports.monthly') }}" method="GET" class="flex items-center gap-2">
                <input type="month" 
                       name="month" 
                       value="{{ $selectedMonth }}" 
                       onchange="this.form.submit()" 
                       class="bg-surface-container border border-border-base text-on-surface text-sm rounded-xl px-3 py-1.5 font-bold focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary cursor-pointer">
            </form>

            <a href="{{ route('reports.monthly', ['month' => $nextMonth]) }}" 
               class="p-2 rounded-xl bg-surface-container hover:bg-surface-container-high border border-border-base text-on-surface-variant transition-colors"
               title="{{ __('auto.0085') }}">
                <span class="material-symbols-outlined text-base">chevron_right</span>
            </a>

            <a href="{{ route('export.csv', ['month' => $selectedMonth]) }}" 
               class="px-3.5 py-1.5 bg-primary hover:bg-primary/90 text-white rounded-xl text-xs font-bold transition-all shadow-sm flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">download</span>
                匯出 CSV
            </a>
            <a href="{{ route('reports.export.pdf.monthly', ['month' => $selectedMonth]) }}" 
               target="_blank"
               class="px-3.5 py-1.5 bg-danger hover:bg-danger/90 text-white rounded-xl text-xs font-bold transition-all shadow-sm flex items-center gap-1"
               title="PDF 月報（瀏覽器列印模式，按 Ctrl+P 另存 PDF）">
                <span class="material-symbols-outlined text-sm">picture_as_pdf</span>
                匯出 PDF
            </a>
            <button type="button" 
                    onclick="triggerAiMonthlyAnalysis()"
                    class="px-3.5 py-1.5 bg-gradient-to-r from-primary to-teal-500 hover:from-primary/90 hover:to-teal-600 text-white rounded-xl text-xs font-bold transition-all shadow-sm flex items-center gap-1 cursor-pointer">
                <span class="material-symbols-outlined text-sm animate-pulse">neurology</span>
                <span>AI 財務健檢</span>
            </button>
        </div>
    </div>

    {{-- AI 智慧分析展示卡片 (動態呈現) --}}
    <div id="aiAnalysisCard" style="display: none;" class="bg-surface-pure border-2 border-primary/40 rounded-2xl p-6 shadow-md relative overflow-hidden">
        <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-full blur-2xl pointer-events-none"></div>
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-border-base">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-xl">neurology</span>
                </div>
                <div>
                    <h3 class="text-base font-bold text-on-surface flex items-center gap-2">
                        <span>Gemini AI 財務健檢報告</span>
                        <span class="text-[10px] bg-primary/15 text-primary px-2 py-0.5 rounded-full font-bold">{{ __('auto.0400') }}</span>
                    </h3>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('aiAnalysisCard').style.display='none'" class="text-on-surface-variant hover:text-on-surface p-1 rounded-lg">
                <span class="material-symbols-outlined text-base">close</span>
            </button>
        </div>

        <div id="aiAnalysisLoading" class="py-8 text-center space-y-3">
            <div class="inline-block animate-spin text-primary">
                <span class="material-symbols-outlined text-4xl">sync</span>
            </div>
            <p class="text-sm font-bold text-on-surface">Gemini AI 正在深度分析您的 {{ $selectedMonth }} 月度財務數據...</p>
            <p class="text-xs text-on-surface-variant">{{ __('auto.0440') }}</p>
        </div>

        <div id="aiAnalysisContent" style="display: none;" class="text-sm text-on-surface leading-relaxed whitespace-pre-line prose dark:prose-invert max-w-none">
        </div>
    </div>

    <!-- 頂部數據 KPI 摘要卡片 -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- 總收入 -->
        <div class="bg-surface-pure rounded-2xl border border-border-base p-5 shadow-sm">
            <div class="text-xs font-bold text-on-surface-variant uppercase mb-1">當月總收入</div>
            <div class="text-2xl font-black text-success tracking-tight">
                $NT {{ number_format($monthlyIncome) }}
            </div>
            <div class="text-xs text-on-surface-variant/70 mt-2 flex items-center gap-1">
                <span class="material-symbols-outlined text-sm text-success">trending_up</span>
                入帳交易筆數統計
            </div>
        </div>

        <!-- 總支出 -->
        <div class="bg-surface-pure rounded-2xl border border-border-base p-5 shadow-sm">
            <div class="text-xs font-bold text-on-surface-variant uppercase mb-1">當月總支出</div>
            <div class="text-2xl font-black text-danger tracking-tight">
                $NT {{ number_format($monthlyExpense) }}
            </div>
            <div class="text-xs text-on-surface-variant/70 mt-2 flex items-center gap-1">
                <span class="material-symbols-outlined text-sm text-danger">trending_down</span>
                生活及開銷統計
            </div>
        </div>

        <!-- 當月淨餘額 -->
        <div class="bg-surface-pure rounded-2xl border border-border-base p-5 shadow-sm">
            <div class="text-xs font-bold text-on-surface-variant uppercase mb-1">當月淨結餘</div>
            <div class="text-2xl font-black {{ $netBalance >= 0 ? 'text-primary' : 'text-danger' }} tracking-tight">
                $NT {{ number_format($netBalance) }}
            </div>
            <div class="text-xs text-on-surface-variant/70 mt-2 flex items-center gap-1">
                <span class="material-symbols-outlined text-sm text-primary">account_balance_wallet</span>
                {{ $netBalance >= 0 ? '收支相抵後有盈餘' : '收支相抵後呈赤字' }}
            </div>
        </div>

        <!-- 儲蓄率與健康度 -->
        <div class="bg-surface-pure rounded-2xl border border-border-base p-5 shadow-sm">
            <div class="text-xs font-bold text-on-surface-variant uppercase mb-1">當月儲蓄率</div>
            <div class="text-2xl font-black text-warning tracking-tight">
                {{ $savingsRate }}%
            </div>
            <div class="w-full bg-surface-container rounded-full h-2 overflow-hidden border border-border-base mt-2">
                <div class="bg-warning h-2 rounded-full" style="width: {{ min(100, max(0, $savingsRate)) }}%"></div>
            </div>
        </div>
    </div>

    <!-- 每日消費趨勢圖 -->
    <div class="bg-surface-pure rounded-2xl border border-border-base p-6 shadow-sm">
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-border-base">
            <div>
                <h2 class="text-lg font-bold text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">calendar_view_day</span>
                    {{ $currentDate->format('m 月') }} 每日支出與收入趨勢
                </h2>
                <p class="text-xs text-on-surface-variant font-medium mt-0.5">{{ __('auto.0454') }}</p>
            </div>
        </div>
        <div class="h-72 w-full relative">
            <canvas id="dailyTrendChart"></canvas>
        </div>
    </div>

    <!-- 兩欄分析區塊：分類排行 & 最大筆支出 Top 5 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- 分類支出排行 -->
        <div class="bg-surface-pure rounded-2xl border border-border-base p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-border-base">
                <h2 class="text-lg font-bold text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">format_list_numbered</span>
                    支出分類占比排行
                </h2>
                <span class="text-xs font-semibold text-on-surface-variant">共 {{ count($categoryRank) }} 項分類</span>
            </div>

            <div class="space-y-4 max-h-[380px] overflow-y-auto pr-1">
                @forelse($categoryRank as $index => $cat)
                    @php
                        // Color palette for category rank
                        $colors = ['#FB7185', '#60A5FA', '#34D399', '#F472B6', '#A78BFA', '#FBBF24', '#F97316', '#94A3B8'];
                        $color = $colors[$index % count($colors)];
                    @endphp
                    <div class="p-3 bg-surface-container-low rounded-xl border border-border-base flex flex-col gap-2">
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2 font-bold text-on-surface">
                                <span class="w-6 h-6 rounded-full text-white text-xs flex items-center justify-center font-black" style="background-color: {{ $color }};">
                                    {{ $index + 1 }}
                                </span>
                                <span>{{ $cat->name }}</span>
                                <span class="text-xs font-normal text-on-surface-variant/70">({{ $cat->count }} 筆)</span>
                            </div>
                            <div class="text-right">
                                <span class="font-black text-on-surface">$NT {{ number_format($cat->total) }}</span>
                                <span class="text-xs text-on-surface-variant ml-1.5 font-bold">({{ $cat->percent }}%)</span>
                            </div>
                        </div>
                        <!-- Progress bar -->
                        <div class="w-full bg-surface-container-high rounded-full h-2 overflow-hidden">
                            <div class="h-2 rounded-full transition-all duration-500" style="width: {{ min(100, max(0, $cat->percent)) }}%; background-color: {{ $color }};"></div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-10 text-on-surface-variant/70">
                        <span class="material-symbols-outlined text-4xl block mb-2">find_in_page</span>
                        目前選擇的月份無任何支出紀錄
                    </div>
                @endforelse
            </div>
        </div>

        <!-- 本月 Top 5 單筆最高支出 -->
        <div class="bg-surface-pure rounded-2xl border border-border-base p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-border-base">
                <h2 class="text-lg font-bold text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-danger">payments</span>
                    單筆最高支出 Top 5
                </h2>
                <span class="text-xs font-semibold text-on-surface-variant">{{ __('auto.0506') }}</span>
            </div>

            <div class="space-y-3">
                @forelse($topExpenses as $tx)
                    <div class="p-3.5 bg-surface-container-low rounded-xl border border-border-base flex items-center justify-between hover:bg-surface-container transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-danger/10 text-danger flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-xl">shopping_cart</span>
                            </div>
                            <div>
                                <div class="font-bold text-on-surface text-sm">
                                    {{ $tx->description ?? ($tx->category->name ?? '日常支出') }}
                                </div>
                                <div class="text-xs text-on-surface-variant/70 flex items-center gap-2 mt-0.5">
                                    <span>{{ $tx->occurred_at ? $tx->occurred_at->format('Y-m-d') : '日期未錄' }}</span>
                                    <span>•</span>
                                    <span>{{ $tx->user->name ?? '未知成員' }}</span>
                                    <span>•</span>
                                    <span>{{ $tx->account->name ?? '現金' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-base font-black text-danger">
                                -$NT {{ number_format($tx->amount) }}
                            </div>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-surface-container-high text-on-surface-variant">
                                {{ $tx->category->name ?? '其他' }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-10 text-on-surface-variant/70">
                        <span class="material-symbols-outlined text-4xl block mb-2">receipt_long</span>
                        本月無支出明細
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Daily Chart Initialization Script -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dailyCtx = document.getElementById('dailyTrendChart').getContext('2d');
    
    new Chart(dailyCtx, {
        type: 'bar',
        data: {
            labels: @js($dailyLabels),
            datasets: [
                {
                    label: '每日支出',
                    data: @js($dailyExpenses),
                    backgroundColor: '#EF4444',
                    borderRadius: 4,
                    barThickness: 12
                },
                {
                    label: '每日收入',
                    data: @js($dailyIncomes),
                    backgroundColor: '#10B981',
                    borderRadius: 4,
                    barThickness: 12
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: { font: { weight: 'bold' } }
                },
                tooltip: {
                    backgroundColor: '#1C1917',
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(ctx) {
                            return ` ${ctx.dataset.label}: $NT ${ctx.parsed.y.toLocaleString()}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 10 } }
                },
                y: {
                    grid: { color: '#F5F5F4' },
                    ticks: {
                        callback: function(val) { return '$' + val.toLocaleString(); },
                        font: { size: 10 }
                    }
                }
            }
        }
    });
});

function triggerAiMonthlyAnalysis() {
    var card = document.getElementById('aiAnalysisCard');
    var loading = document.getElementById('aiAnalysisLoading');
    var content = document.getElementById('aiAnalysisContent');

    if (!card) return;
    card.style.display = 'block';
    loading.style.display = 'block';
    content.style.display = 'none';
    card.scrollIntoView({ behavior: 'smooth', block: 'center' });

    fetch("{{ route('reports.ai_analysis') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ month: "{{ $selectedMonth }}" })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        loading.style.display = 'none';
        content.style.display = 'block';
        if (data.success) {
            content.innerHTML = '<div class="space-y-3">' + 
                data.analysis.replace(/\n/g, '<br>')
                    .replace(/\*\*(.*?)\*\*/g, '<strong class="text-primary font-bold">$1</strong>')
                    .replace(/💡/g, '💡 ')
                    .replace(/⚠️/g, '⚠️ ')
                    .replace(/🎯/g, '🎯 ') + 
                '</div>';
        } else {
            content.innerHTML = '<div class="p-4 rounded-xl bg-danger/10 border border-danger/20 text-danger font-medium text-xs">' +
                '❌ AI 分析未完成：' + (data.message || '未知錯誤') + 
                '<div class="mt-2 text-[11px] text-on-surface-variant">提示：若尚未填寫 Token，請聯絡最高管理員於「AI 智能設定」中填寫 Google Gemini API Key。</div></div>';
        }
    })
    .catch(function(err) {
        loading.style.display = 'none';
        content.style.display = 'block';
        content.innerHTML = '<div class="p-4 rounded-xl bg-danger/10 border border-danger/20 text-danger font-medium text-xs">' +
            '❌ 請求失敗：' + err.message + '</div>';
    });
}
</script>
@endsection
