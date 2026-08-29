@php
    $tone = $tone ?? 'parent';
    $toneClasses = [
        'parent' => [
            'header' => 'bg-primary/10 text-primary border-primary/20 hover:bg-primary/20',
            'icon' => 'text-primary',
            'dot' => 'bg-primary',
        ],
        'child' => [
            'header' => 'bg-warning/15 text-warning border-warning/20 hover:bg-warning/20 dark:bg-warning/15/40 dark:text-warning/70 dark:border-warning',
            'icon' => 'text-warning dark:text-warning/70',
            'dot' => 'bg-warning',
        ],
        'admin' => [
            'header' => 'bg-danger/15 text-danger border-danger/20 hover:bg-danger/20 dark:bg-danger/15/40 dark:text-danger/70 dark:border-danger',
            'icon' => 'text-danger dark:text-danger/70',
            'dot' => 'bg-danger',
        ],
    ];
    $t = $toneClasses[$tone] ?? $toneClasses['parent'];
    $default = $default ?? true;
    $id = $id ?? 'section';
    $icon = $icon ?? 'folder';
    $title = $title ?? '';
@endphp
<div x-data="sidebarSection(@js($id), @json($default))"
     x-init="init()"
     class="mb-2"
     data-sidebar-section="{{ $id }}">
    <button type="button"
            @click="toggle()"
            :aria-expanded="expanded ? 'true' : 'false'"
            class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-bold border {{ $t['header'] }} transition-all cursor-pointer select-none">
        <span class="flex items-center gap-2 min-w-0">
            <span class="material-symbols-outlined text-[18px] {{ $t['icon'] }} shrink-0">{{ $icon }}</span>
            <span class="truncate">{{ $title }}</span>
        </span>
        <span class="flex items-center gap-1.5 shrink-0">
            <span class="w-1.5 h-1.5 rounded-full {{ $t['dot'] }}"
                  x-bind:class="expanded ? 'opacity-100' : 'opacity-30'"></span>
            <span class="material-symbols-outlined text-[18px] transition-transform duration-200"
                  x-bind:class="expanded ? 'rotate-180' : ''">expand_more</span>
        </span>
    </button>

    <div x-show="expanded"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-1"
         class="mt-1.5">
        <ul class="space-y-1 pl-1">
            {{ $slot }}
        </ul>
    </div>
</div>
