/**
 * Liste utilisateurs — suppression, suspension, réinitialisation mot de passe.
 * Modal sans $.fn.modal (DataTables remplace jQuery et retire Bootstrap).
 */
(function ($) {
    'use strict';

    var backdropAttr = 'data-caert-modal-backdrop';
    var passwordVisible = false;
    var lastModalTrigger = null;

    function supportsInert() {
        return 'inert' in HTMLElement.prototype;
    }

    function getModalFocusable(modalEl) {
        if (!modalEl) {
            return null;
        }

        return modalEl.querySelector(
            'input:not([disabled]), button:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        );
    }

    function setModalAccessibility(modalEl, open) {
        if (!modalEl) {
            return;
        }

        if (open) {
            modalEl.setAttribute('aria-hidden', 'false');
            modalEl.setAttribute('aria-modal', 'true');
            if (supportsInert()) {
                modalEl.inert = false;
            } else {
                modalEl.removeAttribute('inert');
            }
            return;
        }

        modalEl.setAttribute('aria-hidden', 'true');
        modalEl.removeAttribute('aria-modal');
        if (supportsInert()) {
            modalEl.inert = true;
        } else {
            modalEl.setAttribute('inert', '');
        }
    }

    function generatePassword(length) {
        var charset = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789!@#$%&*';
        var result = '';
        var i;
        var randomValues = new Uint32Array(length);

        if (window.crypto && window.crypto.getRandomValues) {
            window.crypto.getRandomValues(randomValues);
        } else {
            for (i = 0; i < length; i += 1) {
                randomValues[i] = Math.floor(Math.random() * 4294967296);
            }
        }

        for (i = 0; i < length; i += 1) {
            result += charset[randomValues[i] % charset.length];
        }

        return result;
    }

    function getResetPasswordField() {
        return document.getElementById('userResetPasswordPlain');
    }

    function setPasswordVisibility(visible) {
        passwordVisible = visible;
        var field = getResetPasswordField();
        if (field) {
            field.type = visible ? 'text' : 'password';
        }

        var icon = document.getElementById('userResetPasswordToggleIcon');
        var label = document.getElementById('userResetPasswordToggleLabel');
        var toggleBtn = document.getElementById('userResetPasswordToggleVisibility');

        if (icon) {
            icon.className = visible ? 'fas fa-eye-slash fa-fw' : 'fas fa-eye fa-fw';
        }
        if (label) {
            label.textContent = visible ? 'Masquer' : 'Afficher';
        }
        if (toggleBtn) {
            toggleBtn.setAttribute('aria-label', visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
            toggleBtn.setAttribute('title', visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
        }
    }

    function fillGeneratedPassword() {
        var password = generatePassword(12);
        var plain = getResetPasswordField();

        if (!plain) {
            return;
        }

        plain.value = password;
        setPasswordVisibility(true);
        plain.focus();
        plain.select();
    }

    function copyToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }

        return new Promise(function (resolve, reject) {
            var textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'fixed';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();

            try {
                document.execCommand('copy') ? resolve() : reject();
            } catch (err) {
                reject(err);
            } finally {
                document.body.removeChild(textarea);
            }
        });
    }

    function showCopyFeedback(success) {
        var label = document.getElementById('userResetPasswordCopyLabel');
        if (!label) {
            return;
        }

        var original = label.textContent;
        label.textContent = success ? 'Copié !' : 'Échec';
        window.setTimeout(function () {
            label.textContent = original;
        }, 1500);

        if (typeof Swal !== 'undefined') {
            var modal = document.getElementById('userResetPasswordModal');
            var swalOptions = {
                toast: true,
                position: 'top-end',
                icon: success ? 'success' : 'error',
                title: success ? 'Copié !' : 'Échec',
                showConfirmButton: false,
                timer: 1800,
                timerProgressBar: true,
            };

            if (modal && modal.classList.contains('show')) {
                swalOptions.target = modal;
                swalOptions.customClass = { container: 'caert-swal-in-modal' };
            }

            Swal.fire(swalOptions).then(function () {
                if (modal && modal.classList.contains('show')) {
                    setModalAccessibility(modal, true);
                    var focusable = getModalFocusable(modal);
                    if (focusable) {
                        focusable.focus();
                    }
                }
            });
        }
    }

    function copyResetPassword() {
        var plain = document.getElementById('userResetPasswordPlain');
        if (!plain) {
            return;
        }

        var password = plain.value || '';
        if (password.length < 6) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Rien à copier', 'Saisissez ou générez un mot de passe d\'abord.', 'info');
            } else {
                window.alert('Saisissez ou générez un mot de passe d\'abord.');
            }
            return;
        }

        copyToClipboard(password)
            .then(function () {
                showCopyFeedback(true);
            })
            .catch(function () {
                showCopyFeedback(false);
            });
    }

    function openModal(modalEl) {
        if (!modalEl) {
            return;
        }

        if (modalEl.parentElement !== document.body) {
            document.body.appendChild(modalEl);
        }

        modalEl.classList.add('show');
        modalEl.style.display = 'block';
        setModalAccessibility(modalEl, true);
        document.body.classList.add('modal-open');

        var backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        backdrop.setAttribute(backdropAttr, '1');
        backdrop.addEventListener('click', function () {
            closeModal(modalEl);
        });
        document.body.appendChild(backdrop);
    }

    function closeModal(modalEl) {
        if (!modalEl) {
            return;
        }

        modalEl.classList.remove('show');
        modalEl.style.display = 'none';
        setModalAccessibility(modalEl, false);
        document.body.classList.remove('modal-open');
        document.querySelectorAll('[' + backdropAttr + ']').forEach(function (el) {
            el.remove();
        });

        if (lastModalTrigger && typeof lastModalTrigger.focus === 'function') {
            lastModalTrigger.focus();
        }
    }

    document.addEventListener('click', function (e) {
        var dismissBtn = e.target.closest('[data-dismiss="modal"]');
        if (!dismissBtn) {
            return;
        }

        var modal = dismissBtn.closest('.modal');
        if (modal) {
            e.preventDefault();
            closeModal(modal);
        }
    });

    $(document).on('submit', '.caert-delete-user-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var name = $form.data('user-name') || 'cet utilisateur';

        if (typeof Swal === 'undefined') {
            if (window.confirm('Supprimer ' + name + ' ?')) {
                $form.off('submit').submit();
            }
            return;
        }

        Swal.fire({
            title: 'Supprimer cet utilisateur ?',
            text: name,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonText: 'Annuler',
            confirmButtonText: 'Supprimer',
        }).then(function (result) {
            if (result.isConfirmed) {
                $form.off('submit').submit();
            }
        });
    });

    $(document).on('click', '#userResetPasswordGenerate', function (e) {
        e.preventDefault();
        fillGeneratedPassword();
    });

    $(document).on('click', '#userResetPasswordToggleVisibility', function (e) {
        e.preventDefault();
        setPasswordVisibility(!passwordVisible);
    });

    $(document).on('click', '#userResetPasswordCopy', function (e) {
        e.preventDefault();
        copyResetPassword();
    });

    $(document).on('click', '.caert-reset-password-btn', function (e) {
        e.preventDefault();

        var btn = e.currentTarget;
        lastModalTrigger = btn;
        var modal = document.getElementById('userResetPasswordModal');
        var form = document.getElementById('userResetPasswordForm');

        if (!modal || !form) {
            return;
        }

        form.setAttribute('action', btn.getAttribute('data-action') || '#');
        document.getElementById('userResetPasswordToken').value = btn.getAttribute('data-token') || '';
        document.getElementById('userResetPasswordName').textContent = btn.getAttribute('data-user-name') || '';
        document.getElementById('userResetPasswordEmail').textContent = btn.getAttribute('data-user-email') || '';
        var plainField = getResetPasswordField();
        if (plainField) {
            plainField.value = '';
        }
        document.getElementById('userResetPasswordSendEmail').checked = false;
        setPasswordVisibility(false);

        openModal(modal);

        window.requestAnimationFrame(function () {
            var focusable = document.getElementById('userResetPasswordPlain') || getModalFocusable(modal);
            if (focusable) {
                focusable.focus();
            }
        });
    });

    $(document).on('submit', '#userResetPasswordForm', function (e) {
        var plain = (getResetPasswordField() && getResetPasswordField().value) || '';

        if (plain.length < 6) {
            e.preventDefault();
            if (typeof Swal !== 'undefined') {
                Swal.fire('Mot de passe trop court', 'Au moins 6 caractères requis.', 'warning');
            } else {
                window.alert('Le mot de passe doit contenir au moins 6 caractères.');
            }
        }
    });

    $(document).on('submit', '.caert-user-toggle-form', function (e) {
        var $form = $(this);
        if ($form.data('active') !== '1') {
            return;
        }

        e.preventDefault();

        if (typeof Swal === 'undefined') {
            if (window.confirm('Suspendre ce compte ?')) {
                $form.off('submit').submit();
            }
            return;
        }

        Swal.fire({
            title: 'Suspendre ce compte ?',
            text: 'L\'utilisateur ne pourra plus se connecter.',
            icon: 'question',
            showCancelButton: true,
            cancelButtonText: 'Annuler',
            confirmButtonText: 'Suspendre',
        }).then(function (result) {
            if (result.isConfirmed) {
                $form.off('submit').submit();
            }
        });
    });
})(window.jQuery);
