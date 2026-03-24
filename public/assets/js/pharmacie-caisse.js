document.addEventListener('DOMContentLoaded', function () {
    const container = document.querySelector('#ventes-lignes');
    if (!container) return;

    const collectionHolder = container.querySelector('[data-prototype]') || container.querySelector('div');
    if (!collectionHolder) return;

    function debounce(callback, delay) {
        let timerId = null;

        return function debounced(...args) {
            window.clearTimeout(timerId);
            timerId = window.setTimeout(() => callback.apply(this, args), delay);
        };
    }

    function addRemoveButton(entry) {
        if (entry.querySelector('.remove-ligne')) return;

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-sm btn-danger remove-ligne mt-3 align-self-start';
        btn.textContent = 'Supprimer';
        btn.addEventListener('click', () => entry.remove());
        entry.appendChild(btn);
    }

    function findLineContainer(element) {
        let current = element.parentElement;
        while (current && current !== collectionHolder && current !== container) {
            if (current.querySelector('.medicament-search-input') && current.querySelector('input[id$="prixUnitaire"], input[name$="[prixUnitaire]"]')) {
                return current;
            }
            current = current.parentElement;
        }

        return element.closest('.ligne-vente-item, .form-group, .sf-fieldset') || element.parentElement;
    }

    function updatePriceInput(entry, price) {
        if (!entry) return;

        const priceInput = entry.querySelector('input[id$="prixUnitaire"], input[name$="[prixUnitaire]"]');
        if (priceInput) {
            priceInput.value = price ?? '';
            priceInput.setAttribute('value', price ?? '');
        }
    }

    function createResultElement(item, onSelect) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'medicament-search-result';
        button.innerHTML =
            '<span class="medicament-search-result__title">' + item.nom + '</span>' +
            '<span class="medicament-search-result__meta">' +
                (item.codeBarre ? 'Code: ' + item.codeBarre + ' · ' : '') +
                'Stock: ' + item.quantite + ' · Prix: ' + item.prixUnitaire + ' FCFA' +
            '</span>';
        button.addEventListener('mousedown', function (event) {
            event.preventDefault();
            onSelect(item);
        });

        return button;
    }

    function initializeMedicamentSearch(root) {
        root.querySelectorAll('.medicament-search-input').forEach((input) => {
            if (input.dataset.ajaxInit === '1') return;
            input.dataset.ajaxInit = '1';

            const entry = findLineContainer(input);
            const hiddenInput = entry ? entry.querySelector('.medicament-id-input') : null;
            const searchUrl = input.dataset.searchUrl;
            if (!entry || !hiddenInput || !searchUrl) return;

            const host = input.parentElement;
            host.classList.add('medicament-search-host');

            const dropdown = document.createElement('div');
            dropdown.className = 'medicament-search-dropdown d-none';

            const meta = document.createElement('div');
            meta.className = 'medicament-search-meta';

            host.appendChild(dropdown);
            host.insertAdjacentElement('afterend', meta);

            let items = [];
            let activeIndex = -1;
            let activeRequest = null;

            function closeDropdown() {
                dropdown.classList.add('d-none');
                dropdown.innerHTML = '';
                activeIndex = -1;
            }

            function openDropdown() {
                if (dropdown.childElementCount > 0) {
                    dropdown.classList.remove('d-none');
                }
            }

            function setMeta(item) {
                if (!item) {
                    meta.innerHTML = '<span class="text-muted">Aucun medicament selectionne.</span>';
                    return;
                }

                meta.innerHTML =
                    '<span class="badge bg-success-subtle text-success">Stock: ' + item.quantite + '</span>' +
                    '<span class="badge bg-primary-subtle text-primary">Prix: ' + item.prixUnitaire + ' FCFA</span>' +
                    (item.codeBarre ? '<span class="badge bg-secondary-subtle text-secondary">Code: ' + item.codeBarre + '</span>' : '');
            }

            function setActiveResult(index) {
                const results = dropdown.querySelectorAll('.medicament-search-result');
                results.forEach((result, resultIndex) => {
                    result.classList.toggle('is-active', resultIndex === index);
                });
                activeIndex = index;
            }

            function selectItem(item) {
                hiddenInput.value = item.id;
                input.value = item.codeBarre ? item.nom + ' | ' + item.codeBarre : item.nom;
                input.dataset.selectedId = String(item.id);
                input.dataset.selectedLabel = input.value;
                input.dataset.selectedPrice = String(item.prixUnitaire);
                setMeta(item);
                updatePriceInput(entry, item.prixUnitaire);
                closeDropdown();
            }

            function renderResults(results) {
                dropdown.innerHTML = '';
                items = results;
                activeIndex = -1;

                if (!results.length) {
                    const empty = document.createElement('div');
                    empty.className = 'medicament-search-empty';
                    empty.textContent = 'Aucun medicament trouve.';
                    dropdown.appendChild(empty);
                    openDropdown();
                    return;
                }

                results.forEach((item, index) => {
                    const resultElement = createResultElement(item, selectItem);
                    dropdown.appendChild(resultElement);
                    if (index === 0) {
                        setActiveResult(0);
                    }
                });

                openDropdown();
            }

            function fetchResults(query) {
                if (activeRequest) {
                    activeRequest.abort();
                }

                activeRequest = new AbortController();

                fetch(searchUrl + '?q=' + encodeURIComponent(query), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: activeRequest.signal,
                })
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error('Impossible de charger les medicaments.');
                        }

                        return response.json();
                    })
                    .then((results) => {
                        renderResults(Array.isArray(results) ? results : []);
                    })
                    .catch((error) => {
                        if (error.name !== 'AbortError') {
                            console.error(error);
                        }
                    });
            }

            const debouncedFetch = debounce((query) => fetchResults(query), 180);

            input.addEventListener('focus', function () {
                fetchResults(input.value.trim());
            });

            input.addEventListener('input', function () {
                const currentValue = input.value.trim();
                const selectedLabel = input.dataset.selectedLabel || '';

                if (currentValue !== selectedLabel) {
                    hiddenInput.value = '';
                    input.dataset.selectedId = '';
                    input.dataset.selectedPrice = '';
                    setMeta(null);
                    updatePriceInput(entry, '');
                }

                debouncedFetch(currentValue);
            });

            input.addEventListener('keydown', function (event) {
                if (dropdown.classList.contains('d-none') && ['ArrowDown', 'ArrowUp', 'Enter'].includes(event.key)) {
                    fetchResults(input.value.trim());
                }

                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    if (!items.length) return;
                    setActiveResult(activeIndex < items.length - 1 ? activeIndex + 1 : 0);
                }

                if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    if (!items.length) return;
                    setActiveResult(activeIndex > 0 ? activeIndex - 1 : items.length - 1);
                }

                if (event.key === 'Enter') {
                    if (activeIndex >= 0 && items[activeIndex]) {
                        event.preventDefault();
                        selectItem(items[activeIndex]);
                    }
                }

                if (event.key === 'Escape') {
                    closeDropdown();
                }
            });

            input.addEventListener('blur', function () {
                window.setTimeout(() => {
                    closeDropdown();

                    if (!hiddenInput.value) {
                        input.value = '';
                        setMeta(null);
                    }
                }, 150);
            });

            if (input.dataset.initialId && input.dataset.initialLabel) {
                hiddenInput.value = input.dataset.initialId;
                input.dataset.selectedId = input.dataset.initialId;
                input.dataset.selectedLabel = input.dataset.initialLabel;
                input.dataset.selectedPrice = input.dataset.initialPrice || '';
                setMeta({
                    quantite: '?',
                    prixUnitaire: input.dataset.initialPrice || '0',
                    codeBarre: '',
                });
                updatePriceInput(entry, input.dataset.initialPrice || '');
            } else {
                setMeta(null);
            }
        });
    }

    const addButton = document.createElement('button');
    addButton.type = 'button';
    addButton.className = 'btn btn-sm btn-secondary mb-3';
    addButton.textContent = 'Ajouter une ligne';

    container.insertBefore(addButton, collectionHolder);

    let index = collectionHolder.children.length;

    addButton.addEventListener('click', () => {
        const prototype = collectionHolder.dataset.prototype || collectionHolder.getAttribute('data-prototype');
        if (!prototype) return;

        const newForm = prototype.replace(/__name__/g, index);
        index += 1;

        const wrapper = document.createElement('div');
        wrapper.className = 'ligne-vente-item';
        wrapper.innerHTML = newForm;

        addRemoveButton(wrapper);
        collectionHolder.appendChild(wrapper);
        initializeMedicamentSearch(wrapper);

        const firstSearchInput = wrapper.querySelector('.medicament-search-input');
        if (firstSearchInput) {
            firstSearchInput.focus();
        }
    });

    collectionHolder.querySelectorAll('.form-group, .row, .sf-fieldset, .ligne-vente-item').forEach((entry) => {
        addRemoveButton(entry);
    });

    initializeMedicamentSearch(collectionHolder);

    const initialSearchInput = collectionHolder.querySelector('.medicament-search-input');
    if (initialSearchInput) {
        initialSearchInput.focus();
    }

    document.addEventListener('click', function (event) {
        if (event.target.closest('.medicament-search-host')) {
            return;
        }

        document.querySelectorAll('.medicament-search-dropdown').forEach((dropdown) => {
            dropdown.classList.add('d-none');
        });
    });
});
