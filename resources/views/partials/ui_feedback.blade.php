{{--
    Umpan balik UI global:
    1. Konfirmasi seragam (SweetAlert2) untuk <form data-confirm="...">.
    2. Loading state otomatis pada submit form (anti klik ganda).
    3. Loading state sementara untuk tautan/form unduhan ([data-download]).
--}}
<style>
    .is-loading { pointer-events: none; opacity: .75; }
    .is-loading .erp-spinner { display: inline-block; }
    .erp-spinner {
        display: none;
        width: 1em; height: 1em;
        border: .15em solid currentColor; border-right-color: transparent;
        border-radius: 50%;
        animation: erp-spin .7s linear infinite;
        vertical-align: -.125em;
        margin-right: .4em;
    }
    .action-icon-btn.is-loading .erp-spinner { margin-right: 0; }
    .action-icon-btn.is-loading > i { display: none; }
    @keyframes erp-spin { to { transform: rotate(360deg); } }
    @media (prefers-reduced-motion: reduce) {
        .erp-spinner {
            animation: none;
            border-right-color: currentColor;
        }
    }
</style>
<script>
    window.ErpUI = (function () {
        const DOWNLOAD_RESET_MS = 4000;

        function esc(value) {
            return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        function setLoading(el, on) {
            if (!el) return;
            if (on) {
                if (el.classList.contains('is-loading')) return;
                el.classList.add('is-loading');
                el.setAttribute('aria-busy', 'true');
                if (!el.querySelector(':scope > .erp-spinner')) {
                    const spinner = document.createElement('span');
                    spinner.className = 'erp-spinner';
                    spinner.setAttribute('aria-hidden', 'true');
                    el.insertBefore(spinner, el.firstChild);
                }
            } else {
                el.classList.remove('is-loading');
                el.removeAttribute('aria-busy');
            }
        }

        function submitButtons(form) {
            return Array.from(form.querySelectorAll('button[type="submit"], button:not([type])'))
                .filter(function (b) { return !b.closest('.swal2-container'); });
        }

        function confirmDialog(opts) {
            const danger = (opts.variant || 'danger') === 'danger';
            let html = '';
            if (opts.subject) html += '<div class="mb-2"><strong>' + esc(opts.subject) + '</strong></div>';
            if (opts.text) html += '<div>' + esc(opts.text) + '</div>';
            if (opts.impactHtml) html += '<div class="mt-2 small text-muted text-start">' + opts.impactHtml + '</div>';
            return Swal.fire({
                title: opts.title || 'Apakah Anda yakin?',
                html: html,
                icon: danger ? 'warning' : 'question',
                showCancelButton: true,
                focusCancel: danger,
                confirmButtonColor: danger ? '#dc3545' : '#0d6efd',
                cancelButtonColor: '#6c757d',
                confirmButtonText: opts.confirmText || 'Ya, Lanjutkan',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                customClass: { popup: 'rounded-4 shadow-lg border-0' }
            });
        }

        document.addEventListener('submit', function (event) {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || form.dataset.noLoading !== undefined) return;
            if ((form.method || 'get').toLowerCase() === 'get') return;

            if (form.dataset.submitting === '1') {
                event.preventDefault();
                return;
            }

            if (form.dataset.confirm !== undefined && form.dataset.confirmed !== '1') {
                event.preventDefault();
                const submitter = event.submitter || null;
                confirmDialog({
                    title: form.dataset.confirmTitle,
                    subject: form.dataset.confirmSubject,
                    text: form.dataset.confirm,
                    impactHtml: form.dataset.confirmImpact ? esc(form.dataset.confirmImpact) : '',
                    confirmText: form.dataset.confirmButton,
                    variant: form.dataset.confirmVariant
                }).then(function (result) {
                    if (!result.isConfirmed) return;
                    form.dataset.confirmed = '1';
                    if (form.requestSubmit) {
                        form.requestSubmit(submitter);
                    } else {
                        form.submit();
                    }
                });
                return;
            }

            const isDownload = form.dataset.download !== undefined;
            const buttons = submitButtons(form);
            const target = event.submitter || buttons[0];
            form.dataset.confirmed = '';
            form.dataset.submitting = '1';
            setLoading(target, true);

            if (isDownload || (form.target && form.target !== '_self')) {
                setTimeout(function () {
                    form.dataset.submitting = '';
                    setLoading(target, false);
                }, DOWNLOAD_RESET_MS);
            }
        });

        document.addEventListener('click', function (event) {
            const link = event.target.closest('a[data-download], button[data-download]');
            if (!link || link.tagName === 'FORM' || link.classList.contains('is-loading')) return;
            setLoading(link, true);
            setTimeout(function () { setLoading(link, false); }, DOWNLOAD_RESET_MS);
        });

        // Kembali via tombol Back (bfcache): pastikan tidak ada tombol yang tertinggal "loading".
        window.addEventListener('pageshow', function (event) {
            if (!event.persisted) return;
            document.querySelectorAll('.is-loading').forEach(function (el) { setLoading(el, false); });
            document.querySelectorAll('form[data-submitting="1"]').forEach(function (f) {
                f.dataset.submitting = '';
                f.dataset.confirmed = '';
            });
        });

        return { esc: esc, confirm: confirmDialog, setLoading: setLoading };
    })();
</script>
