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
});
