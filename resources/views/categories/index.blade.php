@extends('layouts.app')

@section('content')
<div x-data="categoryManager()" class="space-y-6">
    <!-- Header Title & Add Button -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-surface-pure p-6 rounded-2xl shadow-sm border border-border-base">
        <div>
            <h1 class="text-2xl font-black text-primary flex items-center gap-2">
                <span class="material-symbols-outlined text-3xl">category</span>
                分類管理
            </h1>
            <p class="text-sm text-on-surface-variant mt-1">
                管理當前家庭與全域系統之主/子分類，區分支出與收入類別，掌握關聯交易數據。
            </p>
        </div>
        @if(auth()->user()->canEditCurrentFamily())
        <div>
            <button @click="openAddModal()" type="button" class="w-full sm:w-auto px-4 py-2.5 bg-primary text-white rounded-xl font-bold flex items-center justify-center gap-2 shadow-md hover:bg-primary/90 transition-all cursor-pointer">
                <span class="material-symbols-outlined">add_circle</span>
                <span>{{ __('auto.0387') }}</span>
            </button>
        </div>
        @else
            @include('partials.read-only-notice')
        @endif
    </div>

    <!-- Category Type Tabs -->
    <div class="flex border-b border-border-base space-x-4">
        <button @click="activeTab = 'expense'" :class="{ 'border-primary text-primary font-black': activeTab === 'expense', 'border-transparent text-on-surface-variant hover:text-on-surface': activeTab !== 'expense' }" class="pb-3 px-4 text-base border-b-2 font-bold transition-colors flex items-center gap-2 cursor-pointer">
            <span class="material-symbols-outlined text-danger">trending_down</span>
            <span>支出分類 ({{ $expenseCategories->count() }})</span>
        </button>
        <button @click="activeTab = 'income'" :class="{ 'border-primary text-primary font-black': activeTab === 'income', 'border-transparent text-on-surface-variant hover:text-on-surface': activeTab !== 'income' }" class="pb-3 px-4 text-base border-b-2 font-bold transition-colors flex items-center gap-2 cursor-pointer">
            <span class="material-symbols-outlined text-success">trending_up</span>
            <span>收入分類 ({{ $incomeCategories->count() }})</span>
        </button>
    </div>

    <!-- Expense Categories View -->
    <div x-show="activeTab === 'expense'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @forelse($expenseCategories as $cat)
            @include('categories.partials.category_card', ['category' => $cat])
        @empty
            <div class="col-span-full p-12 bg-surface-pure rounded-2xl border border-dashed border-border-base text-center text-on-surface-variant">
                <span class="material-symbols-outlined text-5xl text-on-surface-variant/40 mb-2">category_off</span>
                <p class="text-base font-bold">{{ __('auto.0540') }}</p>
            </div>
        @endforelse
    </div>

    <!-- Income Categories View -->
    <div x-show="activeTab === 'income'" class="grid grid-cols-1 md:grid-cols-2 gap-6" x-cloak>
        @forelse($incomeCategories as $cat)
            @include('categories.partials.category_card', ['category' => $cat])
        @empty
            <div class="col-span-full p-12 bg-surface-pure rounded-2xl border border-dashed border-border-base text-center text-on-surface-variant">
                <span class="material-symbols-outlined text-5xl text-on-surface-variant/40 mb-2">category_off</span>
                <p class="text-base font-bold">{{ __('auto.0541') }}</p>
            </div>
        @endforelse
    </div>

    <!-- Add / Edit Category Modal -->
    <div x-show="isModalOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
         x-cloak>
        <div @click.outside="isModalOpen = false" class="bg-surface-pure w-full max-w-lg rounded-2xl shadow-xl border border-border-base overflow-hidden">
            <div class="px-6 py-4 bg-surface-container-low border-b border-border-base flex justify-between items-center">
                <h3 class="text-lg font-bold text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined" x-text="isEditMode ? 'edit_note' : 'add_box'"></span>
                    <span x-text="isEditMode ? '編輯分類資訊' : '新增自訂分類'"></span>
                </h3>
                <button @click="isModalOpen = false" type="button" class="text-on-surface-variant hover:text-on-surface">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form :action="formUrl" method="POST" class="p-6 space-y-4">
                @csrf
                <template x-if="isEditMode">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <!-- Category Name -->
                <div>
                    <label class="block text-sm font-bold text-on-surface mb-1">分類名稱 <span class="text-danger">*</span></label>
                    <input type="text" name="name" x-model="formData.name" required placeholder="{{ __('auto.0123') }}" class="w-full px-3.5 py-2.5 rounded-xl border border-border-base bg-surface-bright text-on-surface focus:outline-none focus:border-primary">
                </div>

                <!-- Type & Scope -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-on-surface mb-1">收支類型 <span class="text-danger">*</span></label>
                        <select name="type" x-model="formData.type" class="w-full px-3.5 py-2.5 rounded-xl border border-border-base bg-surface-bright text-on-surface focus:outline-none focus:border-primary">
                            <option value="expense">{{ __('auto.0368') }}</option>
                            <option value="income">{{ __('auto.0371') }}</option>
                            <option value="both">{{ __('auto.0716') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-on-surface mb-1">{{ __('auto.0688') }}</label>
                        <select name="scope" x-model="formData.scope" class="w-full px-3.5 py-2.5 rounded-xl border border-border-base bg-surface-bright text-on-surface focus:outline-none focus:border-primary">
                            <option value="family">{{ __('auto.0167') }}</option>
                            <option value="personal">{{ __('auto.0145') }}</option>
                        </select>
                    </div>
                </div>

                <!-- Parent Category Selection -->
                <div>
                    <label class="block text-sm font-bold text-on-surface mb-1">{{ __('auto.0482') }}</label>
                    <select name="parent_id" x-model="formData.parent_id" class="w-full px-3.5 py-2.5 rounded-xl border border-border-base bg-surface-bright text-on-surface focus:outline-none focus:border-primary">
                        <option value="">-- 無 (作為獨立主分類) --</option>
                        @foreach($parentCategories as $parent)
                            <option value="{{ $parent->id }}" :disabled="isEditMode && formData.id == {{ $parent->id }}">
                                📂 {{ $parent->name }} ({{ $parent->type === 'expense' ? '支出' : ($parent->type === 'income' ? '收入' : '雙向') }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Icon & Color -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-on-surface mb-1">{{ __('auto.0249') }}</label>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-2xl text-primary" x-text="formData.icon || 'category'"></span>
                            <input type="text" name="icon" x-model="formData.icon" placeholder="restaurant, shopping_cart" class="w-full px-3.5 py-2 rounded-xl border border-border-base bg-surface-bright text-on-surface text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-on-surface mb-1">{{ __('auto.0096') }}</label>
                        <div class="flex items-center gap-2">
                            <input type="color" name="color" x-model="formData.color" class="w-10 h-10 rounded-lg border border-border-base cursor-pointer">
                            <input type="text" x-model="formData.color" class="w-full px-3 py-2 rounded-xl border border-border-base bg-surface-bright text-on-surface text-xs font-mono">
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-border-base flex justify-end gap-3">
                    <button @click="isModalOpen = false" type="button" class="px-4 py-2.5 rounded-xl border border-border-base text-on-surface-variant font-bold hover:bg-surface-container transition-colors">{{ __('common.cancel') }}</button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-primary text-white font-bold hover:bg-primary/90 transition-colors">{{ __('action.save_changes') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
window.categoryManager = function categoryManager() {
    return {
        activeTab: 'expense',
        isModalOpen: false,
        isEditMode: false,
        formUrl: '{{ route("categories.store") }}',
        formData: {
            id: null,
            name: '',
            type: 'expense',
            parent_id: '',
            icon: 'category',
            color: '#006b5f',
            scope: 'family'
        },
        openAddModal(defaultType = 'expense') {
            this.isEditMode = false;
            this.formUrl = '{{ route("categories.store") }}';
            this.formData = {
                id: null,
                name: '',
                type: defaultType,
                parent_id: '',
                icon: 'category',
                color: '#006b5f',
                scope: 'family'
            };
            this.isModalOpen = true;
        },
        openEditModal(category) {
            this.isEditMode = true;
            this.formUrl = `/categories/${category.id}`;
            this.formData = {
                id: category.id,
                name: category.name,
                type: category.type,
                parent_id: category.parent_id || '',
                icon: category.icon || 'category',
                color: category.color || '#006b5f',
                scope: category.scope || 'family'
            };
            this.isModalOpen = true;
        }
    }
}
</script>
@endsection
