import './styles/forgotten-password.css';
import 'smartwizard/dist/css/smart_wizard_all.min.css';
import smartWizard from 'smartwizard';

$(document).ready(function () {
    var wizardForm = $('#smartwizard');
    var storedEmail = '';

    wizardForm.smartWizard({
        theme: 'arrows',
        transition: {
            animation: 'fade',
            speed: '400',
            easing: '',
        },
        lang: {
            next: 'Suivant',
            previous: 'Précédent',
        },
    });

    wizardForm.on('leaveStep', function (e, anchorObject, stepNumber, stepDirection) {
        if (stepDirection !== 'forward') {
            return true;
        }

        if (stepNumber === 0) {
            var email = ($('#email').val() || '').trim();
            if (email === '') {
                $('#error-step-1').text('Renseignez votre e-mail.');
                return false;
            }

            var deferred = $.Deferred();
            $.ajax({
                method: 'POST',
                url: '/forgotten-pass/code',
                data: {
                    email: email,
                    _token: $('#forgot-csrf').val(),
                },
            }).done(function (response) {
                if (!response || response.success !== true) {
                    $('#error-step-1').text(response && response.message ? response.message : 'Une erreur est survenue.');
                    deferred.resolve(false);
                    return;
                }
                storedEmail = email;
                $('#error-step-1').text('');
                deferred.resolve(true);
            }).fail(function (xhr) {
                var message = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'Une erreur est survenue.';
                $('#error-step-1').text(message);
                deferred.resolve(false);
            });

            e.preventDefault();
            deferred.promise().then(function (canLeave) {
                if (canLeave) {
                    wizardForm.smartWizard('next');
                }
            });
            return false;
        }

        if (stepNumber === 1) {
            var code = ($('#code').val() || '').trim();
            if (code === '') {
                $('#error-step-2').text('Renseignez le code reçu par e-mail.');
                return false;
            }

            var verifyDeferred = $.Deferred();
            $.ajax({
                method: 'POST',
                url: '/forgotten-pass/verify',
                data: {
                    email: storedEmail,
                    code: code,
                    _token: $('#forgot-csrf').val(),
                },
            }).done(function (response) {
                if (!response || response.success !== true || !response.redirect) {
                    $('#error-step-2').text(response && response.message ? response.message : 'Le code ne correspond pas.');
                    verifyDeferred.resolve(false);
                    return;
                }
                window.location.href = response.redirect;
                verifyDeferred.resolve(false);
            }).fail(function (xhr) {
                var message = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'Le code ne correspond pas.';
                $('#error-step-2').text(message);
                verifyDeferred.resolve(false);
            });

            e.preventDefault();
            return false;
        }

        return true;
    });
});
