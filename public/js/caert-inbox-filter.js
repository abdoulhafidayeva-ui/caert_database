/**
 * Filtres de la file de validation (DataTables).
 */
(function ($) {
    'use strict';

    var FILTER_COLUMNS = ['region', 'pays', 'dateAttaque'];

    function hasValues(values) {
        return Array.isArray(values) && values.length > 0;
    }

    function buildColumnIndexMap(dt) {
        var map = {};
        var settings = dt.settings()[0];

        if (!settings || !settings.aoColumns) {
            return map;
        }

        for (var i = 0; i < settings.aoColumns.length; i++) {
            var dataKey = settings.aoColumns[i].data;
            if (typeof dataKey === 'string' && dataKey !== '') {
                map[dataKey] = i;
            }
        }

        return map;
    }

    function getColumnByKey(dt, columnMap, key) {
        if (!Object.prototype.hasOwnProperty.call(columnMap, key)) {
            return null;
        }

        return dt.column(columnMap[key]);
    }

    function setMultiSearch(dt, columnMap, columnKey, values) {
        var column = getColumnByKey(dt, columnMap, columnKey);
        if (!column) {
            return;
        }

        if (hasValues(values)) {
            column.search(JSON.stringify(values));
        } else {
            column.search('');
        }
    }

    function setDateSearch(dt, columnMap, start, end) {
        var column = getColumnByKey(dt, columnMap, 'dateAttaque');
        if (!column) {
            return;
        }

        var payload = {};
        if (start) {
            payload.start = start;
        }
        if (end) {
            payload.end = end;
        }

        if (Object.keys(payload).length > 0) {
            column.search(JSON.stringify(payload));
        } else {
            column.search('');
        }
    }

    function setSearchLoading(isLoading) {
        var $btn = $('#inboxSearchForm button[type="submit"]');
        var $panel = $('#inboxSearchForm').closest('.caert-panel');

        if (isLoading) {
            if (!$btn.data('original-html')) {
                $btn.data('original-html', $btn.html());
            }
            $btn.prop('disabled', true).addClass('is-loading');
            $btn.html(
                '<span class="caert-btn-spinner" aria-hidden="true"></span> ' +
                ((window.caertI18n && window.caertI18n.searching) || 'Searching…')
            );
            $panel.addClass('is-processing');
        } else {
            $btn.prop('disabled', false).removeClass('is-loading');
            $panel.removeClass('is-processing');
            if ($btn.data('original-html')) {
                $btn.html($btn.data('original-html'));
            }
        }
    }

    function clearAllFilters(dt, columnMap) {
        FILTER_COLUMNS.forEach(function (columnKey) {
            var column = getColumnByKey(dt, columnMap, columnKey);
            if (column) {
                column.search('');
            }
        });
        dt.search('');
    }

    function clearDatepicker($input) {
        if (!$input.length) {
            return;
        }

        $input.val('');

        if (!$input.data('datepicker')) {
            return;
        }

        try {
            $input.datepicker('clearDates');
        } catch (e) {
            try {
                $input.datepicker('setDate', null);
            } catch (e2) {
                // ignore
            }
        }
    }

    function resetFilterForm($form) {
        $form.find('select.select2').each(function () {
            $(this).val(null).trigger('change');
        });

        clearDatepicker($('#inbox_date_start'));
        clearDatepicker($('#inbox_date_end'));

        if (typeof window.caertInitSelect2 === 'function') {
            window.caertInitSelect2('#inboxSearchForm');
        }
    }

    function applyFiltersFromForm(dt, columnMap) {
        setMultiSearch(dt, columnMap, 'region', $('#inbox_region').val());
        setMultiSearch(dt, columnMap, 'pays', $('#inbox_pays').val());
        setDateSearch(
            dt,
            columnMap,
            $.trim($('#inbox_date_start').val() || ''),
            $.trim($('#inbox_date_end').val() || '')
        );
    }

    function drawWithLoading(dt) {
        setSearchLoading(true);
        dt.one('draw', function () {
            setSearchLoading(false);
        });
        dt.draw();
    }

    window.caertInitInboxFilter = function (dt) {
        var $form = $('#inboxSearchForm');
        if (!$form.length || !dt) {
            return;
        }

        var columnMap = buildColumnIndexMap(dt);

        $form.on('submit', function (e) {
            e.preventDefault();
            setSearchLoading(true);
            applyFiltersFromForm(dt, columnMap);
            dt.one('draw', function () {
                setSearchLoading(false);
            });
            dt.draw();
        });

        $('#resetInboxSearchForm').on('click', function (e) {
            e.preventDefault();
            resetFilterForm($form);
            clearAllFilters(dt, columnMap);
            drawWithLoading(dt);
        });
    };
})(window.jQuery);
