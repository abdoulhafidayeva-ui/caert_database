/**
 * Select2 — chargé après DataTables pour rester sur le même jQuery global.
 */
(function ($) {
    'use strict';

    if (!$ || typeof $.fn.select2 !== 'function') {
        return;
    }

    function destroySelect2($el) {
        if (!$el.length || !$el.hasClass('select2-hidden-accessible')) {
            return;
        }

        try {
            $el.select2('destroy');
        } catch (e) {
            console.warn('[caert-select2] destroy', e);
        }
    }

    function initSelect2($el) {
        if (!$el.length) {
            return;
        }

        destroySelect2($el);

        var placeholder = $el.data('placeholder') || 'Sélectionner';

        $el.select2({
            placeholder: placeholder,
            allowClear: !$el.prop('multiple'),
            theme: 'bootstrap4',
            width: '100%',
        });
    }

    window.caertInitSelect2 = function (root) {
        if (typeof $.fn.select2 !== 'function') {
            return;
        }

        var $root = root ? $(root) : $(document);
        $root.find('select.select2').each(function () {
            initSelect2($(this));
        });
    };

    $(function () {
        window.caertInitSelect2();
    });
})(window.jQuery);
