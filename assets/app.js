import './stimulus_bootstrap.js';
import './styles/app.css';

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-menu-toggle]');
    if (toggle) {
        const open = document.body.classList.toggle('menu-open');
        toggle.setAttribute('aria-expanded', String(open));
    }
    if (event.target.closest('[data-print]')) window.print();
    if (event.target.closest('.nav-link')) document.body.classList.remove('menu-open');
});
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        document.body.classList.remove('menu-open');
        document.querySelector('[data-menu-toggle]')?.setAttribute('aria-expanded', 'false');
    }
});
document.addEventListener('submit', (event) => {
    const message = event.target.dataset.confirm;
    if (message && !window.confirm(message)) event.preventDefault();
});
document.addEventListener('change', (event) => {
    if (event.target.id !== 'jugador_fotografiaArchivo') return;
    const preview = document.querySelector('[data-photo-preview]');
    if (!preview) return;
    if (preview.dataset.url) URL.revokeObjectURL(preview.dataset.url);
    const file = event.target.files?.[0];
    preview.hidden = !file || !['image/jpeg', 'image/png', 'image/webp'].includes(file.type);
    if (!preview.hidden) {
        const url = URL.createObjectURL(file);
        preview.dataset.url = url;
        preview.src = url;
    }
});
