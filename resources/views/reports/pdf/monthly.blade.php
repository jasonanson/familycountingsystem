@extends('reports.pdf.layout')

@section('content')
<h1>{{ $currentDate->format('Y 年 m 月') }} 月度財務報告</h1>
<p class="meta">
    家庭：<strong>{{ $familyName }}</strong>
    · 期間：{{ $currentDate->copy()->startOfMonth()->format('Y/m/d') }} ~ {{ $currentDate->copy()->endOfMonth()->format('Y/m/d') }}
    · 報告產生：{{ $generatedAt ?? now()->format('Y-m-d H:i') }}
</p>

{{-- KPI --}}
<div class="kpi-row">
    <div class="kpi">
        <div class="kpi-label">總收入</div>
        <div class="kpi-value v-income">NT$ {{ number_format($monthlyIncome) }}</div>
        <div class="kpi-hint">含薪資、獎金、零用錢等</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">總支出</div>
        <div class="kpi-value v-expense">NT$ {{ number_format($monthlyExpense) }}</div>
        <div class="kpi-hint">本月所有消費加總</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">淨結餘</div>
        <div class="kpi-value v-balance">NT$ {{ number_format($netBalance) }}</div>
        <div class="kpi-hint">
            @if($savingsRate > 0)
                儲蓄率 {{ $savingsRate }}%
            @else
                入不敷出
            @endif
        </div>
    </div>
</div>

{{-- 分類支出排行 --}}
<div class="section">
    <h2>{{ __('auto.0194') }}</h2>
    @if(isset($categoryRank) && $categoryRank->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th style="width:36pt;">#</th>
                    <th>{{ __('finance.category') }}</th>
                    <th class="right" style="width:80pt;">{{ __('sub_page.amount') }}</th>
                    <th class="right" style="width:60pt;">{{ __('auto.0112') }}</th>
                    <th class="right" style="width:60pt;">{{ __('auto.0562') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categoryRank as $i => $cat)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $cat->name }}</td>
                        <td class="right">NT$ {{ number_format($cat->total) }}</td>
                        <td class="right">{{ $cat->percent }}%</td>
                        <td class="right">{{ $cat->count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="meta">{{ __('auto.0421') }}</p>
    @endif
</div>

{{-- 最大筆支出 --}}
<div class="section">
    <h2>{{ __('auto.0242') }}</h2>
    @if(isset($topExpenses) && $topExpenses->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th>{{ __('tx_page.date') }}</th>
                    <th>{{ __('auto.0353') }}</th>
                    <th>{{ __('role_label.member') }}</th>
                    <th>{{ __('account_page.title') }}</th>
                    <th class="right">{{ __('sub_page.amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topExpenses as $tx)
                    <tr>
                        <td>{{ $tx->occurred_at ? $tx->occurred_at->format('Y-m-d') : '-' }}</td>
                        <td>{{ $tx->description ?? ($tx->category->name ?? '日常支出') }}<br><span class="tag">{{ $tx->category->name ?? '其他' }}</span></td>
                        <td>{{ $tx->user->name ?? '未知' }}</td>
                        <td>{{ $tx->account->name ?? '現金' }}</td>
                        <td class="right">NT$ {{ number_format($tx->amount) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="meta">{{ __('auto.0421') }}</p>
    @endif
</div>

{{-- 家庭成員消費 --}}
@if(isset($memberExpenses) && count($memberExpenses) > 0)
<div class="section">
    <h2>{{ __('auto.0279') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('role_label.member') }}</th>
                <th class="right">{{ __('dashboard.this_month_expense') }}</th>
                <th class="right">{{ __('auto.0113') }}</th>
            </tr>
        </thead>
        <tbody>
            @php $totalMember = array_sum($memberExpenses); @endphp
            @foreach($memberExpenses as $name => $amount)
                <tr>
                    <td>{{ $name }}</td>
                    <td class="right">NT$ {{ number_format($amount) }}</td>
                    <td class="right">{{ $totalMember > 0 ? round(($amount / $totalMember) * 100, 1) : 0 }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
