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

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('template-fields');
    const add = document.getElementById('add-template-field');
    if (container && add) {
        add.addEventListener('click', () => {
            const index = Number(container.dataset.nextIndex || 0);
            container.dataset.nextIndex = String(index + 1);
            const row = document.createElement('div');
            row.className = 'credential-field-row';
            row.dataset.fieldRow = '';
            const nameLabel = document.createElement('label');
            nameLabel.textContent = `${container.dataset.labelName} `;
            const name = document.createElement('input');
            name.name = `fields[${index}][name]`;
            name.maxLength = 120;
            name.required = true;
            nameLabel.append(name);
            const typeLabel = document.createElement('label');
            typeLabel.textContent = `${container.dataset.labelType} `;
            const type = document.createElement('select');
            type.name = `fields[${index}][type]`;
            [['text', container.dataset.labelText], ['password', container.dataset.labelPassword], ['url', container.dataset.labelUrl]].forEach(([value, label]) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = label;
                type.append(option);
            });
            typeLabel.append(type);
            const requiredLabel = document.createElement('label');
            requiredLabel.className = 'credential-check';
            const required = document.createElement('input');
            required.type = 'checkbox';
            required.name = `fields[${index}][required]`;
            required.value = '1';
            requiredLabel.append(required, document.createTextNode(` ${container.dataset.labelRequired}`));
            const actions = document.createElement('div');
            actions.className = 'credential-row-actions';
            [['↑', 'fieldUp'], ['↓', 'fieldDown'], [container.dataset.labelRemove, 'fieldRemove']].forEach(([label, key]) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'button button-ghost button-sm';
                button.dataset[key] = '';
                button.textContent = label;
                if (key === 'fieldUp') button.setAttribute('aria-label', container.dataset.labelUp);
                if (key === 'fieldDown') button.setAttribute('aria-label', container.dataset.labelDown);
                actions.append(button);
            });
            row.append(nameLabel, typeLabel, requiredLabel, actions);
            container.append(row);
            name.focus();
        });
        container.addEventListener('click', event => {
            const row = event.target.closest('[data-field-row]');
            if (!row) return;
            if (event.target.closest('[data-field-up]') && row.previousElementSibling) container.insertBefore(row, row.previousElementSibling);
            if (event.target.closest('[data-field-down]') && row.nextElementSibling) container.insertBefore(row.nextElementSibling, row);
            if (event.target.closest('[data-field-remove]') && container.querySelectorAll('[data-field-row]').length > 1) row.remove();
        });
    }
    document.querySelectorAll('[data-reveal-url]').forEach(button => {
        let timeout;
        const area = button.closest('.credential-secret');
        const output = area?.querySelector('[data-secret-output]');
        const copy = area?.querySelector('[data-copy-secret]');
        let secret = null;
        const clear = () => {
            secret = null;
            if (output) output.textContent = '••••••••';
            if (copy) copy.disabled = true;
            button.textContent = button.dataset.labelShow;
        };
        button.addEventListener('click', async () => {
            if (secret !== null) { clearTimeout(timeout); clear(); return; }
            button.disabled = true;
            try {
                const response = await fetch(button.dataset.revealUrl, {
                    method: 'POST', credentials: 'same-origin', cache: 'no-store',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json' },
                });
                if (!response.ok) throw new Error(button.dataset.errorReveal);
                secret = (await response.json()).value;
                output.textContent = secret;
                if (copy) copy.disabled = false;
                button.textContent = button.dataset.labelHide;
                timeout = setTimeout(clear, 15000);
            } catch (error) { window.alert(error.message); }
            finally { button.disabled = false; }
        });
        copy?.addEventListener('click', async () => {
            if (secret === null) return;
            try { await navigator.clipboard.writeText(secret); copy.textContent = copy.dataset.labelCopied; setTimeout(() => { copy.textContent = copy.dataset.labelCopy; }, 2000); }
            catch { window.alert(button.dataset.errorCopy); }
        });
        window.addEventListener('pagehide', clear);
    });
});
