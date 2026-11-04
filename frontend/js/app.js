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
document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.querySelector('#confirm-dialog');
    const accountDialog = document.querySelector('#account-dialog');
    const cancel = dialog?.querySelector('[data-confirm-cancel]');
    const accept = dialog?.querySelector('[data-confirm-accept]');
    let pendingSubmission = null;
    let approvedForm = null;
    let returnToAccount = false;

    document.addEventListener('submit', event => {
        const form = event.target;
        const submitter = event.submitter;
        const message = submitter?.dataset.confirm || form.dataset.confirm;

        if (message && form !== approvedForm) {
            event.preventDefault();
            if (!dialog || typeof dialog.showModal !== 'function') {
                if (window.confirm(message)) {
                    approvedForm = form;
                    try { form.requestSubmit(submitter || undefined); }
                    finally { approvedForm = null; }
                }
                return;
            }

            const action = submitter?.textContent?.trim() || submitter?.value || 'Confirmar';
            const isDanger = submitter?.classList.contains('button-danger')
                || /suspender|rechazar|revocar|eliminar|finalizar/i.test(action);
            dialog.querySelector('[data-confirm-title]').textContent = action;
            dialog.querySelector('[data-confirm-message]').textContent = message;
            accept.textContent = action;
            accept.classList.toggle('button-danger', isDanger);
            accept.classList.toggle('button-primary', !isDanger);
            pendingSubmission = { form, submitter };
            returnToAccount = Boolean(accountDialog?.open);
            if (returnToAccount) accountDialog.close();
            dialog.showModal();
            cancel.focus();
            return;
        }

        if (form.method.toLowerCase() !== 'get') {
            document.body.classList.add('is-loading');
            submitter?.setAttribute('aria-disabled', 'true');
        }
    });

    cancel?.addEventListener('click', () => dialog.close());
    dialog?.addEventListener('click', event => {
        if (event.target === dialog) dialog.close();
    });
    dialog?.addEventListener('close', () => {
        if (pendingSubmission && returnToAccount) accountDialog.showModal();
        pendingSubmission?.submitter?.focus();
        pendingSubmission = null;
        returnToAccount = false;
    });
    accept?.addEventListener('click', () => {
        const submission = pendingSubmission;
        if (!submission) return;
        pendingSubmission = null;
        dialog.close();
        approvedForm = submission.form;
        try { submission.form.requestSubmit(submission.submitter || undefined); }
        finally { approvedForm = null; }
    });
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
    } else if (dialog.dataset.reopenAccount === 'true') {
        dialog.showModal();
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

document.addEventListener('DOMContentLoaded', () => {
    const pickers = document.querySelectorAll('[data-language-picker]');
    document.addEventListener('click', event => {
        pickers.forEach(picker => {
            if (picker.open && !picker.contains(event.target)) picker.open = false;
        });
    });
    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;
        const picker = [...pickers].find(item => item.open);
        if (!picker) return;
        event.preventDefault();
        picker.open = false;
        picker.querySelector('summary')?.focus();
    });
});
