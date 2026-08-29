@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-primary flex items-center gap-2">
                <span class="material-symbols-outlined text-3xl">manage_accounts</span>
                個人資料與帳號設定
            </h1>
            <p class="text-sm text-on-surface-variant">{{ __('auto.0486') }}</p>
        </div>
    </div>

    <!-- Main Profile Form -->
    <div class="surface-pure rounded-2xl border border-border-base shadow-sm p-6 md:p-8 max-w-3xl">
        <form action="{{ route('settings.profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- Avatar Section (獨立防覆蓋機制) -->
            <div class="flex flex-col sm:flex-row items-center gap-6 pb-6 border-b border-border-base">
                <div class="relative group">
                    @if(filled($user->avatar_url))
                        <img src="{{ asset($user->avatar_url) }}" alt="Avatar" class="w-24 h-24 rounded-full object-cover border-4 border-primary/20 shadow-md">
                    @else
                        <div class="w-24 h-24 rounded-full bg-primary/10 border-4 border-primary/20 flex items-center justify-center text-primary font-bold text-3xl shadow-md">
                            {{ mb_substr($user->name, 0, 1) }}
                        </div>
                    @endif
                </div>
                <div class="space-y-2 text-center sm:text-left flex-1">
                    <label class="block text-sm font-semibold text-text-primary">{{ __('auto.0146') }}</label>
                    <input type="file" name="avatar" accept="image/*" class="block w-full text-xs text-on-surface-variant file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 cursor-pointer">
                    <p class="text-xs text-on-surface-variant">{{ __('auto.0369') }}</p>
                    
                    @if(filled($user->avatar_url))
                        <div class="flex items-center gap-2 pt-1">
                            <input type="checkbox" name="remove_avatar" value="1" id="remove_avatar" class="rounded text-danger focus:ring-danger">
                            <label for="remove_avatar" class="text-xs text-danger font-medium cursor-pointer">{{ __('auto.0556') }}</label>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Basic Info Form Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Login Account (READONLY / DISABLED) -->
                <div>
                    <label class="block text-sm font-semibold text-text-primary mb-1">
                        系統登入帳號 (Account)
                        <span class="text-xs font-normal text-on-surface-variant ml-1">（固定不可變更）</span>
                    </label>
                    <input type="text" value="{{ $user->account ?: '尚未設定' }}" disabled readonly class="w-full px-4 py-2.5 bg-surface-container/60 border border-border-base rounded-xl text-sm font-mono text-on-surface-variant cursor-not-allowed select-none">
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-semibold text-text-primary mb-1">電子郵件 (Email) <span class="text-danger">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full px-4 py-2.5 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary">
                    @error('email')
                        <p class="text-xs text-danger mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Display Name -->
                <div>
                    <label class="block text-sm font-semibold text-text-primary mb-1">姓名 / 顯示名稱 <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full px-4 py-2.5 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary">
                    @error('name')
                        <p class="text-xs text-danger mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-sm font-semibold text-text-primary mb-1">{{ __('auto.0604') }}</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="0912-345-678" class="w-full px-4 py-2.5 border border-border-base rounded-xl text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary">

            <!-- Submit Button -->
            <div class="flex justify-end pt-4 border-t border-border-base">
                <button type="submit" class="px-6 py-2.5 bg-primary text-white font-semibold rounded-xl text-sm shadow-md hover:bg-primary-container transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">save</span>
                    儲存設定變更
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
