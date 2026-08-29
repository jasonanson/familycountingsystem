<x-app-layout>
    <x-slot name="title">{{ __('notif.center_title') }}</x-slot>

    <div x-data="{
        tab: 'all',
        items: {{ json_encode($notificationsJson ?? []) }},
        get unreadCount() {
            return this.items.filter(i => !i.read_at).length;
        },
        get filteredItems() {
            if (this.tab === 'unread') {
                return this.items.filter(i => !i.read_at);
            }
            return this.items;
        },
        async markAsRead(id) {
            const originalItems = [...this.items];
            const now = new Date().toISOString();
            this.items = this.items.map(i => i.id === id ? { ...i, read_at: now } : i);
            try {
                const token = document.querySelector('meta[name=\'csrf-token\']')?.getAttribute('content') || '{{ csrf_token() }}';
                const response = await fetch(`{{ url('notifications') }}/${id}/read`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!response.ok) {
                    this.items = originalItems;
                }
            } catch (e) {
                console.error(e);
                this.items = originalItems;
            }
        },
        async markAllAsRead() {
            const originalItems = [...this.items];
            const now = new Date().toISOString();
            this.items = this.items.map(i => ({ ...i, read_at: i.read_at || now }));
            try {
                const token = document.querySelector('meta[name=\'csrf-token\']')?.getAttribute('content') || '{{ csrf_token() }}';
                const response = await fetch(`{{ route('notifications.mark-all-read') }}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!response.ok) {
                    this.items = originalItems;
                }
            } catch (e) {
                console.error(e);
                this.items = originalItems;
            }
        },
        async deleteItem(id) {
            if (!confirm('確定要刪除這筆通知嗎？')) return;
            this.items = this.items.filter(i => i.id !== id);
            try {
                const token = document.querySelector('meta[name=\'csrf-token\']')?.getAttribute('content') || '{{ csrf_token() }}';
                await fetch(`{{ url('notifications') }}/${id}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ _method: 'DELETE', _token: token })
                });
            } catch (e) {
                console.error(e);
            }
        },
        getIconName(type) {
            switch(type) {
                case 'task_approval':
                case 'task': return 'task_alt';
                case 'budget_alert':
                case 'warning': return 'warning';
                case 'invitation':
                case 'person_add': return 'person_add';
                case 'bill':
                case 'subscription': return 'receipt_long';
                case 'expense_split': return 'call_split';
                default: return 'notifications';
            }
        },
        getIconBgClass(type) {
            switch(type) {
                case 'task_approval':
                case 'task': return 'bg-success/15 text-success dark:bg-success/15 dark:text-success/70 border border-success/20';
                case 'budget_alert':
                case 'warning': return 'bg-warning/15 text-warning dark:bg-warning/15 dark:text-warning/70 border border-warning/20';
                case 'invitation':
                case 'person_add': return 'bg-primary/15 text-primary dark:bg-primary/15 dark:text-primary/60 border border-primary/30';
                case 'bill':
                case 'subscription': return 'bg-danger/15 text-danger dark:bg-danger/15 dark:text-danger/70 border border-danger/20';
                case 'expense_split': return 'bg-category-sky/15 text-category-sky dark:bg-category-sky/15 dark:text-category-sky border border-category-sky/30';
                default: return 'bg-primary/10 text-primary border border-primary/20';
            }
        },
        getTypeBadgeClass(type) {
            switch(type) {
                case 'task_approval':
                case 'task': return 'bg-success/15 text-success';
                case 'budget_alert':
                case 'warning': return 'bg-warning/15 text-warning';
                case 'invitation':
                case 'person_add': return 'bg-primary/15 text-primary/80';
                case 'bill':
                case 'subscription': return 'bg-danger/15 text-danger';
                case 'expense_split': return 'bg-category-sky/15 text-category-sky';
                default: return 'bg-primary/10 text-primary';
            }
        },
        getTypeLabel(type) {
            switch(type) {
                case 'task_approval':
                case 'task': return '任務審核';
                case 'budget_alert':
                case 'warning': return '預算警示';
                case 'invitation':
                case 'person_add': return '系統邀請';
                case 'bill':
                case 'subscription': return '固定支出';
                case 'expense_split': return '分帳提醒';
                default: return '系統通知';
            }
        }
    }" class="space-y-6">

        <!-- Header Section -->
        <div class="bg-surface-pure rounded-2xl p-6 shadow-sm border border-border-base flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary flex-shrink-0">
                    <span class="material-symbols-outlined text-3xl">notifications</span>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-primary flex items-center gap-2">
                        通知中心
                        <span class="text-xs px-2.5 py-0.5 rounded-full font-bold bg-primary/10 text-primary border border-primary/20" x-text="unreadCount + ' 筆未讀'"></span>
                    </h1>
                    <p class="text-sm text-on-surface-variant mt-0.5">{{ __('auto.0223') }}</p>
                </div>
            </div>

            <!-- Action: Mark All as Read -->
            <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                <button @click="markAllAsRead()" 
                        :disabled="unreadCount === 0"
                        :class="unreadCount > 0 ? 'bg-primary text-white hover:bg-primary/90 shadow-sm cursor-pointer' : 'bg-surface-container text-on-surface-variant/50 cursor-not-allowed'"
                        class="px-4 py-2.5 rounded-xl font-bold text-sm transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">done_all</span>
                    <span>{{ __('auto.0079') }}</span>
                </button>
            </div>
        </div>

        <!-- Filter Tabs & Stats -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-border-base pb-4">
            <!-- Tabs -->
            <div class="flex items-center gap-2 bg-surface-container-low p-1.5 rounded-xl border border-border-base w-fit">
                <button @click="tab = 'all'" 
                        :class="tab === 'all' ? 'bg-surface-pure text-primary font-bold shadow-sm' : 'text-on-surface-variant hover:text-on-surface font-medium'"
                        class="px-4 py-2 rounded-lg text-sm transition-all flex items-center gap-2 cursor-pointer">
                    <span>{{ __('auto.0175') }}</span>
                    <span class="px-2 py-0.5 text-xs rounded-full bg-surface-container text-on-surface-variant" x-text="items.length"></span>
                </button>
                <button @click="tab = 'unread'" 
                        :class="tab === 'unread' ? 'bg-surface-pure text-primary font-bold shadow-sm' : 'text-on-surface-variant hover:text-on-surface font-medium'"
                        class="px-4 py-2 rounded-lg text-sm transition-all flex items-center gap-2 cursor-pointer">
                    <span>{{ __('auto.0416') }}</span>
                    <span :class="unreadCount > 0 ? 'bg-danger text-white' : 'bg-surface-container text-on-surface-variant'" 
                          class="px-2 py-0.5 text-xs rounded-full font-bold" 
                          x-text="unreadCount"></span>
                </button>
            </div>

            <div class="text-xs text-on-surface-variant flex items-center gap-2">
                <span class="inline-block w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                <span>{{ __('auto.0584') }}</span>
            </div>
        </div>

        <!-- Notification Cards List -->
        <div class="space-y-3">
            <template x-for="item in filteredItems" :key="item.id">
                <div class="bg-surface-pure rounded-2xl p-5 border shadow-sm transition-all duration-200 hover:shadow-md flex flex-col md:flex-row items-start md:items-center justify-between gap-4 group"
                     :class="!item.read_at ? 'border-l-4 border-l-primary border-t-border-base border-r-border-base border-b-border-base bg-primary/[0.02]' : 'border-border-base opacity-90 hover:opacity-100'">
                    
                    <div class="flex items-start gap-4 flex-1">
                        <!-- Dynamic Type Icon Box -->
                        <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0 mt-0.5 shadow-sm"
                             :class="getIconBgClass(item.type)">
                            <span class="material-symbols-outlined text-2xl" x-text="getIconName(item.type)"></span>
                        </div>

                        <!-- Notification Main Content -->
                        <div class="space-y-1 flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <!-- Type Badge -->
                                <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-md uppercase tracking-wider"
                                      :class="getTypeBadgeClass(item.type)"
                                      x-text="getTypeLabel(item.type)"></span>

                                <!-- Title -->
                                <h3 class="text-base font-bold text-on-surface" 
                                    :class="{ 'text-primary font-black': !item.read_at }" 
                                    x-text="item.title"></h3>

                                <!-- Unread Indicator Dot -->
                                <template x-if="!item.read_at">
                                    <span class="w-2.5 h-2.5 rounded-full bg-primary flex-shrink-0 inline-block shadow-sm" title="{{ __('auto.0416') }}"></span>
                                </template>
                            </div>

                            <!-- Body -->
                            <p class="text-sm text-on-surface-variant leading-relaxed" x-text="item.body"></p>

                            <!-- Timestamp -->
                            <div class="flex items-center gap-3 text-xs text-on-surface-variant/70 pt-1">
                                <span class="flex items-center gap-1 font-mono">
                                    <span class="material-symbols-outlined text-sm">schedule</span>
                                    <span x-text="item.date_formatted"></span>
                                </span>
                                <span>•</span>
                                <span x-text="item.time_ago"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Card Action Buttons -->
                    <div class="flex items-center gap-2 self-end md:self-center pt-2 md:pt-0 border-t md:border-t-0 border-border-base/40 w-full md:w-auto justify-end">
                        <!-- Mark as Read Button (If Unread) -->
                        <template x-if="!item.read_at">
                            <button @click="markAsRead(item.id)" 
                                    type="button"
                                    class="px-3 py-1.5 rounded-xl bg-primary/10 text-primary hover:bg-primary/20 text-xs font-bold transition-all flex items-center gap-1.5 border border-primary/20 cursor-pointer"
                                    title="{{ __('auto.0435') }}">
                                <span class="material-symbols-outlined text-base">check_circle</span>
                                <span>{{ __('auto.0434') }}</span>
                            </button>
                        </template>

                        <!-- Delete Button -->
                        <button @click="deleteItem(item.id)" 
                                type="button"
                                class="px-3 py-1.5 rounded-xl bg-danger/10 text-danger hover:bg-danger/20 text-xs font-bold transition-all flex items-center gap-1.5 border border-danger/20 cursor-pointer opacity-70 hover:opacity-100"
                                title="{{ __('auto.0202') }}">
                            <span class="material-symbols-outlined text-base">delete</span>
                            <span>{{ __('common.delete') }}</span>
                        </button>
                    </div>
                </div>
            </template>

            <!-- Empty State -->
            <template x-if="filteredItems.length === 0">
                <div class="bg-surface-pure rounded-2xl p-12 text-center border border-border-base shadow-sm">
                    <div class="w-16 h-16 rounded-full bg-surface-container flex items-center justify-center text-on-surface-variant/40 mx-auto mb-3">
                        <span class="material-symbols-outlined text-4xl">notifications_off</span>
                    </div>
                    <h3 class="text-base font-bold text-on-surface mb-1">{{ __('auto.0306') }}</h3>
                    <p class="text-xs text-on-surface-variant" x-text="tab === 'unread' ? '您目前所有的通知都已標記為已讀！' : '目前暫時沒有收到任何站內通知。'"></p>
                </div>
            </template>
        </div>
    </div>
</x-app-layout>
