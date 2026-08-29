@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <h1 class="text-3xl font-bold text-text-primary tracking-tight">{{ __('auto.0582') }}</h1>

    <!-- Top Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Family Stat Card -->
        <div class="bg-surface-pure rounded-2xl p-6 border border-border-base shadow-sm flex flex-col gap-2 hover:-translate-y-1 transition-transform duration-300">
            <div class="flex justify-between items-start">
                <span class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">{{ __('auto.0281') }}</span>
                <div class="p-2.5 bg-category-sky/15 rounded-xl text-category-sky">
                    <span class="material-symbols-outlined text-[24px]">holiday_village</span>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold text-text-primary">{{ $totalFamilies }}</span>
                <span class="text-xs text-on-surface-variant">{{ __('auto.0147') }}</span>
            </div>
            <a href="{{ route('admin.families.index') }}" class="mt-auto text-primary text-xs font-semibold flex items-center gap-1 hover:text-primary-container transition-colors w-fit pt-2">
                <span class="material-symbols-outlined text-[16px]">north_east</span> 前往家庭管理
            </a>
        </div>

        <!-- User Stat Card -->
        <div class="bg-surface-pure rounded-2xl p-6 border border-border-base shadow-sm flex flex-col gap-2 hover:-translate-y-1 transition-transform duration-300">
            <div class="flex justify-between items-start">
                <span class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">{{ __('auto.0117') }}</span>
                <div class="p-2.5 bg-category-lavender/15 rounded-xl text-category-lavender">
                    <span class="material-symbols-outlined text-[24px]">groups</span>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold text-text-primary">{{ $totalUsers }}</span>
                <span class="text-xs text-on-surface-variant">{{ __('auto.0111') }}</span>
            </div>
            <a href="{{ route('admin.users.index') }}" class="mt-auto text-primary text-xs font-semibold flex items-center gap-1 hover:text-primary-container transition-colors w-fit pt-2">
                <span class="material-symbols-outlined text-[16px]">north_east</span> 前往使用者管理
            </a>
        </div>

        <!-- Transaction Stat Card -->
        <div class="bg-surface-pure rounded-2xl p-6 border border-border-base shadow-sm flex flex-col gap-2 hover:-translate-y-1 transition-transform duration-300">
            <div class="flex justify-between items-start">
                <span class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">{{ __('auto.0417') }}</span>
                <div class="p-2.5 bg-category-mint/15 rounded-xl text-category-mint">
                    <span class="material-symbols-outlined text-[24px]">receipt_long</span>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold text-text-primary">{{ number_format($totalTransactions) }}</span>
                <span class="text-xs text-on-surface-variant">{{ __('auto.0563') }}</span>
            </div>
            <span class="mt-auto text-success text-xs font-medium pt-2">{{ __('auto.0171') }}</span>
        </div>

        <!-- Storage Stat Card -->
        <div class="bg-surface-pure rounded-2xl p-6 border border-border-base shadow-sm flex flex-col gap-2 hover:-translate-y-1 transition-transform duration-300">
            <div class="flex justify-between items-start">
                <span class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">{{ __('auto.0578') }}</span>
                <div class="p-2.5 bg-category-amber/15 rounded-xl text-category-amber">
                    <span class="material-symbols-outlined text-[24px]">database</span>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold text-text-primary">{{ $storageUsedFormatted }}</span>
                <span class="text-xs text-on-surface-variant">/ 5.0 GB 空間</span>
            </div>
            <div class="w-full h-2 bg-surface-container rounded-full mt-2 overflow-hidden">
                <div class="h-full bg-category-amber rounded-full" style="width: {{ $storagePercent }}%"></div>
            </div>
        </div>
    </div>

    <!-- Quick Overview Sections -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Recent Families -->
        <div class="bg-surface-pure rounded-2xl border border-border-base shadow-sm p-6 space-y-4">
            <div class="flex justify-between items-center pb-2 border-b border-border-base">
                <h3 class="text-lg font-bold text-text-primary">{{ __('auto.0404') }}</h3>
                <a href="{{ route('admin.families.index') }}" class="text-primary text-xs font-semibold hover:underline">{{ __('action.view_all') }}</a>
            </div>
            <div class="space-y-3">
                @forelse($recentFamilies as $family)
                    <div class="flex items-center justify-between p-3 rounded-xl hover:bg-background-warm transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-primary/10 text-primary font-bold flex items-center justify-center text-xs">
                                {{ mb_substr($family->name, 0, 1) }}
                            </div>
                            <div>
                                <div class="font-semibold text-sm text-text-primary">{{ $family->name }}</div>
                                <div class="text-xs text-on-surface-variant">總預算: NT$ {{ number_format($family->total_pool_amount ?? 0) }}</div>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 bg-success/10 text-success rounded-md text-xs font-medium">{{ __('sub_page.active') }}</span>
                    </div>
                @empty
                    <div class="p-4 text-center text-sm text-on-surface-variant">目前無任何家庭資料。</div>
                @endforelse
            </div>
        </div>

        <!-- Recent Users -->
        <div class="bg-surface-pure rounded-2xl border border-border-base shadow-sm p-6 space-y-4">
            <div class="flex justify-between items-center pb-2 border-b border-border-base">
                <h3 class="text-lg font-bold text-text-primary">{{ __('auto.0403') }}</h3>
                <a href="{{ route('admin.users.index') }}" class="text-primary text-xs font-semibold hover:underline">{{ __('action.view_all') }}</a>
            </div>
            <div class="space-y-3">
                @forelse($recentUsers as $user)
                    <div class="flex items-center justify-between p-3 rounded-xl hover:bg-background-warm transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-secondary-container text-on-secondary-container font-bold flex items-center justify-center text-xs">
                                {{ mb_substr($user->name, 0, 1) }}
                            </div>
                            <div>
                                <div class="font-semibold text-sm text-text-primary">{{ $user->name }}</div>
                                <div class="text-xs text-on-surface-variant">{{ $user->email }}</div>
                            </div>
                        </div>
                        @if($user->is_system_admin)
                            <span class="px-2.5 py-1 bg-danger/10 text-danger rounded-md text-xs font-medium">{{ __('auto.0406') }}</span>
                        @else
                            <span class="px-2.5 py-1 bg-surface-container text-on-surface-variant rounded-md text-xs font-medium">{{ __('auto.0078') }}</span>
                        @endif
                    </div>
                @empty
                    <div class="p-4 text-center text-sm text-on-surface-variant">目前無任何帳號資料。</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
