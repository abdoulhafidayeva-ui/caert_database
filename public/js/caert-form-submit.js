/**
 * Indicateur de chargement sur les boutons submit (data-loading-label).
 */
(function () {
    'use strict';

    function setSubmitLoading(form) {
        var btn = form.querySelector('button[type="submit"][data-loading-label]');
        if (!btn || btn.disabled || btn.classList.contains('is-loading')) {
            return;
        }

        if (!btn.dataset.originalHtml) {
            btn.dataset.originalHtml = btn.innerHTML;
        }

        var label = btn.dataset.loadingLabel || 'Chargement…';
        btn.disabled = true;
        btn.classList.add('is-loading');
        btn.innerHTML = '<span class="caert-btn-spinner" aria-hidden="true"></span> ' + label;
    }

    function bindForms() {
        document.querySelectorAll('form').forEach(function (form) {
            if (form.dataset.caertSubmitBound === '1') {
                return;
            }

            var btn = form.querySelector('button[type="submit"][data-loading-label]');
            if (!btn) {
                return;
            }

            form.dataset.caertSubmitBound = '1';
            form.addEventListener('submit', function () {
                setSubmitLoading(form);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindForms);
    } else {
        bindForms();
    }
})();
