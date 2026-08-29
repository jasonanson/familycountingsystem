@props([
    'method' => 'post',
])

<form
    method="{{ $method }}"
    x-data="{ isProcessing: false }"
    x-on:submit="if (isProcessing) { $event.preventDefault(); return false; }"
    x-on:form-processing-started="isProcessing = true"
    x-on:form-processing-finished="isProcessing = false"
    {{ $attributes->class(['fi-form grid gap-y-6']) }}
    onsubmit="event.preventDefault(); event.stopPropagation(); return false;"
>
    {{ $slot }}
</form>

@once
@prepend('body')
<script>
document.addEventListener('submit', function(e) {
    var form = e.target;
    if (!form || !form.classList || !form.classList.contains('fi-form')) return;
    if (typeof window.Livewire !== 'undefined') return;
    e.preventDefault();
    e.stopPropagation();
    var url = (form.action && form.action !== '') ? form.action : window.location.href;
    fetch(url, {
        method: 'POST',
        body: new FormData(form),
        headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html'}
    }).then(function(r){ return r.text(); }).then(function(html){
        document.open(); document.write(html); document.close();
    }).catch(function(err){ console.error('Form submit fallback failed', err); });
    return false;
}, true);
</script>
@endprepend
@endonce