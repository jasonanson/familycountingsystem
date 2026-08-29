<x-app-layout>
    <x-slot name="title">{{ __('auto.0679') }}</x-slot>

    <div class="min-h-screen bg-background-warm">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            {{-- 頁面標題 --}}
            <div class="bg-surface-pure rounded-2xl p-6 shadow-sm border border-border-base">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-primary/10 border border-primary/20 rounded-2xl flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined text-3xl">notifications_active</span>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-on-surface">{{ __('settings_page.notifications') }}</h1>

{{-- Gmail 連線狀態橫幅 --}}
@php
    $gmail = app(\App\Services\GmailConnectionService::class)->getStatus();
@endphp
<div class="mb-6 rounded-lg p-4 {{ $gmail['is_connected'] ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800' }}">
    <div class="flex items-start gap-3">
        <div class="text-2xl">{{ $gmail['is_connected'] ? '✅' : '⚠️' }}</div>
        <div class="flex-1">
            <div class="font-semibold {{ $gmail['is_connected'] ? 'text-green-800 dark:text-green-200' : 'text-yellow-800 dark:text-yellow-200' }}">
                {{ $gmail['is_connected'] ? 'Gmail 連線已啟用' : 'Gmail 尚未連線' }}
            </div>
            <div class="text-sm mt-1 {{ $gmail['is_connected'] ? 'text-green-700 dark:text-green-300' : 'text-yellow-700 dark:text-yellow-300' }}">
                @if($gmail['is_connected'])
                    寄件帳號：<code>{{ $gmail['email'] }}</code> ・ 連線時間：{{ $gmail['connected_at'] }}
                    即使你把下面某個事件的 email 關掉，系統仍會記錄站內通知。
                @else
                    下面的 email 偏好會被儲存，但實際不會寄信。要啟用寄信功能，請聯絡管理員到
                    <a href="/admin/gmail-settings" class="underline font-medium">後台 → 系統 → Gmail 連線</a> 完成 OAuth 授權。
                @endif
            </div>
        </div>
    </div>
</div>
                        <p class="text-sm text-on-surface-variant mt-0.5">{{ __('auto.0613') }}</p>
                    </div>
                </div>
            </div>

            <form action="{{ route('settings.notifications.update') }}" method="POST">
                @csrf
                @method('PUT')

                {{-- 通知管道總開關 --}}
                <div class="bg-surface-pure rounded-2xl p-6 shadow-sm border border-border-base">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="material-symbols-outlined text-primary text-xl">campaign</span>
                        <h2 class="text-lg font-bold text-on-surface">{{ __('auto.0678') }}</h2>
                    </div>
                    <p class="text-xs text-on-surface-variant mb-4">{{ __('auto.0693') }}</p>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <label class="flex items-start gap-3 p-4 bg-surface-container-low rounded-xl cursor-pointer hover:bg-primary/5 transition-colors border border-transparent has-checked:border-primary has-checked:bg-primary/5">
                            <input type="checkbox" name="channels[email]" value="1" @checked($preferences['channels']['email'] ?? false)
                                   class="mt-1 w-4 h-4 accent-primary">
                            <div>
                                <div class="font-bold text-on-surface flex items-center gap-1">
                                    <span class="material-symbols-outlined text-base">email</span> Email
                                </div>
                                <div class="text-xs text-on-surface-variant mt-0.5">寄送到您註冊的 Email</div>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 p-4 bg-surface-container-low rounded-xl cursor-pointer hover:bg-primary/5 transition-colors border border-transparent has-checked:border-primary has-checked:bg-primary/5">
                            <input type="checkbox" name="channels[system]" value="1" @checked($preferences['channels']['system'] ?? true)
                                   class="mt-1 w-4 h-4 accent-primary">
                            <div>
                                <div class="font-bold text-on-surface flex items-center gap-1">
                                    <span class="material-symbols-outlined text-base">notifications</span> 站內通知
                                </div>
                                <div class="text-xs text-on-surface-variant mt-0.5">顯示於通知中心</div>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 p-4 bg-surface-container-low rounded-xl cursor-pointer hover:bg-primary/5 transition-colors border border-transparent has-checked:border-primary has-checked:bg-primary/5">
                            <input type="checkbox" name="channels[discord]" value="1" @checked($preferences['channels']['discord'] ?? false)
                                   class="mt-1 w-4 h-4 accent-primary">
                            <div>
                                <div class="font-bold text-on-surface flex items-center gap-1">
                                    <span class="material-symbols-outlined text-base">forum</span> Discord
                                </div>
                                <div class="text-xs text-on-surface-variant mt-0.5">需在家庭設定配置 Webhook</div>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- 各事件細部偏好 --}}
                <div class="bg-surface-pure rounded-2xl p-6 shadow-sm border border-border-base">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="material-symbols-outlined text-primary text-xl">event_note</span>
                        <h2 class="text-lg font-bold text-on-surface">{{ __('auto.0229') }}</h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="text-xs text-on-surface-variant font-medium uppercase border-b border-border-base">
                                <tr>
                                    <th class="text-left py-3 pr-3">{{ __('auto.0097') }}</th>
                                    <th class="text-center py-3 px-3">{{ __('notif_page.in_app') }}</th>
                                    <th class="text-center py-3 px-3">Email</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border-base">
                                @php
                                    $eventLabels = [
                                        'transaction_created' => ['icon' => 'add_circle', 'name' => '交易新增'],
                                        'transaction_deleted' => ['icon' => 'delete', 'name' => '交易刪除'],
                                        'budget_alert' => ['icon' => 'warning', 'name' => '預算警示'],
                                        'subscription_reminder' => ['icon' => 'receipt_long', 'name' => '訂閱扣款提醒'],
                                        'task_submitted' => ['icon' => 'task_alt', 'name' => '任務提交待審'],
                                        'task_approved' => ['icon' => 'check_circle', 'name' => '任務已核准'],
                                        'task_rejected' => ['icon' => 'cancel', 'name' => '任務被退回'],
                                        'family_invitation' => ['icon' => 'person_add', 'name' => '家庭成員邀請'],
                                        'limit_exceeded' => ['icon' => 'block', 'name' => '孩童消費超限'],
                                    ];
                                @endphp
                                @foreach($eventLabels as $eventKey => $info)
                                    <tr>
                                        <td class="py-3 pr-3">
                                            <span class="material-symbols-outlined text-base align-middle mr-1">{{ $info['icon'] }}</span>
                                            {{ $info['name'] }}
                                            <span class="text-xs text-on-surface-variant ml-1">({{ $eventKey }})</span>
                                        </td>
                                        <td class="text-center py-3 px-3">
                                            <input type="checkbox" name="preferences[{{ $eventKey }}][system]" value="1"
                                                   @checked($preferences['preferences'][$eventKey]['system'] ?? false)
                                                   class="w-4 h-4 accent-primary cursor-pointer">
                                        </td>
                                        <td class="text-center py-3 px-3">
                                            <input type="checkbox" name="preferences[{{ $eventKey }}][email]" value="1"
                                                   @checked($preferences['preferences'][$eventKey]['email'] ?? false)
                                                   class="w-4 h-4 accent-primary cursor-pointer">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- 特殊設定 --}}
                <div class="bg-surface-pure rounded-2xl p-6 shadow-sm border border-border-base">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="material-symbols-outlined text-primary text-xl">tune</span>
                        <h2 class="text-lg font-bold text-on-surface">{{ __('auto.0484') }}</h2>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-on-surface-variant mb-1">{{ __('auto.0731') }}</label>
                        <div class="flex items-center gap-2">
                            <input type="number" name="budget_alert_threshold"
                                   value="{{ old('budget_alert_threshold', $preferences['budget_alert_threshold'] ?? 80) }}"
                                   min="1" max="100" required
                                   class="w-24 px-3 py-2 bg-surface-pure border border-border-base rounded-lg text-base focus:outline-none focus:border-primary">
                            <span class="text-on-surface font-bold">%</span>
                        </div>
                        <p class="text-xs text-on-surface-variant mt-1">{{ __('auto.0507') }}</p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <button type="submit" class="px-6 py-2.5 bg-primary text-on-primary font-bold rounded-xl shadow-md hover:bg-primary/90 transition-all">
                        儲存通知設定
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>