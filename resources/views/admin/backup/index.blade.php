@extends('layouts.app')

@section('title', '資料備份')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-wrap justify-between items-end gap-4">
        <div>
            <h1 class="text-2xl font-bold text-text-primary flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">backup</span>
                資料備份與還原
            </h1>
            <p class="text-sm text-on-surface-variant mt-1">
                資料庫：<span class="font-mono text-primary">{{ $dbName }}</span>
                ｜驅動：<span class="font-mono">{{ strtoupper($driver) }}</span>
            </p>
        </div>
        <form action="{{ route('admin.backup.create') }}" method="POST" onsubmit="return confirm('確定要建立新的備份嗎？')">
            @csrf
            <button type="submit" class="bg-primary hover:bg-primary-container text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-colors flex items-center gap-2 shadow-sm">
                <span class="material-symbols-outlined text-[20px]">cloud_download</span>
                立即建立備份
            </button>
        </form>
    </div>

    <!-- DB Stats -->
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        <div class="bg-surface-pure rounded-xl border border-border-base p-4 shadow-sm">
            <div class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">資料表數</div>
            <div class="text-2xl font-bold text-text-primary mt-1">{{ count($tableStats) }}</div>
        </div>
        <div class="bg-surface-pure rounded-xl border border-border-base p-4 shadow-sm">
            <div class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">總資料筆數</div>
            <div class="text-2xl font-bold text-primary mt-1">{{ number_format($totalRows) }}</div>
        </div>
        <div class="bg-surface-pure rounded-xl border border-border-base p-4 shadow-sm">
            <div class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">資料庫大小</div>
            <div class="text-2xl font-bold text-success mt-1">{{ $humanTotalSize }}</div>
        </div>
    </div>

    <!-- Backup Files -->
    <div class="bg-surface-pure rounded-xl border border-border-base shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-border-base flex items-center justify-between">
            <h2 class="font-bold text-text-primary flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">inventory_2</span>
                備份檔案清單
                <span class="px-2 py-0.5 bg-primary/10 text-primary rounded text-xs font-bold">{{ count($files) }}</span>
            </h2>
        </div>

        @if(count($files) === 0)
            <div class="p-12 text-center text-on-surface-variant">
                <span class="material-symbols-outlined text-[64px] mb-3 opacity-30">folder_off</span>
                <p>{{ __('auto.0535') }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[700px]">
                    <thead>
                        <tr class="bg-background-warm border-b border-border-base text-xs text-on-surface-variant font-medium uppercase">
                            <th class="p-4 pl-6">{{ __('auto.0436') }}</th>
                            <th class="p-4">{{ __('auto.0256') }}</th>
                            <th class="p-4">{{ __('field.created_at') }}</th>
                            <th class="p-4">{{ __('auto.0657') }}</th>
                            <th class="p-4 pr-6 text-right">{{ __('tx_page.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-base text-sm text-text-primary">
                        @foreach($files as $f)
                            <tr class="hover:bg-background-warm/50 transition-colors">
                                <td class="p-4 pl-6 font-mono">{{ $f['name'] }}</td>
                                <td class="p-4 font-semibold text-primary">{{ $f['size'] }}</td>
                                <td class="p-4 text-on-surface-variant">{{ $f['mtime'] }}</td>
                                <td class="p-4">
                                    @if($f['age_hours'] < 24)
                                        <span class="px-2 py-0.5 bg-success/15 text-success rounded text-xs font-bold">{{ $f['age_hours'] }} 小時前</span>
                                    @elseif($f['age_hours'] < 24 * 7)
                                        <span class="px-2 py-0.5 bg-warning/15 text-warning rounded text-xs font-bold">{{ round($f['age_hours'] / 24, 1) }} 天前</span>
                                    @else
                                        <span class="px-2 py-0.5 bg-on-surface/15 text-on-surface rounded text-xs font-bold">{{ round($f['age_hours'] / 24) }} 天前</span>
                                    @endif
                                </td>
                                <td class="p-4 pr-6 text-right space-x-2">
                                    <a href="{{ route('admin.backup.download', $f['name']) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 bg-primary/10 text-primary hover:bg-primary/20 rounded-lg text-xs font-semibold transition-colors">
                                        <span class="material-symbols-outlined text-[16px]">download</span>
                                        下載
                                    </a>
                                    <form action="{{ route('admin.backup.destroy', $f['name']) }}" method="POST" class="inline"
                                          onsubmit="return confirm('確定要刪除備份 {{ $f['name'] }}？此操作無法復原。')">
                                        @csrf @method('DELETE')
                                        <button class="inline-flex items-center gap-1 px-3 py-1.5 bg-danger/10 text-danger hover:bg-danger/20 rounded-lg text-xs font-semibold transition-colors">
                                            <span class="material-symbols-outlined text-[16px]">delete</span>
                                            刪除
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Table Stats -->
    <div class="bg-surface-pure rounded-xl border border-border-base shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-border-base">
            <h2 class="font-bold text-text-primary flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">storage</span>
                各資料表記錄統計
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-background-warm border-b border-border-base text-xs text-on-surface-variant font-medium uppercase">
                        <th class="p-3 pl-6">{{ __('auto.0651') }}</th>
                        <th class="p-3">{{ __('auto.0329') }}</th>
                        <th class="p-3 text-right">{{ __('auto.0562') }}</th>
                        <th class="p-3 pr-6 text-right">{{ __('auto.0256') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-base text-text-primary">
                    @foreach($tableStats as $t)
                        <tr class="hover:bg-background-warm/50 transition-colors">
                            <td class="p-3 pl-6 font-mono">{{ $t['name'] }}</td>
                            <td class="p-3 text-xs text-on-surface-variant">{{ $t['engine'] }}</td>
                            <td class="p-3 text-right font-mono">{{ number_format($t['rows']) }}</td>
                            <td class="p-3 pr-6 text-right text-on-surface-variant">{{ $t['size'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Restore Help -->
    <div class="bg-primary/5 border border-primary/20 rounded-xl p-5">
        <h3 class="font-bold text-text-primary flex items-center gap-2 mb-2">
            <span class="material-symbols-outlined text-primary text-[20px]">info</span>
            如何還原備份？
        </h3>
        <ol class="text-sm text-on-surface-variant space-y-1 list-decimal pl-5">
            <li>下載備份檔（.sql）到本機</li>
            <li>使用 MySQL/MariaDB client（phpMyAdmin、HeidiSQL、命令列 <code class="bg-surface-container px-1.5 py-0.5 rounded text-xs">mysql -u root -p family_accounting &lt; backup_xxx.sql</code>）匯入</li>
            <li>或聯絡系統管理員協助處理</li>
        </ol>
    </div>
</div>
@endsection
