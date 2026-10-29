<script>
    window.initializeSmoothDropdowns = function(root) {
        root = root || document;
        let selects = [];

        if (root.matches && root.matches('select:not([data-native-dropdown])')) {
            selects.push(root);
        }
        if (root.querySelectorAll) {
            selects = selects.concat(Array.from(root.querySelectorAll('select:not([data-native-dropdown])')));
        }

        selects.forEach(function(select) {
            if (select.tomselect) {
                return;
            }

            select.classList.add('smooth-dropdown');

            let placeholder = select.dataset.placeholder;
            if (!placeholder && select.options.length && select.options[0].value === '') {
                placeholder = select.options[0].text.trim();
            }

            let isCustomerSelect = select.classList.contains('customer-select');
            let settings = {
                allowEmptyOption: true,
                create: false,
                closeAfterSelect: true,
                dropdownParent: 'body',
                maxOptions: null,
                placeholder: placeholder || 'Pilih opsi...',
                searchField: isCustomerSelect
                    ? ['text', 'taxId', 'email']
                    : ['text'],
                render: {
                    no_results: function() {
                        return '<div class="no-results">Tidak ada hasil yang cocok</div>';
                    }
                }
            };

            if (select.classList.contains('category-filter-dropdown')) {
                settings.dropdownClass = 'category-filter-dropdown-menu';
                settings.onInitialize = function() {
                    let instance = this;
                    Array.from(select.options).forEach(function(option) {
                        let item = instance.options[option.value];
                        if (item) {
                            item.type = option.dataset.type || '';
                        }
                    });
                };
            }

            if (isCustomerSelect) {
                settings.render.option = function(data, escape) {
                    let option = Array.from(select.options).find(function(item) {
                        return item.value === data.value;
                    });
                    let details = [];
                    if (option && option.dataset.taxId) {
                        details.push(`${option.dataset.taxType || 'NPWP'}: ${option.dataset.taxId}`);
                    }
                    if (option && option.dataset.email) {
                        details.push(option.dataset.email);
                    }

                    return `<div class="customer-option">
                        <span class="customer-option-name">${escape(data.text)}</span>
                        ${details.length ? `<span class="customer-option-meta">${escape(details.join(' · '))}</span>` : ''}
                    </div>`;
                };
                settings.onInitialize = function() {
                    let instance = this;
                    Array.from(select.options).forEach(function(option) {
                        let item = instance.options[option.value];
                        if (item) {
                            item.taxId = option.dataset.taxId || '';
                            item.email = option.dataset.email || '';
                        }
                    });
                };
            }

            new TomSelect(select, settings);
        });
    };

    document.addEventListener('DOMContentLoaded', function() {
        window.initializeSmoothDropdowns(document);

        let dropdownObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            window.initializeSmoothDropdowns(node);
                        }
                    });
                    return;
                }

                let select = mutation.target;
                let instance = select.tomselect;
                if (!instance) {
                    return;
                }

                if (select.disabled && !instance.isDisabled) {
                    instance.disable();
                } else if (!select.disabled && instance.isDisabled) {
                    instance.enable();
                }
            });
        });

        dropdownObserver.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['disabled'],
            childList: true,
            subtree: true
        });
    });
</script>
