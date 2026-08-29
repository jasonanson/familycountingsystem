@extends('layouts.app')

@section('content')
<div x-data="{ showDirectModal: false, showInviteModal: false, copied: false }" class="space-y-6">

    <!-- Header Card -->
    <div class="bg-surface-pure border border-border-base rounded-2xl p-6 shadow-sm relative overflow-hidden">
        <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-primary/5 rounded-full blur-2xl pointer-events-none"></div>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-xs font-bold text-primary uppercase tracking-wider">
                    <span class="material-symbols-outlined text-base">roofing</span>
                    <span>{{ __('auto.0495') }}</span>
                </div>
                <h1 class="text-2xl font-black text-on-surface flex items-center gap-2">
                    <span>{{ $family ? $family->name : '無家庭' }}</span>
                    @if($family && $family->invite_code)
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-primary/10 text-primary border border-primary/20" title="{{ __('auth.invite_code') }}">
                            🔑 {{ $family->invite_code }}
                        </span>
                    @endif
                </h1>
                <p class="text-sm text-on-surface-variant">{{ __('auto.0572') }}</p>
            </div>

            @if($isParent && auth()->user()->canEditCurrentFamily())
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Flow A Trigger -->
                    <button @click="showDirectModal = true" class="px-4 py-2.5 bg-primary hover:bg-primary/90 text-white font-bold rounded-xl shadow-sm transition-all hover:scale-[1.02] flex items-center gap-2 text-sm">
                        <span class="material-symbols-outlined text-lg">person_add</span>
                        <span>{{ __('auto.0549') }}</span>
                    </button>

                    <!-- Flow B Trigger -->
                    <button @click="showInviteModal = true" class="px-4 py-2.5 bg-surface-pure hover:bg-primary/5 text-primary border border-primary/30 font-bold rounded-xl shadow-sm transition-all hover:scale-[1.02] flex items-center gap-2 text-sm">
                        <span class="material-symbols-outlined text-lg">mail</span>
                        <span>{{ __('auto.0492') }}</span>
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- Generated Invitation Link Alert (Flow B Result) -->
    @if(session('invited_url'))
        <div class="bg-success/10 border border-success/50/30 rounded-2xl p-5 shadow-sm space-y-3" x-data="{ copiedLink: false }">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 text-success font-bold text-base">
                    <span class="material-symbols-outlined text-xl">mark_email_read</span>
                    <span>{{ __('auto.0268') }}</span>
                </div>
                <span class="text-xs text-success font-semibold">{{ __('auto.0411') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <input type="text" readonly value="{{ session('invited_url') }}" id="invitedUrlInput" class="flex-1 px-4 py-2.5 bg-white border border-success/30 rounded-xl text-sm font-mono text-success focus:outline-none select-all">
                <button @click="navigator.clipboard.writeText('{{ session('invited_url') }}'); copiedLink = true; setTimeout(() => copiedLink = false, 2500)" class="px-4 py-2.5 bg-success hover:bg-success text-white font-bold rounded-xl text-sm transition-all flex items-center gap-1.5 flex-shrink-0">
                    <span class="material-symbols-outlined text-lg" x-text="copiedLink ? 'check' : 'content_copy'"></span>
                    <span x-text="copiedLink ? '已複製連結！' : '一鍵複製'"></span>
                </button>
            </div>
            <p class="text-xs text-success">{{ __('auto.0638') }}</p>
        </div>
    @endif

    <!-- Member List -->
    <div class="bg-surface-pure border border-border-base rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-border-base flex items-center justify-between bg-surface-container-low/50">
            <h2 class="font-bold text-lg text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">group</span>
                <span>家庭成員列表 ({{ $members->count() }} 人)</span>
            </h2>
            <span class="text-xs text-on-surface-variant font-medium">您的身份：
                <span class="font-bold text-primary">{{ $isParent ? '👑 家長 (管理者)' : '成員' }}</span>
            </span>
        </div>

        <div class="divide-y divide-border-base">
            @forelse($members as $member)
                @php
                    $role = $member->pivot->role ?? 'member';
                    $isActive = $member->pivot->is_active ?? true;
                @endphp
                <div class="p-4 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-surface-container-low/30 transition-colors">
                    <div class="flex items-center gap-4">
                        <!-- Avatar -->
                        @if($member->avatar_url)
                            <img src="{{ asset($member->avatar_url) }}" alt="{{ $member->name }}" class="w-12 h-12 rounded-full object-cover border border-primary/20 shadow-sm">
                        @else
                            <div class="w-12 h-12 rounded-full bg-primary/10 text-primary font-bold flex items-center justify-center text-lg border border-primary/20 shadow-sm">
                                {{ mb_substr($member->name, 0, 1) }}
                            </div>
                        @endif

                        <div class="space-y-0.5">
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-base text-on-surface">{{ $member->name }}</h3>

                                <!-- Role Badge -->
                                @if($role === 'parent')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-warning/10 text-warning border border-warning/50/20 flex items-center gap-0.5">
                                        <span>👑 家長</span>
                                    </span>
                                @elseif($role === 'child')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-category-sky/10 text-category-sky border border-category-sky/20 flex items-center gap-0.5">
                                        <span>👶 孩童</span>
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-on-surface-variant/10 text-on-surface-variant border border-on-surface-variant/20">
                                        <span>{{ __('tip.guest') }}</span>
                                    </span>
                                @endif

                                <!-- Active Status Badge -->
                                @if($isActive)
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-success/15 text-success border border-success/50/20">
                                        ● 啟用中
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-danger/10 text-danger border border-danger/20">
                                        ○ 已停用
                                    </span>
                                @endif
                            </div>

                            <div class="text-xs text-on-surface-variant flex flex-wrap items-center gap-3">
                                <span>帳號: <strong class="font-mono text-on-surface">{{ $member->account }}</strong></span>
                                <span>Email: <span class="font-mono">{{ $member->email }}</span></span>
                                @if($member->pivot->joined_at)
                                    <span class="text-on-surface-variant/70">加入時間: {{ \Carbon\Carbon::parse($member->pivot->joined_at)->format('Y-m-d') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Parent Actions -->
                    @if($isParent && auth()->user()->canEditCurrentFamily())
                        <div class="flex items-center gap-2 self-end sm:self-center">
                            <!-- Toggle Status Form -->
                            <form action="{{ route('members.toggle', $member->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all border flex items-center gap-1 {{ $isActive ? 'bg-warning/10 text-warning border-warning/50/20 hover:bg-warning/20' : 'bg-success/10 text-success border-success/50/20 hover:bg-success/20' }}" title="{{ $isActive ? '切換為停用' : '切換為啟用' }}">
                                    <span class="material-symbols-outlined text-sm">{{ $isActive ? 'block' : 'check_circle' }}</span>
                                    <span>{{ $isActive ? '停用' : '啟用' }}</span>
                                </button>
                            </form>

                            <!-- Remove Member Form -->
                            @if((int)$member->id !== (int)auth()->id())
                                <form action="{{ route('members.remove', $member->id) }}" method="POST" onsubmit="return confirm('確定要將成員「{{ $member->name }}」自家庭中移除嗎？')">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 bg-danger/10 hover:bg-danger/20 text-danger border border-danger/20 rounded-xl text-xs font-bold transition-all flex items-center gap-1" title="{{ __('auto.0608') }}">
                                        <span class="material-symbols-outlined text-sm">person_remove</span>
                                        <span>{{ __('member.remove') }}</span>
                                    </button>
                                </form>
                            @else
                                <span class="text-[11px] text-on-surface-variant italic px-2">{{ __('auto.0609') }}</span>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="p-8 text-center text-on-surface-variant space-y-2">
                    <span class="material-symbols-outlined text-4xl text-on-surface-variant/50">group_off</span>
                    <p class="font-medium">{{ __('auto.0445') }}</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Pending Invitations List (if any) -->
    @if($isParent && isset($invitations) && $invitations->count() > 0)
        <div class="bg-surface-pure border border-border-base rounded-2xl shadow-sm p-6 space-y-4">
            <h3 class="font-bold text-base text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-warning text-lg">pending_actions</span>
                <span>等待中的邀請 ({{ $invitations->count() }} 個)</span>
            </h3>
            <div class="divide-y divide-border-base">
                @foreach($invitations as $inv)
                    <div class="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                        <div>
                            <span class="font-bold text-on-surface">{{ $inv->email }}</span>
                            <span class="text-on-surface-variant ml-2">建立於 {{ $inv->created_at->format('Y-m-d H:i') }}</span>
                        </div>
                        <div class="flex items-center gap-2" x-data="{ copiedThis: false }">
                            <input type="text" readonly value="{{ url('/join-family?token=' . $inv->token) }}" class="px-2.5 py-1 bg-background-warm border border-border-base rounded-lg text-xs font-mono w-48 truncate">
                            <button @click="navigator.clipboard.writeText('{{ url('/join-family?token=' . $inv->token) }}'); copiedThis = true; setTimeout(() => copiedThis = false, 2000)" class="px-2.5 py-1 bg-primary/10 hover:bg-primary/20 text-primary font-bold rounded-lg transition-colors">
                                <span x-text="copiedThis ? '已複製' : '複製連結'"></span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Flow A: Direct Child Creation Modal -->
    <div x-show="showDirectModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" x-transition.opacity>
        <div @click.away="showDirectModal = false" class="w-full max-w-md bg-surface-pure border border-border-base rounded-2xl shadow-2xl p-6 space-y-5" @keydown.escape.window="showDirectModal = false">
            <div class="flex items-center justify-between border-b border-border-base pb-3">
                <div class="flex items-center gap-2 text-primary font-bold text-lg">
                    <span class="material-symbols-outlined text-2xl">person_add</span>
                    <span>{{ __('auto.0548') }}</span>
                </div>
                <button @click="showDirectModal = false" class="text-on-surface-variant hover:text-on-surface">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form action="{{ route('members.child-direct') }}" method="POST" class="space-y-4">
                @csrf
                <div class="space-y-1">
                    <label class="block text-sm font-bold text-on-surface-variant">孩童姓名 <span class="text-danger">*</span></label>
                    <input type="text" name="name" required placeholder="{{ __('auto.0131') }}" class="w-full px-4 py-2.5 bg-background-warm border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                </div>

                <div class="space-y-1">
                    <label class="block text-sm font-bold text-on-surface-variant">登入帳號 <span class="text-danger">*</span></label>
                    <input type="text" name="account" required placeholder="{{ __('auto.0227') }}" class="w-full px-4 py-2.5 bg-background-warm border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                </div>

                <div class="space-y-1">
                    <label class="block text-sm font-bold text-on-surface-variant">設定密碼 <span class="text-danger">*</span></label>
                    <input type="password" name="password" required placeholder="至少 6 位數" class="w-full px-4 py-2.5 bg-background-warm border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                </div>

                <div class="pt-3 flex justify-end gap-3">
                    <button type="button" @click="showDirectModal = false" class="px-4 py-2 bg-background-warm hover:bg-border-base text-on-surface-variant font-bold rounded-xl text-sm transition-colors">{{ __('common.cancel') }}</button>
                    <button type="submit" class="px-5 py-2 bg-primary hover:bg-primary/90 text-white font-bold rounded-xl text-sm shadow-sm transition-all flex items-center gap-1">
                        <span>{{ __('auto.0326') }}</span>
                        <span class="material-symbols-outlined text-base">arrow_forward</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Flow B: Generate Invitation Link Modal -->
    <div x-show="showInviteModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" x-transition.opacity>
        <div @click.away="showInviteModal = false" class="w-full max-w-md bg-surface-pure border border-border-base rounded-2xl shadow-2xl p-6 space-y-5" @keydown.escape.window="showInviteModal = false">
            <div class="flex items-center justify-between border-b border-border-base pb-3">
                <div class="flex items-center gap-2 text-primary font-bold text-lg">
                    <span class="material-symbols-outlined text-2xl">mail</span>
                    <span>{{ __('auto.0492') }}</span>
                </div>
                <button @click="showInviteModal = false" class="text-on-surface-variant hover:text-on-surface">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form action="{{ route('members.invite-child') }}" method="POST" class="space-y-4">
                @csrf
                <div class="space-y-1">
                    <label class="block text-sm font-bold text-on-surface-variant">邀請 Email 信箱 <span class="text-danger">*</span></label>
                    <input type="email" name="email" required placeholder="例如：孩童或大人的 Email" class="w-full px-4 py-2.5 bg-background-warm border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                    <p class="text-[11px] text-on-surface-variant">{{ __('auto.0580') }}</p>
                </div>
                <div class="space-y-1">
                    <label class="block text-sm font-bold text-on-surface-variant">邀請角色</label>
                    <select name="role" class="w-full px-4 py-2.5 bg-background-warm border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary">
                        <option value="child" selected>👶 兒童 (僅檢視/有限的記帳)</option>
                        <option value="parent">👨‍👩‍👧 家長 (完整編輯權限,例如邀請系統管理員協作)</option>
                    </select>
                    <p class="text-[11px] text-on-surface-variant">若 Email 已註冊為系統管理員,將自動以「家長」身份邀請。</p>
                </div>

                <div class="pt-3 flex justify-end gap-3">
                    <button type="button" @click="showInviteModal = false" class="px-4 py-2 bg-background-warm hover:bg-border-base text-on-surface-variant font-bold rounded-xl text-sm transition-colors">{{ __('common.cancel') }}</button>
                    <button type="submit" class="px-5 py-2 bg-primary hover:bg-primary/90 text-white font-bold rounded-xl text-sm shadow-sm transition-all flex items-center gap-1">
                        <span>{{ __('auto.0493') }}</span>
                        <span class="material-symbols-outlined text-base">send</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
