<x-app-layout>
    <x-slot name="title">{{ __('auto.0647') }}</x-slot>

    <div class="space-y-8">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-surface-pure border border-border-base p-6 rounded-2xl shadow-sm">
            <div>
                <h1 class="text-2xl font-bold text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-3xl">import_export</span>
                    <span>{{ __('auto.0647') }}</span>
                </h1>
                <p class="text-sm text-on-surface-variant mt-1">
                    匯出家庭記帳明細為標準 CSV 報表，或透過「CSV 對帳單精靈」批次匯入銀行與信用卡帳單，自動過濾重複紀錄。
                </p>
            </div>
            <div class="flex items-center gap-3">
                <span class="px-3 py-1.5 rounded-xl bg-primary/10 text-primary text-xs font-bold border border-primary/20 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">verified</span>
                    <span>Phase 5 模組已就緒</span>
                </span>
            </div>
        </div>

        <!-- Quick Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-surface-pure border border-border-base rounded-2xl p-5 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center text-2xl flex-shrink-0">
                    <span class="material-symbols-outlined">receipt_long</span>
                </div>
                <div>
                    <div class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">歷史交易筆數</div>
                    <div class="text-2xl font-black text-on-surface mt-0.5">{{ number_format($totalTransactions ?? 0) }} 筆</div>
                </div>
            </div>

            <div class="bg-surface-pure border border-border-base rounded-2xl p-5 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-secondary-container/50 text-secondary flex items-center justify-center text-2xl flex-shrink-0">
                    <span class="material-symbols-outlined">account_balance</span>
                </div>
                <div>
                    <div class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">關聯對帳帳戶</div>
                    <div class="text-2xl font-black text-on-surface mt-0.5">{{ count($accounts ?? []) }} 個</div>
                </div>
            </div>

            <div class="bg-surface-pure border border-border-base rounded-2xl p-5 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-warning/15 text-warning flex items-center justify-center text-2xl flex-shrink-0">
                    <span class="material-symbols-outlined">category</span>
                </div>
                <div>
                    <div class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">收支分類項目</div>
                    <div class="text-2xl font-black text-on-surface mt-0.5">{{ count($categories ?? []) }} 個</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- CSV Export Panel -->
            <div class="bg-surface-pure border border-border-base rounded-2xl p-6 shadow-sm space-y-6 flex flex-col justify-between">
                <div class="space-y-4">
                    <div class="flex items-center gap-3 border-b border-border-base pb-4">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                            <span class="material-symbols-outlined text-2xl">download</span>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-on-surface">1. CSV 交易明細匯出</h2>
                            <p class="text-xs text-on-surface-variant">{{ __('auto.0218') }}</p>
                        </div>
                    </div>

                    <!-- Included Columns List -->
                    <div class="bg-surface-container-low p-4 rounded-xl space-y-2 border border-border-base/60">
                        <div class="text-xs font-bold text-on-surface flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-primary text-sm">check_circle</span>
                            <span>{{ __('auto.0216') }}</span>
                        </div>
                        <div class="flex flex-wrap gap-2 text-xs font-mono">
                            <span class="px-2 py-1 rounded bg-surface-pure border text-primary font-bold">1. 日期</span>
                            <span class="px-2 py-1 rounded bg-surface-pure border text-primary font-bold">2. 類型</span>
                            <span class="px-2 py-1 rounded bg-surface-pure border text-primary font-bold">3. 分類</span>
                            <span class="px-2 py-1 rounded bg-surface-pure border text-primary font-bold">4. 帳戶</span>
                            <span class="px-2 py-1 rounded bg-surface-pure border text-primary font-bold">5. 金額</span>
                            <span class="px-2 py-1 rounded bg-surface-pure border text-primary font-bold">6. 交易人</span>
                            <span class="px-2 py-1 rounded bg-surface-pure border text-primary font-bold">7. 備註</span>
                        </div>
                    </div>

                    <!-- Export Filter Form -->
                    <form action="{{ route('data_exchange.export') }}" method="GET" class="space-y-4 pt-2">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-on-surface mb-1">{{ __('auto.0707') }}</label>
                                <input type="date" name="date_from" class="w-full bg-background-warm border border-border-base text-sm rounded-xl px-3 py-2 focus:outline-none focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-on-surface mb-1">{{ __('auto.0586') }}</label>
                                <input type="date" name="date_to" class="w-full bg-background-warm border border-border-base text-sm rounded-xl px-3 py-2 focus:outline-none focus:border-primary">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-on-surface mb-1">{{ __('auto.0106') }}</label>
                            <select name="type" class="w-full bg-background-warm border border-border-base text-sm rounded-xl px-3 py-2 focus:outline-none focus:border-primary">
                                <option value="all">{{ __('auto.0177') }}</option>
                                <option value="expense">{{ __('auto.0157') }}</option>
                                <option value="income">{{ __('auto.0158') }}</option>
                                <option value="transfer">{{ __('auto.0159') }}</option>
                            </select>
                        </div>

                        <div class="pt-4 border-t border-border-base">
                            <button type="submit" class="w-full py-3 bg-primary hover:bg-primary/90 text-on-primary font-bold rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined">download</span>
                                <span>{{ __('auto.0557') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- CSV Import Panel Wizard -->
            <div class="bg-surface-pure border border-border-base rounded-2xl p-6 shadow-sm space-y-6">
                <div class="flex items-center gap-3 border-b border-border-base pb-4">
                    <div class="w-10 h-10 rounded-xl bg-success/10 text-success flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl">upload_file</span>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-on-surface">2. CSV 匯入對帳單精靈</h2>
                        <p class="text-xs text-on-surface-variant">{{ __('auto.0370') }}</p>
                    </div>
                </div>

                <!-- Import Form -->
                <form action="{{ route('data_exchange.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <!-- File Input Box -->
                    <div class="space-y-1">
                        <label class="block text-sm font-bold text-on-surface">選擇 CSV 對帳單檔案 <span class="text-danger">*</span></label>
                        <div class="border-2 border-dashed border-border-base hover:border-primary/50 bg-background-warm rounded-2xl p-4 text-center transition-colors">
                            <span class="material-symbols-outlined text-3xl text-primary mb-1">cloud_upload</span>
                            <input type="file" name="csv_file" accept=".csv,text/csv" required
                                   class="w-full text-xs text-on-surface-variant file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 cursor-pointer">
                            <p class="text-[11px] text-on-surface-variant mt-2">{{ __('auto.0644') }}</p>
                        </div>
                    </div>

                    <!-- Features & Rules -->
                    <div class="bg-surface-container-low p-4 rounded-xl space-y-2 border border-border-base/60">
                        <div class="text-xs font-bold text-on-surface flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-success text-base">auto_fix_high</span>
                            <span>{{ __('auto.0301') }}</span>
                        </div>
                        <ul class="text-xs text-on-surface-variant space-y-1 list-disc pl-4 leading-relaxed">
                            <li><strong class="text-on-surface">過濾重複紀錄：</strong> 系統將依據日期、金額與描述比對，自動跳過已存在的交易。</li>
                            <li><strong class="text-on-surface">智慧欄位對映：</strong> 自動判讀「日期、類型、分類、帳戶、金額、交易人、備註」。</li>
                            <li><strong class="text-on-surface">自動建立新分類：</strong> 遇到資料庫未包含之新分類時將自動建立。</li>
                        </ul>
                    </div>

                    <div class="pt-2 border-t border-border-base">
                        <button type="submit" class="w-full py-3 bg-success hover:bg-success/90 text-white font-bold rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined">play_arrow</span>
                            <span>{{ __('auto.0706') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sample CSV Format Guide -->
        <div class="bg-surface-pure border border-border-base rounded-2xl p-6 shadow-sm space-y-4">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-primary text-2xl">table_chart</span>
                <h3 class="text-base font-bold text-on-surface">{{ __('auto.0432') }}</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs font-mono text-on-surface border border-border-base rounded-xl overflow-hidden">
                    <thead class="bg-surface-container font-bold text-on-surface-variant border-b border-border-base">
                        <tr>
                            <th class="px-4 py-2.5">{{ __('auto.0394') }}</th>
                            <th class="px-4 py-2.5">{{ __('auto.0738') }}</th>
                            <th class="px-4 py-2.5">{{ __('auto.0191') }}</th>
                            <th class="px-4 py-2.5">{{ __('auto.0317') }}</th>
                            <th class="px-4 py-2.5">{{ __('auto.0704') }}</th>
                            <th class="px-4 py-2.5">{{ __('auto.0098') }}</th>
                            <th class="px-4 py-2.5">{{ __('auto.0151') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-base bg-background-warm">
                        <tr>
                            <td class="px-4 py-2">2026-08-26 12:30:00</td>
                            <td class="px-4 py-2 text-danger font-bold">{{ __('child_page.withdraw') }}</td>
                            <td class="px-4 py-2">{{ __('auto.0742') }}</td>
                            <td class="px-4 py-2">{{ __('auto.0487') }}</td>
                            <td class="px-4 py-2 font-bold">350.00</td>
                            <td class="px-4 py-2">{{ __('auto.0483') }}</td>
                            <td class="px-4 py-2">{{ __('auto.0172') }}</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2">2026-08-25 09:00:00</td>
                            <td class="px-4 py-2 text-success font-bold">{{ __('direction.in') }}</td>
                            <td class="px-4 py-2">{{ __('auto.0616') }}</td>
                            <td class="px-4 py-2">{{ __('auto.0228') }}</td>
                            <td class="px-4 py-2 font-bold">60000.00</td>
                            <td class="px-4 py-2">{{ __('auto.0262') }}</td>
                            <td class="px-4 py-2">8月份月薪發放</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-on-surface-variant">
                💡 提示：欄位順序可自由調換，精靈會透過首行 Header 標頭名稱自動辨識對映！
            </p>
        </div>
    </div>
</x-app-layout>
