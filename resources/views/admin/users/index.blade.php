@extends('layouts.app')

@section('title', '使用者管理')

@section('content')
<div class="space-y-6">
    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-text-primary flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-2xl">manage_accounts</span>
                <span>{{ __('auto.0576') }}</span>
            </h1>
            <p class="text-sm text-on-surface-variant">{{ __('auto.0565') }}</p>
        </div>
        <button onclick="document.getElementById('createUserModal').classList.remove('hidden')" class="bg-primary hover:bg-primary-container text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors flex items-center gap-2 shadow-sm self-start sm:self-auto">
            <span class="material-symbols-outlined text-[20px]">person_add</span> 新增帳號
        </button>
    </div>

    <!-- Filter Toolbar -->
    <div class="bg-surface-pure rounded-2xl border border-border-base p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
            <!-- Search Input -->
            <div class="sm:col-span-4 relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">search</span>
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="{{ __('auto.0364') }}" 
                       class="w-full pl-9 pr-3 py-2 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
            </div>

            <!-- Family Filter -->
            <div class="sm:col-span-3">
                <select name="family_id" class="w-full px-3 py-2 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                    <option value="all" {{ ($familyFilter === 'all' || empty($familyFilter)) ? 'selected' : '' }}>🏛️ 所有家庭 (全部)</option>
                    <option value="none" {{ $familyFilter === 'none' ? 'selected' : '' }}>⚠️ 未加入任何家庭</option>
                    @foreach($allFamilies as $fam)
                        <option value="{{ $fam->id }}" {{ $familyFilter == $fam->id ? 'selected' : '' }}>
                            🏡 {{ $fam->name }} ({{ $fam->members->count() }} 人)
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Role Filter -->
            <div class="sm:col-span-3">
                <select name="role" class="w-full px-3 py-2 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                    <option value="" {{ empty($roleFilter) ? 'selected' : '' }}>👥 所有身份角色</option>
                    <option value="admin" {{ $roleFilter === 'admin' ? 'selected' : '' }}>🛡️ 最高系統管理員</option>
                    <option value="parent" {{ $roleFilter === 'parent' ? 'selected' : '' }}>🧑 大人 / 家長</option>
                    <option value="child" {{ $roleFilter === 'child' ? 'selected' : '' }}>👶 小孩 / 兒童</option>
                </select>
            </div>

            <!-- Submit / Reset Buttons -->
            <div class="sm:col-span-2 flex gap-2">
                <button type="submit" class="flex-1 bg-surface-container-high hover:bg-surface-container text-text-primary px-3 py-2 rounded-xl text-sm font-semibold transition-colors">{{ __('tx_page.filter') }}</button>
                @if($search || ($familyFilter && $familyFilter !== 'all') || $roleFilter)
                    <a href="{{ route('admin.users.index') }}" class="px-3 py-2 border border-border-base rounded-xl text-sm text-on-surface-variant hover:bg-surface-container flex items-center justify-center" title="{{ __('auto.0472') }}">
                        <span class="material-symbols-outlined text-[18px]">restart_alt</span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- User Table Card -->
    <div class="bg-surface-pure rounded-2xl border border-border-base shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[900px]">
                <thead>
                    <tr class="bg-background-warm border-b border-border-base text-xs text-on-surface-variant font-semibold uppercase">
                        <th class="p-4 pl-6 whitespace-nowrap"><a href="{{ route('admin.users.index', array_merge(request()->query(), ['sort_by' => 'name', 'sort_dir' => (request('sort_by') == 'name' && request('sort_dir') == 'asc') ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-primary">使用者 @if(request('sort_by') == 'name')<span class="material-symbols-outlined text-[14px]">{{ request('sort_dir') == 'asc' ? "arrow_upward" : "arrow_downward" }}</span>@else<span class="material-symbols-outlined text-[14px] opacity-30">unfold_more</span>@endif</a></th>
                        <th class="p-4 whitespace-nowrap"><a href="{{ route('admin.users.index', array_merge(request()->query(), ['sort_by' => 'email', 'sort_dir' => (request('sort_by') == 'email' && request('sort_dir') == 'asc') ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-primary">帳號 / Email @if(request('sort_by') == 'email')<span class="material-symbols-outlined text-[14px]">{{ request('sort_dir') == 'asc' ? "arrow_upward" : "arrow_downward" }}</span>@else<span class="material-symbols-outlined text-[14px] opacity-30">unfold_more</span>@endif</a></th>
                        <th class="p-4 whitespace-nowrap"><a href="{{ route('admin.users.index', array_merge(request()->query(), ['sort_by' => 'is_system_admin', 'sort_dir' => (request('sort_by') == 'is_system_admin' && request('sort_dir') == 'asc') ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-primary">系統層級身分 @if(request('sort_by') == 'is_system_admin')<span class="material-symbols-outlined text-[14px]">{{ request('sort_dir') == 'asc' ? "arrow_upward" : "arrow_downward" }}</span>@else<span class="material-symbols-outlined text-[14px] opacity-30">unfold_more</span>@endif</a></th>
                        <th class="p-4 whitespace-nowrap">{{ __('auto.0343') }}</th>
                        <th class="p-4 whitespace-nowrap"><a href="{{ route('admin.users.index', array_merge(request()->query(), ['sort_by' => 'created_at', 'sort_dir' => (request('sort_by') == 'created_at' && request('sort_dir') == 'asc') ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-primary">註冊時間 @if(request('sort_by') == 'created_at')<span class="material-symbols-outlined text-[14px]">{{ request('sort_dir') == 'asc' ? "arrow_upward" : "arrow_downward" }}</span>@else<span class="material-symbols-outlined text-[14px] opacity-30">unfold_more</span>@endif</a></th>
                        <th class="p-4 pr-6 text-right whitespace-nowrap">{{ __('tx_page.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-base text-sm text-text-primary">
                    @forelse($users as $user)
                        <tr class="hover:bg-background-warm/50 transition-colors">
                            <!-- User Name & Avatar -->
                            <td class="p-4 pl-6 align-middle whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full shrink-0 {{ ($user->registration_role === 'child' || $user->currentFamilyRole() === 'child') ? 'bg-category-mint/20 text-category-mint' : 'bg-primary/10 text-primary' }} flex items-center justify-center font-bold text-sm border border-border-base">
                                        {{ mb_substr($user->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-text-primary flex items-center gap-1.5 whitespace-nowrap">
                                            <span>{{ $user->name }}</span>
                                            @if($user->is_system_admin)
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-danger/10 text-danger border border-danger/20 shrink-0">ADMIN</span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-on-surface-variant flex items-center gap-1 mt-0.5 whitespace-nowrap">
                                            <span class="material-symbols-outlined text-[14px]">
                                                {{ ($user->registration_role === 'child') ? 'child_care' : 'person' }}
                                            </span>
                                            <span>{{ ($user->registration_role === 'child') ? '註冊身分：小孩' : '註冊身分：大人' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Account / Email -->
                            <td class="p-4 align-middle whitespace-nowrap">
                                <div class="font-mono text-xs font-bold text-text-primary">{{ $user->account ?: '(無帳號代碼)' }}</div>
                                <div class="text-xs text-on-surface-variant">{{ $user->email }}</div>
                            </td>

                            <!-- System Level Role -->
                            <td class="p-4 align-middle whitespace-nowrap">
                                <form action="{{ route('admin.users.toggle_admin', $user) }}" method="POST" class="inline-block">
                                    @csrf
                                    <button type="submit" 
                                            class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all border inline-flex items-center gap-1 whitespace-nowrap {{ $user->is_system_admin ? 'bg-danger/10 text-danger border-danger/30 hover:bg-danger/20' : 'bg-surface-container text-on-surface-variant border-border-base hover:bg-surface-container-high' }}"
                                            title="{{ __('auto.0746') }}">
                                        <span class="material-symbols-outlined text-[14px]">{{ $user->is_system_admin ? 'shield_person' : 'person_outline' }}</span>
                                        <span>{{ $user->is_system_admin ? '最高系統管理員' : '一般使用者' }}</span>
                                    </button>
                                </form>
                            </td>

                            <!-- Families & Roles in each Family -->
                            <td class="p-4 align-middle">
                                <div class="flex flex-wrap gap-1.5 items-center">
                                    @forelse($user->families as $f)
                                        @php
                                            $roleInFamily = $f->pivot->role ?? 'parent';
                                            $isCurrent = ($user->current_family_id == $f->id);
                                        @endphp
                                        <button type="button" onclick="openManageFamilyModal({{ $user->id }}, '{{ addslashes($user->name) }}', {{ $user->current_family_id ?? 'null' }}, {{ json_encode($user->families->map(fn($f) => ['id' => $f->id, 'name' => $f->name, 'role' => $f->pivot->role])) }}, '{{ ($user->registration_role === 'child' || $user->currentFamilyRole() === 'child') ? 'child' : 'parent' }}')" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium border whitespace-nowrap transition-all hover:scale-[1.02] cursor-pointer {{ $isCurrent ? 'bg-primary/10 text-primary border-primary/30 ring-1 ring-primary/20 hover:bg-primary/15' : 'bg-surface-container-low hover:bg-surface-container text-text-primary border-border-base' }}" title="點擊管理此使用者的家庭歸屬與權限">
                                            @if($roleInFamily === 'child')
                                                <span class="material-symbols-outlined text-[14px] text-category-mint">child_care</span>
                                            @elseif($roleInFamily === 'parent')
                                                <span class="material-symbols-outlined text-[14px] text-primary">supervisor_account</span>
                                            @else
                                                <span class="material-symbols-outlined text-[14px] text-on-surface-variant">visibility</span>
                                            @endif
                                            <strong>{{ $f->name }}</strong>
                                            <span class="opacity-75">({{ $roleInFamily === 'child' ? '小孩' : ($roleInFamily === 'parent' ? '家長' : '訪客') }})</span>
                                            @if($isCurrent)
                                                <span class="text-[10px] font-bold bg-primary text-white px-1 rounded ml-0.5">{{ __('auto.0526') }}</span>
                                            @endif
                                        </button>
                                    @empty
                                        <button type="button" onclick="openManageFamilyModal({{ $user->id }}, '{{ addslashes($user->name) }}', null, [], '{{ ($user->registration_role === 'child') ? 'child' : 'parent' }}')" class="px-2 py-0.5 rounded text-xs bg-warning/10 text-warning hover:bg-warning/20 border border-warning/20 whitespace-nowrap transition-colors flex items-center gap-1 cursor-pointer" title="點擊為此使用者指派家庭">
                                            <span>⚠️ 未加入任何家庭 (無檢視權限)</span>
                                            <span class="material-symbols-outlined text-[14px]">add</span>
                                        </button>
                                    @endforelse
                                </div>
                            </td>

                            <!-- Created At (Registration Time) -->
                            <td class="p-4 align-middle whitespace-nowrap text-xs text-on-surface-variant">
                                {{ $user->created_at ? $user->created_at->format('Y-m-d H:i') : '-' }}
                            </td>

                            <!-- Actions -->
                            <td class="p-4 pr-6 align-middle text-right whitespace-nowrap">
                                <div class="inline-flex items-center justify-end gap-2">
                                    <button onclick="openManageFamilyModal({{ $user->id }}, '{{ addslashes($user->name) }}', {{ $user->current_family_id ?? 'null' }}, {{ json_encode($user->families->map(fn($f) => ['id' => $f->id, 'name' => $f->name, 'role' => $f->pivot->role])) }}, '{{ ($user->registration_role === 'child' || $user->currentFamilyRole() === 'child') ? 'child' : 'parent' }}')" class="px-2.5 py-1.5 rounded-lg border border-border-base hover:border-primary text-primary hover:bg-primary/5 text-xs font-semibold transition-colors whitespace-nowrap flex items-center gap-1 cursor-pointer">
                                        <span class="material-symbols-outlined text-[15px]">manage_accounts</span>
                                        <span>指派/管理家庭</span>
                                    </button>
                                    <button onclick="openEditUserModal({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ addslashes($user->email) }}', '{{ addslashes($user->account ?? '') }}', '{{ $user->registration_role ?? 'parent' }}', {{ $user->current_family_id ?? 'null' }}, '{{ $user->currentFamilyRole() ?? 'parent' }}')" class="px-2.5 py-1.5 rounded-lg border border-border-base hover:bg-surface-container text-on-surface-variant hover:text-text-primary text-xs font-semibold transition-colors whitespace-nowrap">
                                        編輯
                                    </button>
                                    @if(auth()->id() !== $user->id)
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline-block" onsubmit="return confirm('確定要永久刪除此使用者帳號 ({{ addslashes($user->name) }}) 嗎？')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2.5 py-1.5 rounded-lg border border-danger/20 text-danger hover:bg-danger/10 text-xs font-semibold transition-colors whitespace-nowrap">{{ __('common.delete') }}</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-12 text-center text-on-surface-variant">
                                查無符合條件的使用者資料。
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div class="p-4 border-t border-border-base bg-surface-pure">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal: Create User -->
<div id="createUserModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-on-surface/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-surface-pure rounded-3xl max-w-md w-full p-6 shadow-2xl border border-border-base">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-text-primary flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">person_add</span>
                <span>{{ __('auto.0381') }}</span>
            </h3>
            <button onclick="document.getElementById('createUserModal').classList.add('hidden')" class="text-on-surface-variant hover:text-text-primary">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-text-primary uppercase mb-1">姓名 / 顯示名稱 <span class="text-danger">*</span></label>
                <input type="text" name="name" required placeholder="{{ __('auto.0139') }}" class="w-full px-3.5 py-2.5 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
            </div>
            <div>
                <label class="block text-xs font-bold text-text-primary uppercase mb-1">登入帳號 (Account) <span class="text-danger">*</span></label>
                <input type="text" name="account" required placeholder="{{ __('auto.0128') }}" class="w-full px-3.5 py-2.5 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
            </div>
            <div>
                <label class="block text-xs font-bold text-text-primary uppercase mb-1">電子信箱 (Email) <span class="text-danger">*</span></label>
                <input type="email" name="email" required placeholder="ming@example.com" class="w-full px-3.5 py-2.5 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
            </div>
            <div>
                <label class="block text-xs font-bold text-text-primary uppercase mb-1">預設密碼 <span class="text-danger">*</span></label>
                <input type="password" name="password" required value="password" class="w-full px-3.5 py-2.5 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-text-primary uppercase mb-1">{{ __('auto.0199') }}</label>
                    <select name="family_id" class="w-full px-3.5 py-2.5 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                        <option value="">-- 先不加入任何家庭 --</option>
                        @foreach($allFamilies as $f)
                            <option value="{{ $f->id }}">{{ $f->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-text-primary uppercase mb-1">{{ __('auto.0275') }}</label>
                    <select name="family_role" class="w-full px-3.5 py-2.5 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                        <option value="parent">🧑 大人 / 家長</option>
                        <option value="child">👶 小孩 / 兒童</option>
                        <option value="viewer">👀 觀察者 / 訪客</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" name="is_system_admin" id="create_is_system_admin" class="rounded text-primary focus:ring-primary h-4 w-4">
                <label for="create_is_system_admin" class="text-sm font-medium text-text-primary">{{ __('auto.0631') }}</label>
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t border-border-base">
                <button type="button" onclick="document.getElementById('createUserModal').classList.add('hidden')" class="px-4 py-2 border border-border-base rounded-xl text-sm text-on-surface-variant hover:bg-surface-container">{{ __('common.cancel') }}</button>
                <button type="submit" class="px-5 py-2 bg-primary text-white rounded-xl text-sm font-bold hover:bg-primary-container shadow-sm">{{ __('auto.0326') }}</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Assign / Manage Families for User -->
<div id="manageFamilyModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-on-surface/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-surface-pure rounded-3xl max-w-xl w-full p-6 sm:p-7 shadow-2xl border border-border-base space-y-6">
        <div class="flex justify-between items-center pb-3 border-b border-border-base">
            <div>
                <h3 class="text-lg font-bold text-text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-2xl">family_restroom</span>
                    <span>家庭權限與指派管理</span>
                </h3>
                <p class="text-xs text-on-surface-variant mt-0.5">目標使用者：<strong id="manage_user_name" class="text-primary font-bold"></strong></p>
            </div>
            <button onclick="document.getElementById('manageFamilyModal').classList.add('hidden')" class="text-on-surface-variant hover:text-text-primary p-1 rounded-lg hover:bg-surface-container">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- 1. 現有已加入家庭列表與權限設定 -->
        <div>
            <div class="flex items-center justify-between mb-2">
                <label class="block text-xs font-bold text-text-primary uppercase tracking-wider">目前已加入之家庭與權限</label>
                <span id="joinedCountBadge" class="text-[11px] font-semibold text-on-surface-variant"></span>
            </div>
            <div id="joinedFamiliesList" class="space-y-2.5 max-h-56 overflow-y-auto pr-1">
                <!-- Populated by JS -->
            </div>
        </div>

        <!-- 2. 指派/加入新家庭 -->
        <div class="pt-4 border-t border-border-base">
            <label class="block text-xs font-bold text-text-primary uppercase tracking-wider mb-2.5 flex items-center gap-1">
                <span class="material-symbols-outlined text-base text-primary">add_circle</span>
                指派加入新家庭
            </label>
            <form id="attachFamilyForm" method="POST" class="space-y-3 bg-surface-container-low p-4 rounded-2xl border border-border-base">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-on-surface-variant uppercase mb-1">選擇目標家庭 <span class="text-danger">*</span></label>
                        <select name="family_id" id="assign_family_id" required class="w-full px-3 py-2 bg-surface-pure border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                            <option value="">-- 請選擇家庭 --</option>
                            @foreach($allFamilies as $f)
                                <option value="{{ $f->id }}">🏡 {{ $f->name }} ({{ $f->members->count() }} 人)</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-on-surface-variant uppercase mb-1">家庭內權限身分 <span class="text-danger">*</span></label>
                        <select name="role" class="w-full px-3 py-2 bg-surface-pure border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                            <option value="parent">🧑 大人 / 家長</option>
                            <option value="child">👶 小孩 / 兒童</option>
                            <option value="viewer">👀 觀察者 / 訪客</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pt-1">
                    <label class="inline-flex items-center gap-2 text-xs font-medium text-text-primary cursor-pointer">
                        <input type="checkbox" name="set_as_current" value="1" class="rounded text-primary focus:ring-primary h-4 w-4">
                        <span>同時設為該使用者的主要檢視家庭</span>
                    </label>
                    <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary/90 text-white rounded-xl text-xs font-bold transition-all shadow-sm flex items-center justify-center gap-1 shrink-0 cursor-pointer">
                        <span class="material-symbols-outlined text-sm">person_add</span>
                        指派加入家庭
                    </button>
                </div>
            </form>
        </div>

        <!-- 3. 危險操作：一鍵移除所有家庭 -->
        <div class="pt-3 border-t border-border-base flex items-center justify-between">
            <form id="detachAllFamiliesForm" method="POST" onsubmit="return confirm('⚠️ 確定要將該使用者從【所有家庭】中全部移出嗎？\n\n這將撤銷該使用者在系統中所有家庭的檢視與記帳權限！')">
                @csrf
                <button type="submit" id="detachAllBtn" class="text-danger hover:bg-danger/10 px-3 py-1.5 rounded-xl text-xs font-bold transition-colors inline-flex items-center gap-1 border border-danger/20 cursor-pointer">
                    <span class="material-symbols-outlined text-sm">group_remove</span>
                    移出所有家庭 (清空家庭權限)
                </button>
            </form>
            <button type="button" onclick="document.getElementById('manageFamilyModal').classList.add('hidden')" class="px-4 py-2 border border-border-base rounded-xl text-xs font-bold text-on-surface-variant hover:bg-surface-container cursor-pointer">
                關閉視窗
            </button>
        </div>
    </div>
</div>

<!-- Modal: Edit User -->
<div id="editUserModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-on-surface/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-surface-pure rounded-3xl max-w-md w-full p-6 shadow-2xl border border-border-base">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-text-primary flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">edit_square</span>
                <span>{{ __('auto.0587') }}</span>
            </h3>
            <button onclick="document.getElementById('editUserModal').classList.add('hidden')" class="text-on-surface-variant hover:text-text-primary">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form id="editUserForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-bold text-text-primary uppercase mb-1">姓名 / 顯示名稱 <span class="text-danger">*</span></label>
                <input type="text" id="edit_user_name" name="name" required class="w-full px-3.5 py-2.5 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
            </div>
            <div>
                <label class="block text-xs font-bold text-text-primary uppercase mb-1">電子信箱 (Email) <span class="text-danger">*</span></label>
                <input type="email" id="edit_user_email" name="email" required class="w-full px-3.5 py-2.5 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
            </div>
            <div>
                <label class="block text-xs font-bold text-text-primary uppercase mb-1">{{ __('auto.0509') }}</label>
                <input type="text" id="edit_user_account" name="account" class="w-full px-3.5 py-2.5 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
            </div>
            <div>
                <label class="block text-xs font-bold text-text-primary uppercase mb-1">{{ __('auto.0633') }}</label>
                <select id="edit_user_registration_role" name="registration_role" class="w-full px-3.5 py-2.5 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                    <option value="parent">🧑 大人 / 家長</option>
                    <option value="child">👶 小孩 / 兒童</option>
                    <option value="member">👥 家庭成員</option>
                    <option value="guest">👀 訪客</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-text-primary uppercase mb-1">主要預設家庭</label>
                    <select id="edit_user_family_id" name="family_id" class="w-full px-3.5 py-2.5 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                        <option value="none">⚠️ 未加入任何家庭</option>
                        @foreach($allFamilies as $f)
                            <option value="{{ $f->id }}">{{ $f->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-text-primary uppercase mb-1">家庭內權限角色</label>
                    <select id="edit_user_family_role" name="family_role" class="w-full px-3.5 py-2.5 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                        <option value="parent">🧑 大人 / 家長</option>
                        <option value="child">👶 小孩 / 兒童</option>
                        <option value="viewer">👀 觀察者 / 訪客</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-text-primary uppercase mb-1">{{ __('auto.0703') }}</label>
                <input type="password" name="password" placeholder="{{ __('auto.0615') }}" class="w-full px-3.5 py-2.5 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
            </div>
            <div class="flex justify-end gap-3 pt-3 border-t border-border-base">
                <button type="button" onclick="document.getElementById('editUserModal').classList.add('hidden')" class="px-4 py-2 border border-border-base rounded-xl text-sm text-on-surface-variant hover:bg-surface-container cursor-pointer">{{ __('common.cancel') }}</button>
                <button type="submit" class="px-5 py-2 bg-primary text-white rounded-xl text-sm font-bold hover:bg-primary-container shadow-sm cursor-pointer">{{ __('action.save_changes') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
function openManageFamilyModal(userId, userName, currentFamilyId, joinedFamilies, defaultRole = 'parent') {
    const baseUrl = '{{ url("admin/users") }}/' + userId;
    document.getElementById('attachFamilyForm').action = baseUrl + '/families/attach';
    document.getElementById('detachAllFamiliesForm').action = baseUrl + '/families/detach-all';
    document.getElementById('manage_user_name').innerText = userName;
    document.getElementById('assign_family_id').value = '';
    
    const countBadge = document.getElementById('joinedCountBadge');
    const detachAllBtn = document.getElementById('detachAllBtn');
    const listContainer = document.getElementById('joinedFamiliesList');
    listContainer.innerHTML = '';

    if (!joinedFamilies || joinedFamilies.length === 0) {
        countBadge.innerText = '共 0 個家庭';
        detachAllBtn.style.display = 'none';
        listContainer.innerHTML = '<div class="p-4 bg-warning/10 border border-warning/20 rounded-2xl text-center text-xs text-warning font-bold flex items-center justify-center gap-1.5"><span class="material-symbols-outlined text-base">warning</span>目前該使用者未加入任何家庭（無任何家庭檢視權限）</div>';
    } else {
        countBadge.innerText = '共 ' + joinedFamilies.length + ' 個家庭';
        detachAllBtn.style.display = 'inline-flex';
        
        joinedFamilies.forEach(f => {
            const isCurrent = (currentFamilyId == f.id);
            const role = f.role || 'parent';
            const roleLabel = (role === 'child') ? '小孩' : (role === 'parent' ? '家長' : '訪客');
            const card = document.createElement('div');
            card.className = 'p-3 rounded-2xl border ' + (isCurrent ? 'border-primary/40 bg-primary/5 shadow-sm' : 'border-border-base bg-surface-container-low') + ' text-xs space-y-2';
            
            card.innerHTML = `
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="material-symbols-outlined text-[18px] text-primary shrink-0">home</span>
                        <strong class="text-text-primary text-sm truncate">${f.name}</strong>
                        ${isCurrent ? '<span class="px-2 py-0.5 bg-primary text-white rounded-md text-[10px] font-black shrink-0">主要檢視家庭</span>' : ''}
                    </div>
                    
                    <div class="flex items-center gap-1.5 shrink-0">
                        ${!isCurrent ? `
                            <form action="${baseUrl}/families/${f.id}/set-primary" method="POST" class="inline">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <button type="submit" class="px-2 py-1 bg-surface-pure hover:bg-surface-container text-primary border border-border-base rounded-lg text-[11px] font-bold transition-colors cursor-pointer" title="切換為該使用者的主要預設家庭">
                                    設為主要
                                </button>
                            </form>
                        ` : ''}
                        
                        <form action="${baseUrl}/families/${f.id}" method="POST" class="inline" onsubmit="return confirm('確定要將使用者從【${f.name}】家庭中移出（踢出家庭）嗎？\n\n移出後該使用者將無法再檢視此家庭的記帳資料！')">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="px-2 py-1 bg-danger/10 hover:bg-danger text-danger hover:text-white border border-danger/25 rounded-lg text-[11px] font-bold transition-colors flex items-center gap-0.5 cursor-pointer" title="移出此家庭">
                                <span class="material-symbols-outlined text-[13px]">person_remove</span> 踢出家庭
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- 角色權限即時變更 -->
                <div class="flex items-center justify-between gap-2 pt-1 border-t border-border-base/60 text-[11px]">
                    <span class="text-on-surface-variant flex items-center gap-1 shrink-0">
                        <span class="material-symbols-outlined text-[14px]">badge</span> 身分權限：
                    </span>
                    <form action="${baseUrl}/families/${f.id}/role" method="POST" class="flex items-center gap-1.5 flex-1 justify-end">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="_method" value="PUT">
                        <select name="role" class="px-2 py-1 bg-surface-pure border border-border-base rounded-lg text-xs font-semibold focus:outline-none focus:border-primary">
                            <option value="parent" ${role === 'parent' ? 'selected' : ''}>🧑 大人 / 家長</option>
                            <option value="child" ${role === 'child' ? 'selected' : ''}>👶 小孩 / 兒童</option>
                            <option value="viewer" ${role === 'viewer' ? 'selected' : ''}>👀 觀察者 / 訪客</option>
                        </select>
                        <button type="submit" class="px-2 py-1 bg-surface-container-high hover:bg-surface-container text-text-primary rounded-lg font-bold transition-colors cursor-pointer">
                            更新身分
                        </button>
                    </form>
                </div>
            `;
            listContainer.appendChild(card);
        });
    }

    document.getElementById('manageFamilyModal').classList.remove('hidden');
}

function openEditUserModal(userId, name, email, account, registrationRole = 'parent', currentFamilyId = 'none', familyRole = 'parent') {
    document.getElementById('editUserForm').action = '{{ url("admin/users") }}/' + userId;
    document.getElementById('edit_user_name').value = name;
    document.getElementById('edit_user_email').value = email;
    document.getElementById('edit_user_account').value = account;
    const regRoleSelect = document.getElementById('edit_user_registration_role');
    if (regRoleSelect) {
        regRoleSelect.value = registrationRole || 'parent';
    }
    const famSelect = document.getElementById('edit_user_family_id');
    if (famSelect) {
        famSelect.value = (currentFamilyId && currentFamilyId !== 'null') ? String(currentFamilyId) : 'none';
    }
    const famRoleSelect = document.getElementById('edit_user_family_role');
    if (famRoleSelect) {
        famRoleSelect.value = familyRole || 'parent';
    }
    document.getElementById('editUserModal').classList.remove('hidden');
}
</script>
@endsection
