@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-primary flex items-center gap-2">
                <span class="material-symbols-outlined text-3xl">history</span>
                全域稽核日誌 (Audit Logs)
            </h1>
            <p class="text-sm text-on-surface-variant">{{ __('auto.0525') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="px-3 py-1 bg-primary/10 text-primary text-xs font-semibold rounded-lg">共 {{ $totalCount ?? 0 }} 筆</span>
        </div>
    </div>

    {{-- Filter 表單 --}}
    <div class="surface-pure rounded-xl border border-border-base shadow-sm overflow-hidden">
        <form method="GET" action="{{ url('/audit-logs') }}" class="p-4 bg-background-warm border-b border-border-base">
            <div class="flex items-center gap-2 mb-3">
                <span class="material-symbols-outlined text-on-surface-variant text-[20px]">tune</span>
                <span class="text-sm font-semibold text-text-primary">{{ __('auto.0397') }}</span>
                <span class="text-xs text-on-surface-variant ml-auto">{{ __('auto.0740') }}</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">{{ __('auto.0212') }}</label>
                    <select name="action" class="w-full px-3 py-2 bg-surface-pure border border-border-base rounded-lg text-sm">
                        <option value="">{{ __('auto.0174') }}</option>
                        @foreach($actionCounts ?? [] as $ac)
                            <option value="{{ $ac->action }}" {{ request('action') == $ac->action ? 'selected' : '' }}>
                                {{ $ac->action }} ({{ $ac->cnt }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">{{ __('auto.0654') }}</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="w-full px-3 py-2 bg-surface-pure border border-border-base rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">{{ __('field.end_date') }}</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="w-full px-3 py-2 bg-surface-pure border border-border-base rounded-lg text-sm">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="px-4 py-2 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary/90 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">filter_alt</span>
                        套用篩選
                    </button>
                    <a href="{{ url('/audit-logs') }}" class="px-4 py-2 bg-background-warm text-on-surface-variant text-sm font-semibold rounded-lg hover:bg-border-base">{{ __('auto.0471') }}</a>
                </div>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[900px]">
                <thead>
                    <tr class="bg-background-warm/50 border-b border-border-base text-xs text-on-surface-variant font-medium uppercase">
                        <th class="p-4 pl-6">{{ __('auto.0398') }}</th>
                        <th class="p-4">{{ __('auto.0366') }}</th>
                        <th class="p-4">{{ __('member_page.belongs_family') }}</th>
                        <th class="p-4">{{ __('auto.0211') }}</th>
                        <th class="p-4">{{ __('auto.0635') }}</th>
                        <th class="p-4">IP 位址</th>
                        <th class="p-4 pr-6">{{ __('auto.0517') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-base text-sm text-text-primary">
                    @forelse($logs ?? [] as $log)
                        <tr class="hover:bg-background-warm/50 transition-colors">
                            <td class="p-4 pl-6 font-mono text-xs text-on-surface-variant">{{ $log['id'] }}</td>
                            <td class="p-4">
                                <div class="font-semibold text-text-primary">{{ $log['user_name'] }}</div>
                                <div class="text-xs text-on-surface-variant font-mono">{{ $log['user_account'] }}</div>
                            </td>
                            <td class="p-4">
                                @if($log['family_name'] !== '-')
                                    <span class="px-2 py-0.5 bg-primary/10 text-primary rounded text-xs">{{ $log['family_name'] }}</span>
                                @else
                                    <span class="text-xs text-on-surface-variant">-</span>
                                @endif
                            </td>
                            <td class="p-4">
                                @if($log['severity'] === 'warning')
                                    <span class="px-2.5 py-1 bg-warning/15 text-warning rounded-md text-xs font-medium">{{ $log['action'] }}</span>
                                @elseif($log['severity'] === 'success')
                                    <span class="px-2.5 py-1 bg-success/15 text-success rounded-md text-xs font-medium">{{ $log['action'] }}</span>
                                @else
                                    <span class="px-2.5 py-1 bg-category-sky/15 text-category-sky rounded-md text-xs font-medium">{{ $log['action'] }}</span>
                                @endif
                            </td>
                            <td class="p-4 text-on-surface-variant">{{ $log['details'] }}</td>
                            <td class="p-4 font-mono text-xs text-on-surface-variant">{{ $log['ip_address'] }}</td>
                            <td class="p-4 pr-6 text-xs text-on-surface-variant">{{ $log['created_at'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-on-surface-variant">{{ __('auto.0539') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection