/**
 * Filtres du tableau de bord — recherche DataTables par index de colonne.
 */
(function ($) {
    'use strict';

    var FILTER_COLUMNS = ['isPublished', 'attaque', 'cible', 'pays', 'perpetrateurs', 'espace', 'region', 'dateAttaque'];

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
            console.warn('[caert-filter] Colonne introuvable:', key);
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
        var $btn = $('#searchForm button[type="submit"]');
        var $panel = $('#searchForm').closest('.caert-panel');

        if (isLoading) {
            if (!$btn.data('original-html')) {
                $btn.data('original-html', $btn.html());
            }
            $btn.prop('disabled', true).addClass('is-loading');
            $btn.html('<span class="caert-btn-spinner" aria-hidden="true"></span> ' + ((window.caertI18n && window.caertI18n.searching) || 'Searching…'));
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
            return;
        } catch (e) {
            // fallback ci-dessous
        }

        try {
            $input.datepicker('setDate', null);
        } catch (e2) {
            console.warn('[caert-filter] impossible de réinitialiser le datepicker', e2);
        }
    }

    function getDefaultOrder(columnMap) {
        var order = [];

        if (Object.prototype.hasOwnProperty.call(columnMap, 'dateAttaque')) {
            order.push([columnMap.dateAttaque, 'desc']);
        }
        if (Object.prototype.hasOwnProperty.call(columnMap, 'id')) {
            order.push([columnMap.id, 'desc']);
        }

        return order.length ? order : [[0, 'desc']];
    }

    function resetFilterForm($form, allPaysOptions) {
        $form.find('select.select2').each(function () {
            $(this).val(null).trigger('change');
        });

        if (allPaysOptions && $('#pays').length) {
            restoreAllPaysOptions($('#searchForm #pays'), allPaysOptions);
        }

        clearDatepicker($('#createdAt_start'));
        clearDatepicker($('#createdAt_end'));

        if (typeof window.caertInitSelect2 === 'function') {
            window.caertInitSelect2('#searchForm');
        }
    }

    function clearTableStateFromUrl() {
        if (!window.history) {
            return;
        }

        var params = new URLSearchParams(window.location.search);
        var year = params.get('year');
        var next = window.location.pathname;
        if (year) {
            next += '?year=' + encodeURIComponent(year);
        }
        window.history.replaceState(null, '', next);
    }

    function isSelect2Available() {
        return typeof $.fn.select2 === 'function';
    }

    function initSelect2($el) {
        if (!isSelect2Available() || !$el.length || $el.hasClass('select2-hidden-accessible')) {
            return;
        }

        $el.select2({
            placeholder: (window.caertI18n && window.caertI18n.select) || 'Select',
            allowClear: true,
            theme: 'bootstrap4',
            width: '100%',
        });
    }

    function destroySelect2($el) {
        if (!isSelect2Available() || !$el.length || !$el.hasClass('select2-hidden-accessible')) {
            return;
        }

        try {
            $el.select2('destroy');
        } catch (e) {
            console.warn('[caert-filter] select2 destroy', e);
        }
    }

    function snapshotPaysOptions($pays) {
        return $pays.find('option').map(function () {
            return { value: $(this).val(), text: $(this).text() };
        }).get();
    }

    function restoreAllPaysOptions($pays, allOptions) {
        if (!$pays.length || !allOptions) {
            return;
        }

        destroySelect2($pays);

        $pays.empty();
        allOptions.forEach(function (opt) {
            $pays.append(new Option(opt.text, opt.value, false, false));
        });

        initSelect2($pays);
        $pays.val(null).trigger('change');

        if (typeof window.caertInitSelect2 === 'function') {
            window.caertInitSelect2('#searchForm');
        }
    }

    function readScopeDefaults($form) {
        return {
            region: $form.data('default-region') || '',
            dateStart: $form.data('default-date-start') || '',
            dateEnd: $form.data('default-date-end') || '',
        };
    }

    function applyScopeDefaultsToForm($form, defaults) {
        if (!defaults) {
            return;
        }

        if (defaults.region) {
            $('#region').val([defaults.region]).trigger('change');
        }
        if (defaults.dateStart) {
            $('#createdAt_start').val(defaults.dateStart);
        }
        if (defaults.dateEnd) {
            $('#createdAt_end').val(defaults.dateEnd);
        }
    }

    function applyScopeDefaultsToTable(dt, columnMap, defaults) {
        if (!defaults) {
            return;
        }

        if (defaults.region) {
            setMultiSearch(dt, columnMap, 'region', [defaults.region]);
        }
        setDateSearch(dt, columnMap, defaults.dateStart || '', defaults.dateEnd || '');
    }

    function applyFiltersFromForm(dt, columnMap) {
        setMultiSearch(dt, columnMap, 'isPublished', $('#filterStatus').val());
        setMultiSearch(dt, columnMap, 'attaque', $('#attaque').val());
        setMultiSearch(dt, columnMap, 'cible', $('#cible').val());
        setMultiSearch(dt, columnMap, 'pays', $('#pays').val());
        setMultiSearch(dt, columnMap, 'perpetrateurs', $('#perpetrateur').val());
        setMultiSearch(dt, columnMap, 'espace', $('#espace').val());
        setMultiSearch(dt, columnMap, 'region', $('#region').val());
        setDateSearch(
            dt,
            columnMap,
            $.trim($('#createdAt_start').val() || ''),
            $.trim($('#createdAt_end').val() || '')
        );
    }

    function drawWithLoading(dt) {
        setSearchLoading(true);
        dt.one('draw', function () {
            setSearchLoading(false);
        });
        dt.draw();
    }

    function getAllPaysOptions() {
        if (window.caertDashboardAllPaysOptions && window.caertDashboardAllPaysOptions.length) {
            return window.caertDashboardAllPaysOptions;
        }

        return snapshotPaysOptions($('#searchForm #pays'));
    }

    window.caertInitDashboardFilter = function (dt) {
        var $form = $('#searchForm');
        if (!$form.length || !dt) {
            return;
        }

        var allPaysOptions = getAllPaysOptions();
        var columnMap = buildColumnIndexMap(dt);
        var scopeDefaults = readScopeDefaults($form);

        $form.on('submit', function (e) {
            e.preventDefault();
            setSearchLoading(true);
            applyFiltersFromForm(dt, columnMap);

            dt.one('draw', function () {
                setSearchLoading(false);
            });
            dt.draw();
        });

        $('#resetSearchForm').on('click', function (e) {
            e.preventDefault();
            resetFilterForm($form, allPaysOptions);
            clearAllFilters(dt, columnMap);
            clearTableStateFromUrl();
            applyScopeDefaultsToForm($form, scopeDefaults);
            applyScopeDefaultsToTable(dt, columnMap, scopeDefaults);
            dt.order(getDefaultOrder(columnMap));
            drawWithLoading(dt);
        });

        if (scopeDefaults.region || scopeDefaults.dateStart || scopeDefaults.dateEnd) {
            applyScopeDefaultsToForm($form, scopeDefaults);
            applyScopeDefaultsToTable(dt, columnMap, scopeDefaults);
            drawWithLoading(dt);
        }
    };
})(window.jQuery);
