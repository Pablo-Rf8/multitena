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
    const message = event.submitter?.dataset.confirm || event.target.dataset.confirm;
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
    const dialog = document.querySelector('#account-dialog');
    const trigger = document.querySelector('[data-account-open]');
    if (!dialog || !trigger || typeof dialog.showModal !== 'function') return;

    const menu = dialog.querySelector('[data-account-menu]');
    const passwordPanel = dialog.querySelector('[data-account-password-panel]');
    const passwordForm = dialog.querySelector('.account-password-form');
    const passwordButton = dialog.querySelector('[data-account-password-open]');
    const showMenu = () => {
        menu.hidden = false;
        passwordPanel.hidden = true;
    };
    const showPassword = () => {
        menu.hidden = true;
        passwordPanel.hidden = false;
        passwordPanel.querySelector('input[name="current_password"]')?.focus();
    };

    trigger.addEventListener('click', () => {
        document.body.classList.remove('sidebar-open');
        document.querySelector('[data-sidebar-open]')?.setAttribute('aria-expanded', 'false');
        showMenu();
        dialog.showModal();
    });
    passwordButton?.addEventListener('click', showPassword);
    dialog.querySelector('[data-account-password-back]')?.addEventListener('click', () => {
        showMenu();
        passwordButton?.focus();
    });
    dialog.querySelector('[data-account-settings]')?.addEventListener('click', event => {
        if (new URL(event.currentTarget.href).pathname === window.location.pathname) {
            const settings = document.getElementById('settings');
            if (settings instanceof HTMLDetailsElement) settings.open = true;
            dialog.close();
        }
    });
    dialog.querySelector('[data-account-close]')?.addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', event => {
        if (event.target === dialog) dialog.close();
    });
    dialog.addEventListener('close', () => {
        passwordForm?.reset();
        showMenu();
        const focusTarget = window.matchMedia('(max-width: 760px)').matches
            ? document.querySelector('[data-sidebar-open]') : trigger;
        focusTarget?.focus();
    });
    if (dialog.dataset.accountError === 'true') {
        dialog.showModal();
        showPassword();
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const revealSection = () => {
        const section = document.getElementById(window.location.hash.slice(1));
        if (section instanceof HTMLDetailsElement) section.open = true;
    };
    revealSection();
    window.addEventListener('hashchange', revealSection);
});
