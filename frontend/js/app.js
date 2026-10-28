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
});
