(() => {
    const form = document.querySelector('[data-branding-form]');
    if (!form) return;

    form.querySelectorAll('input[type="color"]').forEach((input) => {
        const value = input.closest('label')?.querySelector('[data-color-value]');
        input.addEventListener('input', () => { if (value) value.textContent = input.value; });
    });

    const file = form.querySelector('input[name="logo"]');
    const preview = form.querySelector('[data-logo-preview]');
    const placeholder = form.querySelector('[data-logo-placeholder]');
    const label = form.querySelector('[data-file-label]');
    let previewUrl;
    file?.addEventListener('change', () => {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        const selected = file.files?.[0];
        if (!selected || !selected.type.startsWith('image/')) return;
        previewUrl = URL.createObjectURL(selected);
        preview.src = previewUrl;
        preview.hidden = false;
        if (placeholder) placeholder.hidden = true;
        if (label) label.textContent = selected.name;
    });
    window.addEventListener('pagehide', () => { if (previewUrl) URL.revokeObjectURL(previewUrl); }, { once: true });
})();
