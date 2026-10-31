<script>
    window.initializeSmoothDropdowns = function(root) {
        root = root || document;
        let selects = [];

        if (
            root.matches &&
            root.matches('select:not([data-native-dropdown]):not(:disabled)') &&
            !root.closest('.swal2-popup')
        ) {
            selects.push(root);
        }
        if (root.querySelectorAll) {
            selects = selects.concat(
                Array.from(root.querySelectorAll('select:not([data-native-dropdown]):not(:disabled)'))
                    .filter(function(select) {
                        return !select.closest('.swal2-popup');
                    })
            );
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
                searchField: ['text'],
                render: {
                    no_results: function() {
                        return '<div class="no-results">Tidak ada hasil yang cocok</div>';
                    }
                }
            };

            if (select.classList.contains('status-select')) {
                let statusIcons = {
                    DRAFT: 'fa-file-pen',
                    SENT: 'fa-paper-plane',
                    PAID: 'fa-circle-check'
                };
                let renderStatus = function(data, escape, className) {
                    let icon = statusIcons[data.value];
                    if (!icon) {
                        return `<div>${escape(data.text)}</div>`;
                    }

                    return `<div class="${className}">
                        <i class="fa-solid ${icon}" aria-hidden="true"></i>
                        <span>${escape(data.text)}</span>
                    </div>`;
                };

                settings.searchField = [];
                settings.render.option = function(data, escape) {
                    return renderStatus(data, escape, 'status-option');
                };
                settings.render.item = function(data, escape) {
                    return renderStatus(data, escape, 'status-item');
                };
                settings.onInitialize = function() {
                    this.wrapper.classList.remove('form-select');
                    this.control_input.setAttribute('readonly', 'readonly');
                    this.control_input.setAttribute('aria-readonly', 'true');
                };
            }

            if (select.classList.contains('category-filter-dropdown')) {
                settings.score = function(search) {
                    let textScore = this.getScoreFunction(search);

                    return function(data) {
                        let option = Array.from(select.options).find(function(item) {
                            return item.value === data.value;
                        });
                        let selectedType = select.form
                            ? select.form.querySelector('input[name="type"]:checked')?.value
                            : null;
                        let categoryType = option?.dataset.type?.trim().toLowerCase();

                        if (
                            selectedType &&
                            ['income', 'expense'].includes(selectedType) &&
                            categoryType &&
                            categoryType !== selectedType
                        ) {
                            return 0;
                        }

                        return textScore(data);
                    };
                };
            }

            if (isCustomerSelect) {
                settings.render.option = function(data, escape) {
                    return `<div class="customer-option-name">${escape(data.text)}</div>`;
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
                if (!instance && !select.disabled) {
                    window.initializeSmoothDropdowns(select);
                    return;
                }
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
