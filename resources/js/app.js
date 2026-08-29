import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

if (!window._alpineStarted) {
    window._alpineStarted = true;
    Alpine.start();
}

// === Livewire wire:navigate Loading 頂部進度條控制 ===
document.addEventListener('livewire:navigating', () => {
    let bar = document.getElementById('homesync-progress-bar');
    if (!bar) {
        bar = document.createElement('div');
        bar.id = 'homesync-progress-bar';
        document.body.appendChild(bar);
    }
    bar.style.width = '30%';
    bar.style.opacity = '1';
    setTimeout(() => {
        if (bar.style.opacity === '1') {
            bar.style.width = '70%';
        }
    }, 150);
});

document.addEventListener('livewire:navigated', () => {
    const bar = document.getElementById('homesync-progress-bar');
    if (bar) {
        bar.style.width = '100%';
        setTimeout(() => {
            bar.style.opacity = '0';
            setTimeout(() => {
                bar.style.width = '0%';
            }, 300);
        }, 150);
    }
});
