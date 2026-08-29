<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">{{ __('auto.0702') }}</x-slot>
        <x-slot name="description">{{ __('auto.0649') }}</x-slot>

        @php $emailDups = $this->getDuplicateEmails(); @endphp

        @if($emailDups->isEmpty())
            <div class="rounded-lg bg-success/10 p-4 text-success">
                ✓ 沒有重複的 email
            </div>
        @else
            @foreach($emailDups as $dup)
                <div class="mb-4 rounded-lg border border-warning/30 bg-warning/10 p-4">
                    <div class="font-mono font-bold text-warning">{{ $dup->email }}</div>
                    <div class="text-sm text-warning">共 {{ $dup->cnt }} 筆</div>
                    <table class="mt-3 w-full text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="py-2 text-left">ID</th>
                                <th class="py-2 text-left">{{ __('field.name') }}</th>
                                <th class="py-2 text-left">{{ __('common.account') }}</th>
                                <th class="py-2 text-left">Email</th>
                                <th class="py-2 text-left">{{ __('field.created_at') }}</th>
                                <th class="py-2 text-left">{{ __('tx_page.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->getUsersByEmail($dup->email) as $u)
                                <tr class="border-b">
                                    <td class="py-2">{{ $u->id }}</td>
                                    <td class="py-2">{{ $u->name }}</td>
                                    <td class="py-2">{{ $u->account }}</td>
                                    <td class="py-2 font-mono text-xs">{{ $u->email }}</td>
                                    <td class="py-2 text-xs">{{ $u->created_at }}</td>
                                    <td class="py-2">
                                        <a href="/admin/users/{{ $u->id }}/edit" class="text-primary underline">編輯</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        @endif
    </x-filament::section>

    <x-filament::section class="mt-6">
        <x-slot name="heading">{{ __('auto.0701') }}</x-slot>
        <x-slot name="description">{{ __('auto.0648') }}</x-slot>

        @php $accountDups = $this->getDuplicateAccounts(); @endphp

        @if($accountDups->isEmpty())
            <div class="rounded-lg bg-success/10 p-4 text-success">
                ✓ 沒有重複的 account
            </div>
        @else
            @foreach($accountDups as $dup)
                <div class="mb-4 rounded-lg border border-warning/30 bg-warning/10 p-4">
                    <div class="font-mono font-bold text-warning">{{ $dup->account }}</div>
                    <div class="text-sm text-warning">共 {{ $dup->cnt }} 筆</div>
                    <table class="mt-3 w-full text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="py-2 text-left">ID</th>
                                <th class="py-2 text-left">{{ __('field.name') }}</th>
                                <th class="py-2 text-left">{{ __('common.account') }}</th>
                                <th class="py-2 text-left">Email</th>
                                <th class="py-2 text-left">{{ __('tx_page.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->getUsersByAccount($dup->account) as $u)
                                <tr class="border-b">
                                    <td class="py-2">{{ $u->id }}</td>
                                    <td class="py-2">{{ $u->name }}</td>
                                    <td class="py-2">{{ $u->account }}</td>
                                    <td class="py-2 font-mono text-xs">{{ $u->email }}</td>
                                    <td class="py-2">
                                        <a href="/admin/users/{{ $u->id }}/edit" class="text-primary underline">編輯</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        @endif
    </x-filament::section>

    <x-filament::section class="mt-6">
        <x-slot name="heading">{{ __('auto.0367') }}</x-slot>
        <ul class="list-disc pl-5 text-sm text-on-surface-variant">
            <li>點擊上方「編輯」連結可進入 Filament 標準編輯頁面修改 email 或 account</li>
            <li>修改後兩個重複的 user 即可分開</li>
            <li>如有軟刪除(deleted_at)造成的 unique 佔用,需用 artisan 指令修正: <code>php artisan users:check-duplicates</code></li>
            <li>硬刪除前請先備份資料庫</li>
        </ul>
    </x-filament::section>
</x-filament-panels::page>