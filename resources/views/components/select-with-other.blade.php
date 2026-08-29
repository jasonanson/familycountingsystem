@props([
    'label' => '選擇項目',
    'name' => 'category',
    'options' => [],
    'selected' => '',
    'customValue' => '',
    'required' => false,
    'customPlaceholder' => '請輸入其他自訂項目',
    'customName' => null,
    'tabCondition' => null,
])

@php
    $actualCustomName = $customName ?? ($name . '_custom');
@endphp

<div x-data="{ isCustom: '{{ $selected }}' === 'custom', selectedValue: '{{ $selected }}' }" class="space-y-2">
    <label class="block text-xs font-bold text-on-surface-variant">
        {{ $label }}
        @if($required)<span class="text-danger">*</span>@endif
    </label>

    <!-- Dropdown Select -->
    <select name="{{ $name }}" 
            x-model="selectedValue"
            @change="isCustom = (selectedValue === 'custom')"
            @if($tabCondition)
                :disabled="!({!! $tabCondition !!})"
                :required="{{ $required ? '({!! $tabCondition !!})' : 'false' }}"
            @else
                @if($required) required @endif
            @endif
            class="w-full bg-background-warm border border-border-base text-on-surface text-sm rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-primary transition-colors disabled:opacity-50">
        <option value="">-- 請選擇 {{ $label }} --</option>
        @foreach($options as $val => $text)
            <option value="{{ $val }}">{{ $text }}</option>
        @endforeach
        <option value="custom">✏️ 其他 (手動自訂輸入...)</option>
    </select>

    <!-- Inline Expansion Custom Input Field (Exception-First Pattern) -->
    <div x-show="isCustom" x-transition.duration.200ms class="pt-1">
        <div class="relative">
            <input type="text"
                   name="{{ $actualCustomName }}"
                   value="{{ $customValue }}"
                   @if($tabCondition)
                       :disabled="!({!! $tabCondition !!})"
                   @endif
                   placeholder="{{ $customPlaceholder }}"
                   class="w-full bg-surface-pure border-2 border-primary text-on-surface text-sm rounded-xl px-3.5 py-2.5 focus:outline-none shadow-sm font-semibold disabled:opacity-50">
            <span class="absolute right-3 top-2.5 text-xs text-primary font-bold">{{ __('auto.0614') }}</span>
        </div>
        <p class="text-[11px] text-on-surface-variant mt-1 flex items-center gap-1">
            <span>💡 提示：輸入新項目後系統將為您自動記錄此自訂標籤。</span>
        </p>
    </div>
</div>
