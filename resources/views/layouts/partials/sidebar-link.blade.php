@php
    // 必填：$route, $icon, $label
    // 選填：$pattern (額外 routeIs 比對，例如 'admin.*')
    $pattern = $pattern ?? null;
    $isActive = request()->routeIs($route) || ($pattern && request()->routeIs($pattern));
@endphp
<li>
    <a href="{{ route($route) }}"
       class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-base transition-all {{ $isActive ? 'text-primary font-bold bg-primary/10 border-l-4 border-primary' : 'text-on-surface-variant hover:bg-surface-container' }}">
        <span class="material-symbols-outlined text-xl shrink-0">{{ $icon }}</span>
        <span class="truncate">{{ $label }}</span>
    </a>
</li>
