import './stimulus_bootstrap.js';
import './styles/app.css';

function addPasswordToggles() {
    document.querySelectorAll('input[type="password"]').forEach((input) => {
        if (input.closest('.password-field')) return;
        const wrapper = document.createElement('div');
        wrapper.className = 'password-field';
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'password-toggle';
        button.dataset.passwordToggle = '';
        button.textContent = 'Mostrar';
        button.setAttribute('aria-label', 'Mostrar contraseña');
        button.setAttribute('aria-pressed', 'false');
        if (input.id) button.setAttribute('aria-controls', input.id);
        input.before(wrapper);
        wrapper.append(input, button);
    });
}

document.addEventListener('DOMContentLoaded', addPasswordToggles);
document.addEventListener('turbo:load', addPasswordToggles);
if (document.readyState !== 'loading') addPasswordToggles();

document.addEventListener('turbo:before-cache', () => {
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.closest('.password-field').querySelector('input').type = 'password';
        button.textContent = 'Mostrar';
        button.setAttribute('aria-label', 'Mostrar contraseña');
        button.setAttribute('aria-pressed', 'false');
    });
});

document.addEventListener('click', (event) => {
    const passwordToggle = event.target.closest('[data-password-toggle]');
    if (passwordToggle) {
        const input = passwordToggle.closest('.password-field').querySelector('input');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        passwordToggle.textContent = show ? 'Ocultar' : 'Mostrar';
        passwordToggle.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
        passwordToggle.setAttribute('aria-pressed', String(show));
    }
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

// Conserva los campos en la página cuando el acceso falla, sin almacenar la contraseña.
document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!form.matches('[data-login-form]')) return;
    event.preventDefault();
    if (form.dataset.submitting) return;
    const button = form.querySelector('button[type="submit"]');
    const error = document.querySelector('[data-login-error]');
    form.dataset.submitting = 'true';
    button.disabled = true;
    button.textContent = 'Entrando…';
    error.hidden = true;
    try {
        const response = await fetch(form.action, {
            method: 'POST', body: new FormData(form), credentials: 'same-origin',
        });
        if (!response.ok) throw new Error('Respuesta no válida');
        const page = new DOMParser().parseFromString(await response.text(), 'text/html');
        const returnedForm = page.querySelector('[data-login-form]');
        if (!returnedForm) {
            window.location.assign(response.url);
            return;
        }
        const token = returnedForm.querySelector('input[name="_csrf_token"]');
        if (token) form.elements.namedItem('_csrf_token').value = token.value;
        error.textContent = page.querySelector('[data-login-error]')?.textContent.trim()
            || 'No se pudo iniciar sesión. Vuelve a intentarlo.';
        error.hidden = false;
    } catch {
        error.textContent = 'No se pudo conectar. Tus datos siguen aquí; inténtalo de nuevo.';
        error.hidden = false;
    } finally {
        delete form.dataset.submitting;
        button.disabled = false;
        button.textContent = 'Iniciar sesión';
    }
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
