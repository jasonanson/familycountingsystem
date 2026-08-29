@extends('layouts.app')

@section('title', $report->month . ' 月家庭 AI 財務分析報告')

@section('content')
@php
    $m = $report->financial_metrics ?? [];
    $income = $m['total_income'] ?? 0;
    $expense = $m['total_expense'] ?? 0;
    $balance = $m['net_balance'] ?? 0;
    $savingsRate = $m['savings_rate'] ?? 0;
    $subs = $m['subscriptions'] ?? [];
    $subTotal = $m['subscription_total'] ?? 0;
@endphp

<div class="space-y-6 max-w-5xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('family_ai_reports.index') }}" class="w-10 h-10 rounded-2xl bg-surface-pure border border-border-base text-on-surface-variant flex items-center justify-center hover:bg-surface-container hover:text-primary transition-colors shadow-sm" title="{{ __('auto.0670') }}">
                <span class="material-symbols-outlined text-xl">arrow_back</span>
            </a>
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-primary flex items-center gap-2">
                    <span>{{ $report->month }} 月度家庭 AI 財務分析報告</span>
                </h1>
                <div class="flex flex-wrap items-center gap-2 mt-1 text-xs text-on-surface-variant font-medium">
                    <span>👨‍👩‍👧 {{ $report->family?->name ?? '家庭' }}</span>
                    <span>•</span>
                    <span>生成者：{{ $report->creator?->name ?? ($report->creator?->account ?? '系統/管理員') }}</span>
                    <span>•</span>
                    <span>{{ $report->created_at->format('Y-m-d H:i') }}</span>
                    <span class="ml-2 px-2 py-0.5 rounded-full bg-primary/10 text-primary font-bold">
                        📢 已通知全體 {{ $report->sent_to_users_count }} 位家長
                    </span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" class="px-4 py-2 bg-surface-pure border border-border-base text-on-surface hover:bg-surface-container rounded-xl text-xs font-bold transition-all shadow-sm flex items-center gap-1.5 cursor-pointer">
                <span class="material-symbols-outlined text-base">print</span>
                <span>{{ __('auto.0198') }}</span>
            </button>
            <form action="{{ route('family_ai_reports.generate') }}" method="POST">
                @csrf
                <input type="hidden" name="month" value="{{ $report->month }}">
                <input type="hidden" name="family_id" value="{{ $report->family_id }}">
                <button type="submit" class="px-4 py-2 bg-primary text-white hover:bg-primary/90 rounded-xl text-xs font-bold transition-all shadow-sm flex items-center gap-1.5 cursor-pointer">
                    <span class="material-symbols-outlined text-base">refresh</span>
                    <span>{{ __('auto.0698') }}</span>
                </button>
            </form>
        </div>
    </div>

    <!-- 頂部數據 KPI 摘要卡片 -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 總收入 -->
        <div class="bg-surface-pure rounded-2xl border border-border-base p-5 shadow-sm">
            <div class="text-xs font-bold text-on-surface-variant uppercase mb-1">當月家庭總收入</div>
            <div class="text-2xl font-black text-success tracking-tight">
                $NT {{ number_format($income) }}
            </div>
            <div class="text-[11px] text-on-surface-variant mt-2 flex items-center gap-1">
                <span class="material-symbols-outlined text-sm text-success">trending_up</span>
                家庭成員入帳總計
            </div>
        </div>

        <!-- 總支出 -->
        <div class="bg-surface-pure rounded-2xl border border-border-base p-5 shadow-sm">
            <div class="text-xs font-bold text-on-surface-variant uppercase mb-1">當月家庭總支出</div>
            <div class="text-2xl font-black text-danger tracking-tight">
                $NT {{ number_format($expense) }}
            </div>
            <div class="text-[11px] text-on-surface-variant mt-2 flex items-center gap-1">
                <span class="material-symbols-outlined text-sm text-danger">trending_down</span>
                生活及開銷統計
            </div>
        </div>

        <!-- 淨結餘 -->
        <div class="bg-surface-pure rounded-2xl border border-border-base p-5 shadow-sm">
            <div class="text-xs font-bold text-on-surface-variant uppercase mb-1">當月淨結餘</div>
            <div class="text-2xl font-black {{ $balance >= 0 ? 'text-primary' : 'text-danger' }} tracking-tight">
                $NT {{ number_format($balance) }}
            </div>
            <div class="text-[11px] text-on-surface-variant mt-2 flex items-center gap-1">
                <span class="material-symbols-outlined text-sm text-primary">account_balance_wallet</span>
                {{ $balance >= 0 ? '本月收支盈餘' : '本月呈赤字赤字' }}
            </div>
        </div>

        <!-- 儲蓄率 -->
        <div class="bg-surface-pure rounded-2xl border border-border-base p-5 shadow-sm">
            <div class="text-xs font-bold text-on-surface-variant uppercase mb-1">當月儲蓄率</div>
            <div class="text-2xl font-black text-warning tracking-tight">
                {{ $savingsRate }}%
            </div>
            <div class="w-full bg-surface-container rounded-full h-1.5 overflow-hidden mt-2">
                <div class="bg-warning h-1.5 rounded-full" style="width: {{ min(100, max(0, $savingsRate)) }}%"></div>
            </div>
        </div>
    </div>

    <!-- 固定訂閱與經常性扣款卡片 (若有) -->
    <div class="bg-surface-pure rounded-2xl border border-border-base p-6 shadow-sm">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-border-base">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-xl">sync</span>
                </div>
                <h2 class="text-base font-bold text-on-surface">{{ __('auto.0246') }}</h2>
            </div>
            <span class="text-xs font-bold text-primary px-2.5 py-1 bg-primary/10 rounded-full">
                共 {{ count($subs) }} 筆 (月合計 $NT {{ number_format($subTotal) }})
            </span>
        </div>

        @if(empty($subs))
            <p class="text-xs text-on-surface-variant py-2">{{ __('auto.0543') }}</p>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                @foreach($subs as $s)
                    <div class="p-3 bg-surface-container/40 rounded-xl border border-border-base flex items-center justify-between">
                        <div>
                            <div class="font-bold text-sm text-on-surface">{{ $s['name'] }}</div>
                            <div class="text-[11px] text-on-surface-variant">{{ $s['billing_cycle'] ?? 'monthly' }} 扣款</div>
                        </div>
                        <div class="font-black text-sm text-danger">
                            $NT {{ number_format($s['amount']) }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Gemini 3.5 AI 智慧分析核心內容 -->
    <div class="bg-surface-pure border-2 border-primary/40 rounded-2xl p-6 sm:p-8 shadow-md relative overflow-hidden space-y-4">
        <div class="absolute top-0 right-0 w-48 h-48 bg-primary/5 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex items-center gap-3 pb-4 border-b border-border-base">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-primary to-teal-500 text-white flex items-center justify-center shadow-md">
                <span class="material-symbols-outlined text-2xl">neurology</span>
            </div>
            <div>
                <h2 class="text-lg font-bold text-on-surface flex items-center gap-2">
                    <span>Google Gemini 3.5 Flash Lite 智慧分析評估</span>
                    <span class="text-[10px] bg-primary/15 text-primary px-2 py-0.5 rounded-full font-bold">{{ __('auto.0180') }}</span>
                </h2>
                <p class="text-xs text-on-surface-variant mt-0.5">{{ __('auto.0577') }}</p>
            </div>
        </div>

        <div class="text-sm text-on-surface leading-relaxed whitespace-pre-line space-y-4 pt-2">
            {!! nl2br(e($report->ai_report)) !!}
        </div>
    </div>
</div>
@endsection
