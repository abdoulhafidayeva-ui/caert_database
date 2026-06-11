/**
 * Indicateur de chargement sur les formulaires de connexion / inscription.
 */
(function () {
    'use strict';

    function setSubmitLoading(form) {
        var btn = form.querySelector('button[type="submit"]');
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

    function bindAuthForms() {
        document.querySelectorAll('.caert-auth-body form').forEach(function (form) {
            form.addEventListener('submit', function () {
                setSubmitLoading(form);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindAuthForms);
    } else {
        bindAuthForms();
    }
})();
