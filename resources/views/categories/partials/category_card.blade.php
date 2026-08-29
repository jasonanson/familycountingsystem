<div class="bg-surface-pure rounded-2xl border border-border-base shadow-sm p-5 space-y-4 hover:shadow-md transition-shadow">
    <!-- Card Header -->
    <div class="flex items-start justify-between">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-2xl shadow-inner" style="background-color: {{ $category->color ? $category->color.'20' : '#006b5f20' }}; color: {{ $category->color ?? '#006b5f' }}">
                <span class="material-symbols-outlined">{{ \App\Support\IconHelper::name($category->icon ?? null, 'category') }}</span>
            </div>
            <div>
                <h3 class="font-bold text-lg text-on-surface flex items-center gap-2">
                    <span>{{ $category->name }}</span>
                    @if($category->is_custom)
                        <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full bg-warning/10 text-warning border border-warning/50/20">{{ __('auto.0610') }}</span>
                    @else
                        <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full bg-primary/10 text-primary border border-primary/30/20">{{ __('category_page.system_default') }}</span>
                    @endif
                </h3>
                <div class="flex items-center gap-2 text-xs text-on-surface-variant mt-0.5">
                    <span class="font-semibold">{{ $category->type === 'expense' ? '支出' : ($category->type === 'income' ? '收入' : '雙向') }}</span>
                    <span>&bull;</span>
                    <span>{{ $category->scope === 'family' ? '家庭共享' : '個人專用' }}</span>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        @if(auth()->user()->canEditCurrentFamily())
        <div class="flex items-center gap-1">
            <button @click="openEditModal({{ json_encode($category) }})" type="button" class="p-1.5 text-on-surface-variant hover:text-primary hover:bg-surface-container rounded-lg transition-colors cursor-pointer" title="{{ __('action.edit_category') }}">
                <span class="material-symbols-outlined text-xl">edit</span>
            </button>

            @if($category->is_custom)
                <form action="{{ route('categories.destroy', $category->id) }}" method="POST" onsubmit="return confirm('確定要刪除自訂分類「{{ $category->name }}」嗎？');" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="p-1.5 text-on-surface-variant hover:text-danger hover:bg-danger/10 rounded-lg transition-colors cursor-pointer" title="{{ __('action.delete_category') }}">
                        <span class="material-symbols-outlined text-xl">delete</span>
                    </button>
                </form>
            @endif
        </div>
        @endif
    </div>

    <!-- Transaction Count Badge -->
    <div class="flex items-center justify-between text-xs px-3 py-2 bg-surface-container-low rounded-xl border border-border-base">
        <span class="text-on-surface-variant font-medium">{{ __('auto.0709') }}</span>
        <div class="flex items-center gap-2 font-bold">
            <span class="text-primary">主分類: {{ $category->transactions_count }} 筆</span>
            @if($category->children->count() > 0)
                <span class="text-on-surface-variant">&bull;</span>
                <span class="text-on-surface-variant">包含子分類共 {{ $category->total_transactions_count }} 筆</span>
            @endif
        </div>
    </div>

    <!-- Subcategories List -->
    <div>
        <div class="flex items-center justify-between text-xs font-bold text-on-surface-variant mb-2">
            <span>子分類選單 ({{ $category->children->count() }})</span>
            @if(auth()->user()->canEditCurrentFamily())
            <button @click="openAddModal('{{ $category->type }}')" type="button" class="text-primary hover:underline font-semibold flex items-center gap-0.5 cursor-pointer">
                <span class="material-symbols-outlined text-sm">add</span>
                <span>{{ __('auto.0384') }}</span>
            </button>
            @endif
        </div>

        @if($category->children->count() > 0)
            <div class="space-y-1.5">
                @foreach($category->children as $sub)
                    <div class="flex items-center justify-between p-2 rounded-xl bg-surface-bright border border-border-base/50 hover:border-primary/30 transition-colors">
                        <div class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-lg" style="color: {{ $sub->color ?? '#006b5f' }}">{{ \App\Support\IconHelper::name($sub->icon ?? null, 'subdirectory_arrow_right') }}</span>
                            <span class="text-sm font-semibold text-on-surface">{{ $sub->name }}</span>
                            @if($sub->is_custom)
                                <span class="text-[10px] px-1.5 py-0.2 rounded bg-warning/15 text-warning">{{ __('auto.0610') }}</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-on-surface-variant font-mono">{{ $sub->transactions_count }} 筆</span>
                            @if(auth()->user()->canEditCurrentFamily())
                            <button @click="openEditModal({{ json_encode($sub) }})" type="button" class="p-1 text-on-surface-variant hover:text-primary rounded cursor-pointer">
                                <span class="material-symbols-outlined text-base">edit</span>
                            </button>
                            @if($sub->is_custom)
                                <form action="{{ route('categories.destroy', $sub->id) }}" method="POST" onsubmit="return confirm('確定要刪除子分類「{{ $sub->name }}」嗎？');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 text-on-surface-variant hover:text-danger rounded cursor-pointer">
                                        <span class="material-symbols-outlined text-base">delete</span>
                                    </button>
                                </form>
                            @endif
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-xs text-on-surface-variant/70 italic px-2 py-1">{{ __('auto.0303') }}</p>
        @endif
    </div>
</div>
