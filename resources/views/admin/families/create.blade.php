@extends('layouts.app')

@section('title', '新增家庭')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-text-primary flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-2xl">add_home</span>
            <span>新增家庭</span>
        </h1>
        <p class="text-sm text-on-surface-variant">建立新的家庭 (管理員可建立多個家庭)</p>
    </div>

    @if($errors->any())
        <div class="bg-danger/10 text-danger border border-danger/20 p-3 rounded-xl text-sm">
            ⚠️ {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.families.store') }}" class="bg-surface-pure rounded-2xl border border-border-base p-6 shadow-sm space-y-4 max-w-xl">
        @csrf
        <div class="space-y-1">
            <label class="block text-sm font-bold text-on-surface-variant">家庭名稱 <span class="text-danger">*</span></label>
            <input class="w-full px-4 py-2.5 bg-background-warm border border-border-base rounded-xl text-base focus:outline-none focus:border-primary"
                   name="name" value="{{ old('name') }}" required maxlength="255" placeholder="例如：王家、示範家庭A">
        </div>

        <div class="text-sm text-on-surface-variant bg-primary/5 border border-primary/20 p-3 rounded-xl">
            建立家庭後，您 (管理員) 將自動被加入此家庭 (role=admin)。<br>
            預設會建立一個 NT$ 0 金額池，並隨機產生 6 碼邀請碼。
        </div>

        <div class="flex gap-3">
            <button type="submit" class="flex-1 py-3 bg-primary hover:bg-primary/90 text-white font-bold rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-lg">save</span>
                建立家庭
            </button>
            <a href="{{ route('admin.families.index') }}" class="flex-1 py-3 bg-background-warm border border-border-base text-on-surface-variant font-bold rounded-xl text-center transition-all hover:bg-border-base">
                取消
            </a>
        </div>
    </form>
</div>
@endsection
