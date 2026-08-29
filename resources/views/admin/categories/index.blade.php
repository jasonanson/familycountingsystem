@extends('layouts.app')

@section('title', '分類總覽')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-wrap justify-between items-end gap-4">
        <div>
            <h1 class="text-2xl font-bold text-text-primary flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">category</span>
                全站分類總覽
            </h1>
            <p class="text-sm text-on-surface-variant mt-1">{{ __('auto.0569') }}</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-surface-pure rounded-xl border border-border-base p-4 shadow-sm">
            <div class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">分類總數</div>
            <div class="text-2xl font-bold text-text-primary mt-1">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="bg-surface-pure rounded-xl border border-border-base p-4 shadow-sm">
            <div class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">系統預設</div>
            <div class="text-2xl font-bold text-primary mt-1">{{ number_format($stats['system']) }}</div>
        </div>
        <div class="bg-surface-pure rounded-xl border border-border-base p-4 shadow-sm">
            <div class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">家庭自訂</div>
            <div class="text-2xl font-bold text-success mt-1">{{ number_format($stats['custom']) }}</div>
        </div>
        <div class="bg-surface-pure rounded-xl border border-border-base p-4 shadow-sm">
            <div class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">已封存</div>
            <div class="text-2xl font-bold text-on-surface-variant mt-1">{{ number_format($stats['archived']) }}</div>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('admin.categories.index') }}" class="bg-surface-pure rounded-xl border border-border-base p-4 shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('auto.0363') }}"
                   class="px-3 py-2 bg-background-warm border border-border-base rounded-lg text-sm focus:outline-none focus:border-primary">
            <select name="family_id" class="px-3 py-2 bg-background-warm border border-border-base rounded-lg text-sm focus:outline-none focus:border-primary">
                <option value="all" {{ $familyFilter === 'all' || $familyFilter === null ? 'selected' : '' }}>{{ __('auto.0173') }}</option>
                <option value="system" {{ $familyFilter === 'system' ? 'selected' : '' }}>{{ __('category_page.system_default') }}</option>
                <option value="custom" {{ $familyFilter === 'custom' ? 'selected' : '' }}>{{ __('auto.0283') }}</option>
                @foreach($allFamilies as $f)
                    <option value="{{ $f->id }}" {{ (string)$familyFilter === (string)$f->id ? 'selected' : '' }}>{{ $f->name }}</option>
                @endforeach
            </select>
            <select name="type" class="px-3 py-2 bg-background-warm border border-border-base rounded-lg text-sm focus:outline-none focus:border-primary">
                <option value="">{{ __('auto.0176') }}</option>
                <option value="expense" {{ $typeFilter === 'expense' ? 'selected' : '' }}>{{ __('child_page.withdraw') }}</option>
                <option value="income" {{ $typeFilter === 'income' ? 'selected' : '' }}>{{ __('direction.in') }}</option>
                <option value="both" {{ $typeFilter === 'both' ? 'selected' : '' }}>{{ __('auto.0186') }}</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-semibold hover:bg-primary-container transition-colors flex items-center justify-center gap-1.5">
                <span class="material-symbols-outlined text-[18px]">search</span> 篩選
            </button>
        </div>
    </form>

    <!-- Categories Table -->
    <div class="bg-surface-pure rounded-xl border border-border-base shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[800px]">
                <thead>
                    <tr class="bg-background-warm border-b border-border-base text-xs text-on-surface-variant font-medium uppercase">
                        <th class="p-4 pl-6">{{ __('category.name') }}</th>
                        <th class="p-4">{{ __('member_page.belongs_family') }}</th>
                        <th class="p-4">{{ __('field.type') }}</th>
                        <th class="p-4">{{ __('category_page.parent') }}</th>
                        <th class="p-4 text-center">{{ __('auto.0102') }}</th>
                        <th class="p-4">{{ __('field.status') }}</th>
                        <th class="p-4 pr-6 text-right">{{ __('tx_page.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-base text-sm text-text-primary">
                    @forelse($categories as $cat)
                        <tr class="hover:bg-background-warm/50 transition-colors">
                            <td class="p-4 pl-6 font-semibold flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm shrink-0"
                                     style="background: {{ $cat->color ?? '#006b5f' }}">
                                    <span class="material-symbols-outlined text-[18px]">{{ \App\Support\IconHelper::name($cat->icon ?? null, 'category') }}</span>
                                </div>
                                <span>{{ $cat->name }}</span>
                            </td>
                            <td class="p-4">
                                @if($cat->family)
                                    <span class="px-2 py-0.5 bg-surface-container rounded text-xs">{{ $cat->family->name }}</span>
                                @else
                                    <span class="px-2 py-0.5 bg-primary/10 text-primary rounded text-xs font-bold">{{ __('category_page.system_default') }}</span>
                                @endif
                            </td>
                            <td class="p-4">
                                @if($cat->type === 'expense')<span class="text-danger font-semibold">{{ __('child_page.withdraw') }}</span>
                                @elseif($cat->type === 'income')<span class="text-success font-semibold">{{ __('direction.in') }}</span>
                                @else<span class="text-on-surface-variant">{{ __('auto.0185') }}</span>@endif
                            </td>
                            <td class="p-4 text-on-surface-variant">{{ $cat->parent->name ?? '—' }}</td>
                            <td class="p-4 text-center font-mono">{{ number_format($cat->transactions_count) }}</td>
                            <td class="p-4">
                                @if($cat->is_archived)
                                    <span class="px-2 py-0.5 bg-warning/15 text-warning rounded text-xs font-bold">{{ __('auto.0299') }}</span>
                                @else
                                    <span class="px-2 py-0.5 bg-success/15 text-success rounded text-xs font-bold">{{ __('member_page.active') }}</span>
                                @endif
                            </td>
                            <td class="p-4 pr-6 text-right">
                                @if($cat->transactions_count === 0)
                                    <form action="{{ route('admin.categories.destroy', $cat) }}" method="POST" class="inline" onsubmit="return confirm('確定要刪除分類「{{ $cat->name }}」？')">
                                        @csrf @method('DELETE')
                                        <button class="px-3 py-1.5 bg-danger/10 text-danger hover:bg-danger/20 rounded-lg text-xs font-semibold transition-colors">{{ __('common.delete') }}</button>
                                    </form>
                                @else
                                    <span class="text-xs text-on-surface-variant">{{ __('auto.0310') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-8 text-center text-on-surface-variant">{{ __('auto.0464') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-border-base">{{ $categories->links() }}</div>
    </div>
</div>
@endsection
