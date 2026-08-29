@extends('layouts.app')

@section('title', '家庭 AI 財務分析報告')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-primary flex items-center gap-2">
                <span class="material-symbols-outlined text-3xl animate-pulse">neurology</span>
                <span>{{ __('auto.0272') }}</span>
            </h1>
            <p class="text-sm text-on-surface-variant font-medium mt-1">
                由 Google Gemini 3.5 智慧模型深度分析家庭收支、固定訂閱與預算狀況，生成專屬理財建言並自動同步通知全體家長
            </p>
        </div>

        <div class="flex items-center gap-3">
            <form action="{{ route('family_ai_reports.generate') }}" method="POST" class="flex items-center gap-2">
                @csrf
                <input type="month" name="month" value="{{ $currentMonth }}" class="px-3 py-2 bg-surface-pure border border-border-base rounded-xl text-sm font-bold text-on-surface focus:outline-none focus:border-primary">
                <button type="submit" class="px-4 py-2.5 bg-gradient-to-r from-primary to-teal-500 hover:from-primary/90 hover:to-teal-600 text-white rounded-xl text-sm font-bold shadow-md transition-all flex items-center gap-1.5 cursor-pointer">
                    <span class="material-symbols-outlined text-lg">auto_awesome</span>
                    <span>{{ __('auto.0224') }}</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Feature Info Banner -->
    <div class="bg-gradient-to-r from-primary/10 via-primary/5 to-transparent border border-primary/20 rounded-2xl p-5 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-primary text-white flex items-center justify-center shrink-0 shadow-md">
                <span class="material-symbols-outlined text-2xl">family_restroom</span>
            </div>
            <div>
                <h3 class="text-base font-bold text-on-surface">{{ __('auto.0181') }}</h3>
                <p class="text-xs text-on-surface-variant mt-0.5">
                    當任何一位家長生成本月報告，系統將自動透過<strong>站內通知</strong>與 <strong>Email</strong> 同步寄送給家庭中的<strong>全體家長</strong>，讓每位家長第一時間掌握家庭財務現況！
                </p>
            </div>
        </div>
    </div>

    <!-- Reports Table -->
    <div class="bg-surface-pure rounded-2xl border border-border-base shadow-sm overflow-hidden">
        <div class="p-5 border-b border-border-base flex justify-between items-center">
            <h2 class="font-bold text-base text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">history</span>
                <span>{{ __('auto.0448') }}</span>
            </h2>
            <span class="text-xs text-on-surface-variant">共 {{ $reports->total() }} 份報告</span>
        </div>

        @if($reports->isEmpty())
            <div class="py-16 text-center space-y-3">
                <div class="w-16 h-16 mx-auto rounded-full bg-primary/10 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-3xl">psychology</span>
                </div>
                <p class="text-base font-bold text-on-surface">{{ __('auto.0529') }}</p>
                <p class="text-xs text-on-surface-variant">{{ __('auto.0747') }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-surface-container/50 text-xs text-on-surface-variant uppercase font-semibold border-b border-border-base">
                        <tr>
                            <th class="px-5 py-3.5">{{ __('auto.0253') }}</th>
                            <th class="px-5 py-3.5">{{ __('auto.0375') }}</th>
                            <th class="px-5 py-3.5">{{ __('auto.0621') }}</th>
                            <th class="px-5 py-3.5">{{ __('auto.0490') }}</th>
                            <th class="px-5 py-3.5">{{ __('auto.0676') }}</th>
                            <th class="px-5 py-3.5">{{ __('auto.0489') }}</th>
                            <th class="px-5 py-3.5 text-right">{{ __('tx_page.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-base">
                        @foreach($reports as $r)
                            @php
                                $m = $r->financial_metrics ?? [];
                            @endphp
                            <tr class="hover:bg-surface-container/30 transition-colors">
                                <td class="px-5 py-4 font-bold text-primary">
                                    <div class="flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-base">calendar_month</span>
                                        <span>{{ $r->month }} 月度分析</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-xs space-y-0.5">
                                        <div class="text-success font-medium">入：$NT {{ number_format($m['total_income'] ?? 0) }}</div>
                                        <div class="text-danger font-medium">出：$NT {{ number_format($m['total_expense'] ?? 0) }}</div>
                                        <div class="font-bold {{ ($m['net_balance'] ?? 0) >= 0 ? 'text-primary' : 'text-danger' }}">
                                            餘：$NT {{ number_format($m['net_balance'] ?? 0) }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-primary/10 text-primary">
                                        {{ $m['subscription_count'] ?? 0 }} 筆 ($NT {{ number_format($m['subscription_total'] ?? 0) }})
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-xs text-on-surface">
                                    {{ $r->creator?->name ?? ($r->creator?->account ?? '系統/管理員') }}
                                </td>
                                <td class="px-5 py-4 text-xs font-bold text-on-surface">
                                    <span class="px-2 py-0.5 rounded-md bg-surface-container text-on-surface-variant">
                                        👥 {{ $r->sent_to_users_count }} 位
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-xs text-on-surface-variant">
                                    {{ $r->created_at->format('Y-m-d H:i') }}
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('family_ai_reports.show', $r) }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-primary/10 hover:bg-primary/20 text-primary font-bold rounded-xl text-xs transition-colors">
                                        <span>{{ __('auto.0428') }}</span>
                                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-border-base">
                {{ $reports->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
