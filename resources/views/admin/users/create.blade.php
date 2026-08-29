@extends('layouts.app')

@section('title', '新增使用者')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-text-primary flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-2xl">person_add</span>
            <span>新增使用者</span>
        </h1>
        <p class="text-sm text-on-surface-variant">管理員手動建立使用者帳號</p>
    </div>

    @if($errors->any())
        <div class="bg-danger/10 text-danger border border-danger/20 p-3 rounded-xl text-sm">
            ⚠️ {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.users.store') }}" class="bg-surface-pure rounded-2xl border border-border-base p-6 shadow-sm space-y-4 max-w-2xl">
        @csrf
        <div class="space-y-1">
            <label class="block text-sm font-bold text-on-surface-variant">姓名 <span class="text-danger">*</span></label>
            <input class="w-full px-4 py-2.5 bg-background-warm border border-border-base rounded-xl text-base focus:outline-none focus:border-primary"
                   name="name" value="{{ old('name') }}" required maxlength="255">
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1">
                <label class="block text-sm font-bold text-on-surface-variant">帳號 <span class="text-danger">*</span></label>
                <input class="w-full px-4 py-2.5 bg-background-warm border border-border-base rounded-xl text-base focus:outline-none focus:border-primary"
                       name="account" value="{{ old('account') }}" required maxlength="255" pattern="[A-Za-z0-9_]+">
            </div>
            <div class="space-y-1">
                <label class="block text-sm font-bold text-on-surface-variant">Email <span class="text-danger">*</span></label>
                <input type="email" class="w-full px-4 py-2.5 bg-background-warm border border-border-base rounded-xl text-base focus:outline-none focus:border-primary"
                       name="email" value="{{ old('email') }}" required maxlength="255">
            </div>
        </div>

        <div class="space-y-1">
            <label class="block text-sm font-bold text-on-surface-variant">密碼 <span class="text-danger">*</span></label>
            <input type="password" class="w-full px-4 py-2.5 bg-background-warm border border-border-base rounded-xl text-base focus:outline-none focus:border-primary"
                   name="password" required minlength="6">
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1">
                <label class="block text-sm font-bold text-on-surface-variant">註冊身份 (registration_role)</label>
                <select name="family_role" class="w-full px-4 py-2.5 bg-background-warm border border-border-base rounded-xl text-base focus:outline-none focus:border-primary">
                    <option value="parent" {{ old('family_role', 'parent') === 'parent' ? 'selected' : '' }}>家長</option>
                    <option value="child" {{ old('family_role') === 'child' ? 'selected' : '' }}>小孩</option>
                </select>
            </div>
            <div class="space-y-1">
                <label class="block text-sm font-bold text-on-surface-variant">加入家庭</label>
                <select name="family_id" class="w-full px-4 py-2.5 bg-background-warm border border-border-base rounded-xl text-base focus:outline-none focus:border-primary">
                    <option value="">(不加入)</option>
                    @foreach($families as $f)
                        <option value="{{ $f->id }}" {{ (string)old('family_id') === (string)$f->id ? 'selected' : '' }}>{{ $f->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <label class="flex items-center gap-2">
            <input type="checkbox" name="is_system_admin" value="1" {{ old('is_system_admin') ? 'checked' : '' }}>
            <span class="text-sm">設為系統管理員</span>
        </label>

        <div class="flex gap-3">
            <button type="submit" class="flex-1 py-3 bg-primary hover:bg-primary/90 text-white font-bold rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-lg">save</span>
                建立使用者
            </button>
            <a href="{{ route('admin.users.index') }}" class="flex-1 py-3 bg-background-warm border border-border-base text-on-surface-variant font-bold rounded-xl text-center transition-all hover:bg-border-base">
                取消
            </a>
        </div>
    </form>
</div>
@endsection
