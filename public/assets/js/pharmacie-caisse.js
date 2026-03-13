document.addEventListener('DOMContentLoaded', function () {
    // Manage collection prototype
    const container = document.querySelector('#ventes-lignes');
    if (!container) return;

    // Find the collection widget (Symfony renders a prototype inside the form)
    const collectionHolder = container.querySelector('div');
    if (!collectionHolder) return;

    // Add a remove button to existing items
    function addRemoveButton(entry) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-sm btn-danger remove-ligne';
        btn.textContent = 'Supprimer';
        btn.addEventListener('click', () => entry.remove());
        entry.appendChild(btn);
    }

    collectionHolder.querySelectorAll('.form-group, .row, .sf-fieldset').forEach(entry => {
        addRemoveButton(entry);
    });

    // Add handler to add new prototype
    const addButton = document.createElement('button');
    addButton.type = 'button';
    addButton.className = 'btn btn-sm btn-secondary mb-2';
    addButton.textContent = 'Ajouter une ligne';

    container.insertBefore(addButton, collectionHolder);

    let index = collectionHolder.children.length;

    addButton.addEventListener('click', () => {
        const prototype = collectionHolder.dataset.prototype || collectionHolder.getAttribute('data-prototype');
        if (!prototype) return;
        let newForm = prototype.replace(/__name__/g, index);
        index++;

        const wrapper = document.createElement('div');
        wrapper.innerHTML = newForm;
        // Append remove button
        addRemoveButton(wrapper);

        collectionHolder.appendChild(wrapper);
        attachMedicamentListeners(wrapper);
    });

    // Attach listeners on medicament selects to autofill price
    function attachMedicamentListeners(root) {
        const selects = root.querySelectorAll('.medicament-select');
        selects.forEach(sel => {
            sel.addEventListener('change', (e) => {
                const opt = sel.options[sel.selectedIndex];
                const price = opt ? opt.getAttribute('data-price') : null;
                // find prixUnitaire input in the same entry
                const entry = sel.closest('div');
                if (!entry) return;
                const priceInput = entry.querySelector('input[id$="prixUnitaire"]');
                if (priceInput && price !== null) {
                    priceInput.value = price;
                }
            });
        });
    }

    // Attach to existing
    attachMedicamentListeners(collectionHolder);
});
