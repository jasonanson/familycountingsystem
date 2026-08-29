@extends('layouts.app')

@section('title', '家庭管理')

@section('content')
<div class="space-y-6">
    <!-- Header Actions -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">{{ __('nav.admin_families') }}</h1>
            <p class="text-sm text-on-surface-variant">{{ __('auto.0566') }}</p>
        </div>
        <button onclick="document.getElementById('createFamilyModal').classList.remove('hidden')" class="bg-primary hover:bg-primary-container text-white px-4 py-2 rounded-xl text-sm font-semibold transition-colors flex items-center gap-2 shadow-sm">
            <span class="material-symbols-outlined text-[20px]">add</span> 新增家庭
        </button>
    </div>

    <!-- Family Table Card -->
    <div class="bg-surface-pure rounded-xl border border-border-base shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[700px]">
                <thead>
                    <tr class="bg-background-warm border-b border-border-base text-xs text-on-surface-variant font-medium uppercase">
                        <th class="p-4 pl-6">{{ __('field.family_name') }}</th>
                        <th class="p-4">{{ __('auto.0342') }}</th>
                        <th class="p-4">{{ __('auto.0333') }}</th>
                        <th class="p-4">{{ __('auto.0601') }}</th>
                        <th class="p-4">{{ __('field.status') }}</th>
                        <th class="p-4 pr-6 text-right">{{ __('tx_page.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-base text-sm text-text-primary">
                    @forelse($families as $family)
                        <tr class="hover:bg-background-warm/50 transition-colors">
                            <td class="p-4 pl-6 font-semibold flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-sm">
                                    {{ mb_substr($family->name, 0, 1) }}
                                </div>
                                <span>{{ $family->name }}</span>
                            </td>
                            <td class="p-4 text-on-surface-variant">
                                {{ $family->owner ? $family->owner->name : '未設定' }}
                            </td>
                            <td class="p-4">
                                <span class="px-2.5 py-1 bg-surface-container rounded-lg text-xs font-semibold">
                                    {{ $family->members_count ?? $family->members()->count() }} 人
                                </span>
                            </td>
                            <td class="p-4 font-semibold text-primary">
                                NT$ {{ number_format($family->total_pool_amount ?? 0) }}
                            </td>
                            <td class="p-4">
                                @if($family->is_archived)
                                    <span class="px-2.5 py-1 bg-warning/10 text-warning rounded-md text-xs font-medium">{{ __('auto.0311') }}</span>
                                @else
                                    <span class="px-2.5 py-1 bg-success/10 text-success rounded-md text-xs font-medium">{{ __('auto.0442') }}</span>
                                @endif
                            </td>
                            <td class="p-4 pr-6 text-right space-x-2 whitespace-nowrap">
                                <button onclick="openAdminAiSummary({{ $family->id }}, '{{ addslashes($family->name) }}')" class="text-primary hover:text-primary/80 text-xs font-bold px-2 py-1 bg-primary/10 rounded-lg transition-colors cursor-pointer inline-flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">neurology</span>
                                    <span>AI 財務分析</span>
                                </button>
                                <button onclick="openEditModal({{ $family->id }}, '{{ addslashes($family->name) }}', {{ $family->total_pool_amount ?? 0 }})" class="text-on-surface-variant hover:text-on-surface text-xs font-medium">編輯</button>
                                <form action="{{ route('admin.families.destroy', $family) }}" method="POST" class="inline-block" onsubmit="return confirm('確定要封存此家庭嗎？')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-danger hover:text-danger text-xs font-medium">{{ __('common.delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-on-surface-variant">{{ __('auto.0536') }}</td>
                        </tr>
                    @endempty
                </tbody>
            </table>
        </div>
        @if($families->hasPages())
            <div class="p-4 border-t border-border-base">
                {{ $families->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal: Admin Family AI Analysis & Broadcast -->
<div id="adminAiModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-surface-pure rounded-2xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl border border-border-base space-y-5 relative">
        <div class="flex items-center justify-between pb-3 border-b border-border-base">
            <div class="flex items-center gap-2">
                <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-2xl">neurology</span>
                </div>
                <div>
                    <h3 class="text-base font-bold text-on-surface flex items-center gap-2">
                        <span id="aiModalFamilyName">{{ __('field.family_name') }}</span>
                        <span class="text-[10px] bg-primary/15 text-primary px-2 py-0.5 rounded-full font-bold">AI 即時健檢</span>
                    </h3>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('adminAiModal').classList.add('hidden')" class="text-on-surface-variant hover:text-on-surface p-1">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <div id="aiModalLoading" class="py-12 text-center space-y-3">
            <div class="inline-block animate-spin text-primary">
                <span class="material-symbols-outlined text-4xl">sync</span>
            </div>
            <p class="text-sm font-bold text-on-surface">Gemini 3.5 正在調閱該家庭收支、訂閱與預算進行深度分析...</p>
        </div>

        <div id="aiModalBody" style="display: none;" class="space-y-4 max-h-[60vh] overflow-y-auto pr-1">
            <!-- KPIs -->
            <div class="grid grid-cols-3 gap-3">
                <div class="p-3 bg-surface-container rounded-xl text-center">
                    <div class="text-[11px] text-on-surface-variant">本月總收入</div>
                    <div id="kpiIncome" class="text-base font-bold text-success mt-0.5">$NT 0</div>
                </div>
                <div class="p-3 bg-surface-container rounded-xl text-center">
                    <div class="text-[11px] text-on-surface-variant">本月總支出</div>
                    <div id="kpiExpense" class="text-base font-bold text-danger mt-0.5">$NT 0</div>
                </div>
                <div class="p-3 bg-surface-container rounded-xl text-center">
                    <div class="text-[11px] text-on-surface-variant">固定訂閱扣款</div>
                    <div id="kpiSub" class="text-base font-bold text-primary mt-0.5">0 筆</div>
                </div>
            </div>

            <!-- Analysis text -->
            <div class="p-4 rounded-xl bg-surface-container/40 border border-border-base">
                <h4 class="text-xs font-bold text-on-surface-variant uppercase mb-2">Gemini 3.5 AI 財務健檢建議</h4>
                <div id="aiModalText" class="text-xs sm:text-sm text-on-surface leading-relaxed whitespace-pre-line space-y-2"></div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-3 border-t border-border-base">
            <span class="text-xs text-on-surface-variant">💡 審核確認後，點擊右側按鈕將自動透過站內通知與 Email 廣播給該家庭全部家長。</span>
            <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                <button type="button" onclick="document.getElementById('adminAiModal').classList.add('hidden')" class="px-4 py-2 border border-border-base rounded-xl text-xs font-bold text-on-surface hover:bg-surface-container">{{ __('common.close') }}</button>
                <form id="adminSendAiReportForm" action="{{ route('family_ai_reports.generate') }}" method="POST">
                    @csrf
                    <input type="hidden" name="family_id" id="adminAiFamilyId">
                    <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary/90 text-white rounded-xl text-xs font-bold transition-all shadow-md flex items-center gap-1 cursor-pointer">
                        <span class="material-symbols-outlined text-sm">send</span>
                        <span>{{ __('auto.0488') }}</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Create Family -->
<div id="createFamilyModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-on-surface/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-surface-pure rounded-2xl max-w-md w-full p-6 shadow-xl border border-border-base">
        <h3 class="text-lg font-bold text-text-primary mb-4">{{ __('auto.0385') }}</h3>
        <form action="{{ route('admin.families.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-text-primary mb-1">{{ __('field.family_name') }}</label>
                <input type="text" name="name" required class="w-full px-3 py-2 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-text-primary mb-1">{{ __('auto.0602') }}</label>
                <input type="number" name="total_pool_amount" value="50000" class="w-full px-3 py-2 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('createFamilyModal').classList.add('hidden')" class="px-4 py-2 border border-border-base rounded-xl text-sm text-on-surface-variant hover:bg-surface-container">{{ __('common.cancel') }}</button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-xl text-sm font-semibold hover:bg-primary-container">{{ __('auto.0325') }}</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Family -->
<div id="editFamilyModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-on-surface/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-surface-pure rounded-2xl max-w-md w-full p-6 shadow-xl border border-border-base">
        <h3 class="text-lg font-bold text-text-primary mb-4">{{ __('auto.0589') }}</h3>
        <form id="editFamilyForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-text-primary mb-1">{{ __('field.family_name') }}</label>
                <input type="text" id="edit_name" name="name" required class="w-full px-3 py-2 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-text-primary mb-1">{{ __('auto.0602') }}</label>
                <input type="number" id="edit_total_pool_amount" name="total_pool_amount" class="w-full px-3 py-2 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('editFamilyModal').classList.add('hidden')" class="px-4 py-2 border border-border-base rounded-xl text-sm text-on-surface-variant hover:bg-surface-container">{{ __('common.cancel') }}</button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-xl text-sm font-semibold hover:bg-primary-container">{{ __('action.save_changes') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(id, name, amount) {
    document.getElementById('editFamilyForm').action = '{{ url("admin/families") }}/' + id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_total_pool_amount').value = amount;
    document.getElementById('editFamilyModal').classList.remove('hidden');
}

function openAdminAiSummary(familyId, familyName) {
    document.getElementById('aiModalFamilyName').textContent = familyName;
    document.getElementById('adminAiFamilyId').value = familyId;
    document.getElementById('adminAiModal').classList.remove('hidden');
    document.getElementById('aiModalLoading').style.display = 'block';
    document.getElementById('aiModalBody').style.display = 'none';

    fetch('{{ url("admin/families") }}/' + familyId + '/ai-summary')
        .then(res => res.json())
        .then(data => {
            document.getElementById('aiModalLoading').style.display = 'none';
            document.getElementById('aiModalBody').style.display = 'block';
            if (data.success) {
                document.getElementById('kpiIncome').textContent = '$NT ' + Number(data.metrics.total_income).toLocaleString();
                document.getElementById('kpiExpense').textContent = '$NT ' + Number(data.metrics.total_expense).toLocaleString();
                document.getElementById('kpiSub').textContent = data.metrics.subscription_count + ' 筆 ($NT ' + Number(data.metrics.subscription_total).toLocaleString() + ')';
                document.getElementById('aiModalText').textContent = data.analysis;
            } else {
                document.getElementById('aiModalText').textContent = data.message || '無法取得 AI 分析。';
            }
        })
        .catch(err => {
            document.getElementById('aiModalLoading').style.display = 'none';
            document.getElementById('aiModalBody').style.display = 'block';
            document.getElementById('aiModalText').textContent = '請求失敗: ' + err.message;
        });
}
</script>
@endsection

