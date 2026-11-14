document.addEventListener('DOMContentLoaded', () => {
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
