document.addEventListener('DOMContentLoaded', () => {
    const access = document.querySelector('[data-code-access]');
    if (access) {
        const token = window.location.hash.slice(1);
        window.history.replaceState(null, '', window.location.pathname);
        const valid = /^[a-f0-9]{64}$/.test(token);
        access.querySelector('[data-link-token]').value = valid ? token : '';
        access.querySelector('[data-verify-button]').disabled = !valid;
        access.querySelector('[data-missing-link]').hidden = valid;
    }

    const generator = document.querySelector('[data-code-generator]');
    if (!generator) return;

    const value = generator.querySelector('[data-code-value]');
    const status = generator.querySelector('[data-code-status]');
    const countdown = generator.querySelector('[data-code-countdown]');
    const progress = generator.querySelector('[data-code-progress]');
    let seconds = Number(generator.dataset.seconds) || 30;
    let busy = false;

    const refresh = async () => {
        if (busy) return;
        busy = true;
        try {
            const response = await fetch('/codes/current', { cache: 'no-store', credentials: 'same-origin' });
            const data = await response.json();
            if (!response.ok) {
                status.textContent = data.status === 'suspended' ? 'Enlace suspendido' : data.status === 'revoked' ? 'Enlace revocado' : data.status === 'expired' ? 'Enlace vencido' : data.status === 'account_disabled' ? 'Cuenta sin acceso' : 'Verificación vencida';
                status.classList.add('is-unavailable');
                value.textContent = '••••••';
                countdown.textContent = 'Solicitá un enlace nuevo o verificá tu identidad otra vez.';
                progress.style.width = '0%';
                return;
            }
            status.textContent = 'Enlace activo';
            status.classList.remove('is-unavailable');
            seconds = data.seconds;
            value.textContent = data.code || '••••••';
            countdown.textContent = data.code ? `Cambia en ${seconds} segundos` : `Código utilizado. Esperá ${seconds} segundos.`;
            progress.style.width = `${seconds / 30 * 100}%`;
        } catch {
            countdown.textContent = 'Sin conexión. Reintentando…';
        } finally {
            busy = false;
        }
    };

    refresh();
    setInterval(refresh, 1000);
});
