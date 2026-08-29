<x-app-layout>
    <x-slot name="title">{{ __('auto.0148') }}</x-slot>

    <div x-data="{ showModal: false }" class="space-y-6">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-2xl">task_alt</span>
                    <span>{{ __('auto.0149') }}</span>
                </h1>
                <p class="text-sm text-on-surface-variant mt-1">{{ __('auto.0292') }}</p>
            </div>
@if(! $isChildUser ?? false)
                @if(auth()->user()->canEditCurrentFamily())
                <button @click="showModal = true" class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-primary hover:bg-primary/90 text-white font-bold text-sm shadow-md transition-all hover:scale-[1.02]">
                    <span class="material-symbols-outlined text-lg">add</span>
                    <span>{{ __('auto.0511') }}</span>
                </button>
                @else
                @include('partials.read-only-notice')
                @endif
            @endif

        <!-- Tasks Grid List -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($tasks as $task)
                <div class="bg-surface-pure border border-border-base rounded-2xl p-5 shadow-sm flex flex-col justify-between space-y-4 transition-all hover:-translate-y-0.5">
                    
                    <div class="space-y-2">
                        <div class="flex items-start justify-between">
                            <h3 class="font-bold text-lg text-on-surface flex items-center gap-2">
                                <span>{{ $task->name }}</span>
                            </h3>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold 
                                {{ $task->status === 'approved' ? 'bg-success/15 text-success' : ($task->status === 'reported' ? 'bg-warning/15 text-warning' : ($task->status === 'rejected' ? 'bg-danger/15 text-danger' : 'bg-background-warm text-on-surface-variant border border-border-base')) }}">
                                {{ match($task->status) { 'pending' => '進行中', 'reported' => '待審核', 'approved' => '已撥款', 'rejected' => '未通過', default => $task->status } }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between text-sm pt-1">
                            <span class="text-on-surface-variant">任務獎金：<strong class="text-primary font-bold text-base">NT$ {{ number_format($task->reward_amount) }}</strong></span>
                            <span class="text-on-surface-variant">指派：{{ $task->assignee?->name ?? '全體可領' }}</span>
                        </div>
                    </div>

                    <!-- Task Actions (isChildUser / isParentOrAdminUser 從 controller 傳入) -->
                    <div class="pt-3 border-t border-border-base flex items-center justify-between text-sm">
                        <span class="text-xs text-on-surface-variant">截止日期：{{ $task->due_date ?? '無限制' }}</span>

                        <div class="flex items-center gap-2">
                            @if($task->status === 'pending')
                                @if($isChildUser)
                                    <form action="{{ route('tasks.report', $task) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 bg-primary/10 hover:bg-primary/20 text-primary border border-primary/20 font-bold rounded-lg text-sm flex items-center gap-1 cursor-pointer transition-all">
                                            <span>✋ 回報已完成</span>
                                        </button>
                                    </form>
                                @else
                                    <span class="px-2.5 py-1 text-xs text-on-surface-variant bg-surface-container-high rounded-lg font-medium flex items-center gap-1 select-none" title="{{ __('auto.0289') }}">
                                        <span class="material-symbols-outlined text-[14px]">hourglass_empty</span>
                                        <span>{{ __('auto.0330') }}</span>
                                    </span>
                                @endif
                            @elseif($task->status === 'reported')
                                @if($isParentOrAdminUser && auth()->user()->canEditCurrentFamily())
                                    <form action="{{ route('tasks.approve', $task) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 bg-success text-white font-bold rounded-lg text-sm shadow-sm flex items-center gap-1 hover:opacity-95 cursor-pointer">
                                            <span>✓ 審核撥款</span>
                                        </button>
                                    </form>
                                @else
                                    <span class="px-2.5 py-1 text-xs text-warning bg-warning/10 rounded-lg font-medium flex items-center gap-1 select-none">
                                        <span class="material-symbols-outlined text-[14px]">pending</span>
                                        <span>{{ __('auto.0564') }}</span>
                                    </span>
                                @endif
                            @endif
                        </div>
                    </div>

                </div>
            @empty
                <div class="col-span-full bg-surface-pure border border-border-base rounded-2xl py-12 text-center text-sm text-on-surface-variant shadow-sm">
                    目前尚未建立家事任務，點擊上方「發布家事任務」開始發布！
                </div>
            @endforelse
        </div>

        <!-- Create Task Modal -->
@if(! $isChildUser ?? false)
                    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen p-4 text-center">
                <div x-show="showModal" x-transition.opacity @click="showModal = false" class="fixed inset-0 bg-on-surface/40 backdrop-blur-sm"></div>

                <div x-show="showModal" x-transition 
                     class="inline-block w-full max-w-lg bg-surface-pure border border-border-base rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all z-10 p-6 space-y-4">
                    
                    <div class="flex items-center justify-between pb-3 border-b border-border-base">
                        <h3 class="font-bold text-lg text-on-surface flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">add_task</span>
                            <span>{{ __('auto.0510') }}</span>
                        </h3>
                        <button @click="showModal = false" class="text-on-surface-variant hover:text-on-surface">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>

                    <form action="{{ route('tasks.store') }}" method="POST" class="space-y-4">
                        @csrf

                        <div class="space-y-1">
                            <label class="block text-sm font-bold text-on-surface-variant">家事任務名稱 <span class="text-danger">*</span></label>
                            <input type="text" name="name" required placeholder="{{ __('auto.0132') }}"
                                   class="w-full bg-background-warm border border-border-base text-on-surface text-base rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-primary">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="block text-sm font-bold text-on-surface-variant">獎金金額 (TWD) <span class="text-danger">*</span></label>
                                <input type="number" name="reward_amount" required placeholder="50"
                                       class="w-full bg-background-warm border border-border-base text-on-surface text-base rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-primary">
                            </div>

                            <div class="space-y-1">
                                <label class="block text-sm font-bold text-on-surface-variant">{{ __('auto.0348') }}</label>
                                <select name="assignee_user_id" class="w-full bg-background-warm border border-border-base text-on-surface text-base rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-primary">
                                    <option value="">-- 全體均可領取 --</option>
                                    @foreach($familyMembers as $member)
                                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-sm font-bold text-on-surface-variant">{{ __('auto.0204') }}</label>
                            <input type="date" name="due_date" value="{{ now()->addDays(3)->format('Y-m-d') }}"
                                   class="w-full bg-background-warm border border-border-base text-on-surface text-base rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-primary">
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-border-base">
                            <button type="button" @click="showModal = false" class="px-4 py-2 text-sm font-bold text-on-surface-variant hover:bg-background-warm rounded-xl">{{ __('common.cancel') }}</button>
                            <button type="submit" class="px-5 py-2.5 text-sm font-bold bg-primary hover:bg-primary/90 text-white rounded-xl shadow-md">
                                發布任務
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
            </div>
            @endif
        </div>
    </x-app-layout>