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

    function resolveSelect2Placeholder($el) {
        var fromData = $el.data('placeholder');
        if (fromData) {
            return fromData;
        }

        var emptyOption = $el.find('option[value=""]').first();
        if (emptyOption.length) {
            var emptyText = $.trim(emptyOption.text());
            if (emptyText) {
                return emptyText;
            }
        }

        return (window.caertI18n && window.caertI18n.select) || 'Select';
    }

    function initSelect2($el) {
        if (!$el.length) {
            return;
        }

        destroySelect2($el);

        var placeholder = resolveSelect2Placeholder($el);

        var hasEmptyOption = $el.find('option[value=""]').length > 0;

        $el.select2({
            placeholder: placeholder,
            allowClear: !$el.prop('multiple') && hasEmptyOption,
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
