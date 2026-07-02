/**
 * Bootstrap Datepicker — popup au clic (pas de calendrier inline).
 */
(function ($) {
    'use strict';

    function resolveDatepickerLanguage() {
        var lang = (document.documentElement.lang || 'fr').toLowerCase().split('-')[0];
        return lang === 'en' ? 'en' : 'fr';
    }

    function initCaertDatepicker($input, extra) {
        if (!$input.length || $input.data('datepicker')) {
            return;
        }

        var defaults = {
            format: 'dd/mm/yyyy',
            todayHighlight: true,
            autoclose: true,
            language: resolveDatepickerLanguage(),
            clearBtn: true,
            container: 'body',
            orientation: 'bottom auto',
            enableOnReadonly: true,
        };

        $input.datepicker($.extend({}, defaults, extra || {}));

        var $group = $input.closest('.input-group.date');
        if ($group.length) {
            $group.find('.input-group-text, .input-group-append').on('click', function () {
                $input.datepicker('show');
            });
        }
    }

    window.caertInitDatepickers = function (root) {
        var $root = root ? $(root) : $(document);

        $root.find('.js-datepicker').each(function () {
            initCaertDatepicker($(this));
        });

        $root.find('#createdAt_start').each(function () {
            initCaertDatepicker($(this), { orientation: 'bottom auto' });
        });

        $root.find('#createdAt_end').each(function () {
            initCaertDatepicker($(this), { orientation: 'bottom auto' });
        });
    };

    $(function () {
        caertInitDatepickers();
    });
})(jQuery);
