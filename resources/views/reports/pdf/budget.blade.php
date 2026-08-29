@extends('reports.pdf.layout')

@section('content')
<h1>{{ __('auto.0287') }}</h1>
<p class="meta">
    家庭：<strong>{{ $familyName }}</strong>
    · 期間：{{ $currentDate->format('Y 年 m 月') }}
    · 主預算總額：NT$ {{ number_format($totalBudget) }}
    · 報告產生：{{ $generatedAt ?? now()->format('Y-m-d H:i') }}
</p>

@if($totalBudget > 0)
<div class="section">
    <h2>{{ __('auto.0095') }}</h2>
    <div class="kpi-row">
        <div class="kpi">
            <div class="kpi-label">已使用</div>
            <div class="kpi-value v-expense">NT$ {{ number_format($totalSpent) }}</div>
            <div class="kpi-hint">{{ $totalUsagePercentage }}% of NT$ {{ number_format($totalBudget) }}</div>
            <div class="progress-bar" style="margin-top:6pt;">
                @php $barClass = $totalUsagePercentage >= 100 ? 'danger' : ($totalUsagePercentage >= 80 ? 'warning' : ''); @endphp
                <div class="{{ $barClass }}" style="width: {{ min(100, $totalUsagePercentage) }}%"></div>
            </div>
        </div>
        <div class="kpi">
            <div class="kpi-label">剩餘</div>
            <div class="kpi-value v-balance">NT$ {{ number_format($totalRemaining) }}</div>
            <div class="kpi-hint">
                @if($totalStatus === 'danger')
                    <span class="tag danger">{{ __('auto.0312') }}</span>
                @elseif($totalStatus === 'warning')
                    <span class="tag warning">{{ __('auto.0352') }}</span>
                @else
                    <span class="tag ok">{{ __('auto.0441') }}</span>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

@if(isset($categoryCards) && count($categoryCards) > 0)
<div class="section">
    <h2>{{ __('auto.0230') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('finance.category') }}</th>
                <th class="right">{{ __('auto.0729') }}</th>
                <th class="right">{{ __('limit.used') }}</th>
                <th class="right">{{ __('budget_page.usage') }}</th>
                <th>{{ __('field.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($categoryCards as $card)
                @php $pct = $card['budget_amount'] > 0 ? round(($card['spent'] / $card['budget_amount']) * 100, 1) : ($card['spent'] > 0 ? 100 : 0); @endphp
                <tr>
                    <td>
                        <span class="tag" style="background:{{ $card['color'] ?? '#006b5f' }}22; color:{{ $card['color'] ?? '#006b5f' }};">{{ $card['name'] }}</span>
                    </td>
                    <td class="right">{{ $card['budget_amount'] > 0 ? 'NT$ ' . number_format($card['budget_amount']) : '—' }}</td>
                    <td class="right">NT$ {{ number_format($card['spent']) }}</td>
                    <td class="right">{{ $card['budget_amount'] > 0 ? $pct . '%' : '無預算' }}</td>
                    <td>
                        @if($card['budget_amount'] === 0)
                            <span class="tag">{{ __('auto.0480') }}</span>
                        @elseif($pct >= 100)
                            <span class="tag danger">{{ __('auto.0312') }}</span>
                        @elseif($pct >= 80)
                            <span class="tag warning">{{ __('auto.0352') }}</span>
                        @else
                            <span class="tag ok">{{ __('auto.0441') }}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
