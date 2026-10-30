document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-company-form]');
    if (!form) return;

    const name = form.querySelector('[data-company-name]');
    const email = form.querySelector('[data-admin-email]');
    let edited = email.value.trim() !== '';

    email.addEventListener('input', () => { edited = email.value.trim() !== ''; });
    name.addEventListener('input', () => {
        if (edited) return;
        const slug = name.value.normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        email.value = slug ? `admin@${slug}.com` : '';
    });
});

document.addEventListener('submit', event => {
    const message = event.target.dataset.confirm;
    if (message && !window.confirm(message)) event.preventDefault();
    if (!event.defaultPrevented && event.target.method.toLowerCase() !== 'get') {
        document.body.classList.add('is-loading');
        const button = event.submitter;
        if (button) button.setAttribute('aria-disabled', 'true');
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const open = document.querySelector('[data-sidebar-open]');
    const close = () => {
        document.body.classList.remove('sidebar-open');
        open?.setAttribute('aria-expanded', 'false');
        open?.focus();
    };
    open?.addEventListener('click', () => {
        document.body.classList.add('sidebar-open');
        open.setAttribute('aria-expanded', 'true');
        document.querySelector('[data-sidebar-close]')?.focus();
    });
    document.querySelectorAll('[data-sidebar-close]').forEach(button => button.addEventListener('click', close));
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && document.body.classList.contains('sidebar-open')) close();
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const button = document.querySelector('[data-copy-link]');
    button?.addEventListener('click', async () => {
        const input = document.querySelector('[data-issued-link]');
        const message = document.querySelector('[data-copy-message]');
        try {
            await navigator.clipboard.writeText(input.value);
            message.textContent = 'Enlace copiado. Entregalo por un canal privado.';
        } catch {
            input.select();
            message.textContent = 'Seleccioná y copiá el enlace.';
        }
    });
});
