/**

 * Liste des pays filtrée par région (inscription, incidents, filtres).

 */

(function (window, $) {

    if (!$) {

        return;

    }

    'use strict';



    function isSelect2Available() {

        return typeof $.fn.select2 === 'function';

    }



    function destroySelect2($select) {

        if (!isSelect2Available() || !$select.length || !$select.hasClass('select2-hidden-accessible')) {

            return;

        }



        try {

            $select.select2('destroy');

        } catch (e) {

            console.warn('[caert-region-pays] select2 destroy', e);

        }

    }



    function initSelect2($select, options) {

        if (!isSelect2Available() || !$select.length || $select.hasClass('select2-hidden-accessible')) {

            return;

        }



        $select.select2(options || {

            placeholder: (window.caertI18n && window.caertI18n.select) || 'Select',

            allowClear: true,

            theme: 'bootstrap4',

            width: '100%',

        });

    }



    function apiUrl() {

        return document.body.getAttribute('data-pays-by-region-url') || '/api/pays-by-region';

    }



    function normalizeRegionValues(raw) {

        if (raw == null || raw === '') {

            return [];

        }

        if (Array.isArray(raw)) {

            return raw.filter(function (v) {

                return typeof v === 'string' && v.trim() !== '';

            });

        }

        return typeof raw === 'string' && raw.trim() !== '' ? [raw] : [];

    }



    function fetchPaysByRegion(regionLibelles) {

        var params = new URLSearchParams();

        normalizeRegionValues(regionLibelles).forEach(function (libelle) {

            params.append('region[]', libelle);

        });



        return fetch(apiUrl() + '?' + params.toString(), {

            headers: { Accept: 'application/json' },

            credentials: 'same-origin',

        }).then(function (response) {

            if (!response.ok) {

                throw new Error('pays-by-region HTTP ' + response.status);

            }

            return response.json();

        }).then(function (data) {

            return Array.isArray(data.pays) ? data.pays : [];

        });

    }



    function rebuildNativeSelect(select, paysList, options) {

        var valueKey = options.valueKey || 'id';

        var placeholder = options.placeholder || (window.caertI18n && window.caertI18n.choose) || 'Choose';

        var previous = select.value;

        var allowed = new Set();



        select.innerHTML = '';

        var empty = document.createElement('option');

        empty.value = '';

        empty.textContent = placeholder;

        select.appendChild(empty);



        paysList.forEach(function (p) {

            var opt = document.createElement('option');

            opt.value = String(p[valueKey] != null ? p[valueKey] : p.libelle);

            opt.textContent = p.libelle;

            select.appendChild(opt);

            allowed.add(opt.value);

        });



        if (previous && allowed.has(previous)) {

            select.value = previous;

        } else {

            select.value = '';

        }

    }



    function defaultSelect2Options() {

        return {

            placeholder: (window.caertI18n && window.caertI18n.select) || 'Select',

            allowClear: true,

            theme: 'bootstrap4',

            width: '100%',

        };

    }



    function rebuildJquerySelect($select, paysList, options) {

        var valueKey = options.valueKey || 'libelle';

        var previous = $select.val() || [];

        if (!Array.isArray(previous)) {

            previous = previous ? [previous] : [];

        }



        destroySelect2($select);



        $select.empty();

        paysList.forEach(function (p) {

            var val = String(p[valueKey] != null ? p[valueKey] : p.libelle);

            $select.append(new Option(p.libelle, val, false, false));

        });



        initSelect2($select, defaultSelect2Options());



        var allowed = new Set($select.find('option').map(function () {

            return $(this).val();

        }).get());

        var kept = previous.filter(function (v) {

            return allowed.has(v);

        });



        $select.val(kept.length ? kept : null).trigger('change');

    }



    window.caertFetchPaysByRegion = fetchPaysByRegion;



    window.caertRefreshPaysSelect = function (regionValues, paysSelect, options) {

        options = options || {};

        var regions = normalizeRegionValues(regionValues);



        return fetchPaysByRegion(regions).then(function (paysList) {

            if (!paysSelect) {

                return paysList;

            }



            if (paysSelect instanceof jQuery) {

                rebuildJquerySelect(paysSelect, paysList, options);

            } else if (paysSelect.jquery) {

                rebuildJquerySelect(paysSelect, paysList, options);

            } else {

                rebuildNativeSelect(paysSelect, paysList, options);

            }



            return paysList;

        });

    };



    function findRegionSelect(form) {
        // Incidents: *_regions ; inscription / admin user: *_region
        return form.querySelector('select[id$="_regions"]')
            || form.querySelector('select[name$="[regions]"]')
            || form.querySelector('select[id$="_region"]')
            || form.querySelector('select[name$="[region]"]');
    }

    function regionLibelleFromSelect(regionEl) {
        if (!regionEl || regionEl.selectedIndex < 0) {
            return '';
        }
        var selectedOption = regionEl.options[regionEl.selectedIndex];
        if (!selectedOption || selectedOption.value === '') {
            return '';
        }
        return (selectedOption.textContent || selectedOption.text || '').trim();
    }

    function refreshPaysForRegionSelect(regionEl, paysEl) {
        var libelle = regionLibelleFromSelect(regionEl);
        if (!libelle) {
            rebuildNativeSelect(paysEl, [], {
                placeholder: (window.caertI18n && window.caertI18n.choose) || 'Choose',
            });
            return;
        }

        paysEl.disabled = true;
        fetchPaysByRegion([libelle])
            .then(function (paysList) {
                rebuildNativeSelect(paysEl, paysList, {
                    valueKey: 'id',
                    placeholder: (window.caertI18n && window.caertI18n.choose) || 'Choose',
                });
            })
            .catch(function (err) {
                console.error('[caert-region-pays]', err);
            })
            .finally(function () {
                paysEl.disabled = false;
            });
    }

    function bindSymfonyForms() {
        document.querySelectorAll('form').forEach(function (form) {
            var regionEl = findRegionSelect(form);
            var paysEl = form.querySelector('select[id$="_pays"]')
                || form.querySelector('select[name$="[pays]"]');

            if (!regionEl || !paysEl || regionEl.dataset.caertRegionPaysBound === '1') {
                return;
            }

            regionEl.dataset.caertRegionPaysBound = '1';

            regionEl.addEventListener('change', function () {
                refreshPaysForRegionSelect(regionEl, paysEl);
            });

            // Select2 (si présent) déclenche aussi change jQuery
            if ($ && regionEl.id) {
                $(regionEl).on('change.caertRegionPays select2:select.caertRegionPays select2:clear.caertRegionPays', function () {
                    refreshPaysForRegionSelect(regionEl, paysEl);
                });
            }
        });
    }



    function bindDashboardRegionPays() {

        var $region = $('#searchForm #region');

        var $pays = $('#searchForm #pays');

        if (!$region.length || !$pays.length || $region.data('caertRegionPaysBound')) {

            return;

        }



        var allPaysOptions = $pays.find('option').map(function () {

            return { value: $(this).val(), text: $(this).text() };

        }).get();



        window.caertDashboardAllPaysOptions = allPaysOptions;



        function restoreAllPays() {

            destroySelect2($pays);



            $pays.empty();

            allPaysOptions.forEach(function (opt) {

                $pays.append(new Option(opt.text, opt.value, false, false));

            });



            initSelect2($pays, defaultSelect2Options());

            $pays.val(null).trigger('change');

        }



        function refreshPaysFromRegions() {

            var regions = $region.val() || [];

            if (!regions.length) {

                restoreAllPays();

                return;

            }



            $pays.prop('disabled', true);

            window.caertRefreshPaysSelect(regions, $pays, { valueKey: 'libelle' })

                .catch(function (err) {

                    console.error('[caert-region-pays] dashboard', err);

                })

                .finally(function () {

                    $pays.prop('disabled', false);

                });

        }



        $region.on(

            'change.caertDashboardRegionPays select2:select.caertDashboardRegionPays select2:unselect.caertDashboardRegionPays select2:clear.caertDashboardRegionPays',

            function () {

                window.setTimeout(refreshPaysFromRegions, 0);

            }

        );



        $region.data('caertRegionPaysBound', true);

    }



    function onReady() {

        bindSymfonyForms();

        bindDashboardRegionPays();

    }



    if (document.readyState === 'loading') {

        document.addEventListener('DOMContentLoaded', onReady);

    } else {

        onReady();

    }



    $(function () {

        window.setTimeout(bindDashboardRegionPays, 300);

    });

})(window, window.jQuery);

