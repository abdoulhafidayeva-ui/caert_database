/**
 * Page Analyses — graphiques (jQuery global + Chart.js).
 */
(function ($, window) {
    'use strict';

    if (typeof $ !== 'function') {
        console.error('[caert-graphique] jQuery requis.');
        return;
    }

    function getChart() {
        return window.Chart;
    }

    function i18n(key, fallback) {
        return (window.caertI18n && window.caertI18n[key]) || fallback;
    }

    function formatRegionComparisonTitle(typeLabel) {
        var template = i18n('chartRegionComparison', 'Comparison by region — %type%');
        return template.replace('%type%', typeLabel);
    }

    function normalizeSeries(values) {
        if (Array.isArray(values)) {
            return values.map(function (v) { return Number(v) || 0; });
        }
        if (values && typeof values === 'object') {
            return Object.keys(values)
                .sort(function (a, b) { return Number(a) - Number(b); })
                .map(function (key) { return Number(values[key]) || 0; });
        }
        return [];
    }

    function showChartError($result, message) {
        $result.html(
            '<div class="caert-chart-loading caert-chart-error">' +
            '<em class="fas fa-exclamation-triangle text-warning fa-2x"></em>' +
            '<p>' + message + '</p>' +
            '</div>'
        );
    }

    function renderSummaryBarChart(canvasId, labels, values, title) {
        const Chart = getChart();
        const canvas = document.getElementById(canvasId);
        if (!Chart || !canvas) {
            return;
        }

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: title,
                    data: values,
                    backgroundColor: [
                        'rgba(14, 165, 233, 0.6)',
                        'rgba(239, 68, 68, 0.6)',
                        'rgba(245, 158, 11, 0.6)',
                    ],
                    borderWidth: 1,
                }],
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            },
        });
    }

    function renderSummaryPieChart(canvasId, labels, values, title) {
        const Chart = getChart();
        const canvas = document.getElementById(canvasId);
        if (!Chart || !canvas) {
            return;
        }

        new Chart(canvas, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    label: title,
                    data: values,
                    backgroundColor: [
                        'rgba(14, 165, 233, 0.7)',
                        'rgba(16, 185, 129, 0.7)',
                        'rgba(245, 158, 11, 0.7)',
                    ],
                    borderWidth: 1,
                }],
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } },
            },
        });
    }

    function initGraphiquePage() {
        const Chart = getChart();
        const $form = $('#searchTrend2PageForm');
        if (!$form.length) {
            return;
        }

        if (!Chart) {
            showChartError($('.caert-chart-result'), i18n('chartJsMissing', 'Chart.js is not loaded. Reload the page.'));
            console.error('[caert-graphique] Chart.js non chargé.');
            return;
        }

        const urls = {
            search: $form.attr('data-search-url'),
            incidents: $form.attr('data-incidents-url'),
            targets: $form.attr('data-targets-url'),
        };

        let resultChart = null;
        let requestInFlight = false;

        if (typeof window.caertInitSelect2 === 'function') {
            window.caertInitSelect2($form[0]);
        }

        function setLoading(isLoading) {
            const $btn = $('#generateGraphBtn');
            const $panel = $form.closest('.caert-panel');
            const $result = $('.caert-chart-result');

            if (isLoading) {
                if (!$btn.data('original-html')) {
                    $btn.data('original-html', $btn.html());
                }
                $btn.prop('disabled', true).addClass('is-loading');
                $btn.html('<span class="caert-btn-spinner" aria-hidden="true"></span> ' + i18n('processing', 'Processing…'));
                $panel.addClass('is-processing');
                $result.html(
                    '<div class="caert-chart-loading">' +
                    '<div class="caert-chart-loading-spinner"></div>' +
                    '<p>' + i18n('chartGenerating', 'Generating chart…') + '</p>' +
                    '</div>'
                );
            } else {
                $btn.prop('disabled', false).removeClass('is-loading');
                $panel.removeClass('is-processing');
                if ($btn.data('original-html')) {
                    $btn.html($btn.data('original-html'));
                }
            }
        }

        function validateForm() {
            const start = $.trim(String($('#start').val() || ''));
            const type = $.trim(String($('#type').val() || ''));
            const regions = $('#region').val();

            if (!start || !type || !regions || regions.length === 0) {
                alert((window.caertI18n && window.caertI18n.analyticsPeriodRequired) || 'Please fill in the period, indicator and at least one region.');
                return false;
            }

            if (!urls.search) {
                alert((window.caertI18n && window.caertI18n.analyticsApiMissing) || 'API configuration missing.');
                return false;
            }

            return true;
        }

        function generateGraph() {
            if (requestInFlight) {
                return;
            }

            if (!validateForm()) {
                return;
            }

            requestInFlight = true;
            setLoading(true);

            $.ajax({
                method: 'POST',
                url: urls.search,
                data: $form.serialize(),
                dataType: 'json',
            }).done(function (response) {
                const $result = $('.caert-chart-result');

                if (!response || response.error) {
                    alert((response && response.error) ? response.error : ((window.caertI18n && window.caertI18n.analyticsInvalidResponse) || 'Invalid server response.'));
                    $result.empty();
                    return;
                }

                if (!response.countMonth || !response.regions) {
                    alert((window.caertI18n && window.caertI18n.analyticsInsufficientData) || 'Not enough data to display the chart.');
                    $result.empty();
                    return;
                }

                const colors = [
                    'rgba(14, 165, 233, 0.75)',
                    'rgba(245, 158, 11, 0.75)',
                    'rgba(16, 185, 129, 0.75)',
                    'rgba(139, 92, 246, 0.75)',
                    'rgba(239, 68, 68, 0.75)',
                    'rgba(100, 116, 139, 0.75)',
                ];

                const datasets = [];
                for (let i = 0; i < response.countMonth.length; i++) {
                    datasets.push({
                        label: response.countMonth[i].label,
                        data: normalizeSeries(response.countMonth[i].donnees),
                        backgroundColor: colors[i % colors.length],
                        borderColor: colors[i % colors.length],
                        borderWidth: 1,
                    });
                }

                const labels = Array.isArray(response.regions)
                    ? response.regions
                    : normalizeSeries(response.regions);

                var infoHtml = '';
                if (response.noPublishedData) {
                    var infoMessage = response.info || i18n('analyticsNoPublishedData', 'No published incidents for this period.');
                    infoHtml =
                        '<div class="alert alert-info mb-3" role="status">' +
                        '<em class="fas fa-info-circle mr-1"></em> ' + infoMessage +
                        '</div>';
                }

                $result.html(infoHtml + '<canvas id="resultatGraph" height="120"></canvas>');

                if (resultChart) {
                    resultChart.destroy();
                    resultChart = null;
                }

                const canvas = document.getElementById('resultatGraph');
                if (!canvas) {
                    showChartError($result, i18n('chartDisplayError', 'Unable to display the chart.'));
                    return;
                }

                var typeLabel = response.typeLabel || response.type || String($('#type option:selected').text() || $('#type').val());

                resultChart = new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: datasets,
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { position: 'top' },
                            title: {
                                display: true,
                                text: formatRegionComparisonTitle(typeLabel),
                            },
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { precision: 0 },
                            },
                        },
                    },
                });
            }).fail(function (xhr) {
                let message = i18n('analyticsGenerateFailed', 'Unable to generate the chart.');
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    message = xhr.responseJSON.error;
                } else if (xhr.status === 401 || xhr.status === 403) {
                    message = i18n('analyticsSessionExpired', 'Session expired. Please sign in again.');
                } else if (xhr.status === 500) {
                    message = i18n('analyticsServerError', 'Server error while generating the chart. Try again or contact an administrator.');
                }
                alert(message);
                $('.caert-chart-result').empty();
            }).always(function () {
                requestInFlight = false;
                setLoading(false);
            });
        }

        $('#generateGraphBtn').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            generateGraph();
        });

        $form.on('submit', function (e) {
            e.preventDefault();
            e.stopPropagation();
            generateGraph();
            return false;
        });

        $('.stopSearchTrend2PageForm').on('click', function () {
            if (resultChart) {
                resultChart.destroy();
                resultChart = null;
            }
            $('.caert-chart-result').empty();
            $('#start').val('');
            $('#end').val('');
            $('#type').val($('#type option:first').val()).trigger('change');
            $('#region').val(null).trigger('change');
        });

        if (urls.incidents && document.getElementById('nbTerroristIncidents')) {
            $.getJSON(urls.incidents).done(function (data) {
                renderSummaryBarChart(
                    'nbTerroristIncidents',
                    [
                        (window.caertI18n && window.caertI18n.chartAttacks) || 'Attacks',
                        (window.caertI18n && window.caertI18n.chartDeaths) || 'Deaths',
                        (window.caertI18n && window.caertI18n.chartInjured) || 'Injured',
                    ],
                    [data.totalAttack, data.totalDeath, data.totalInjured],
                    (window.caertI18n && window.caertI18n.chartIncidentsSummary) || 'Terrorist incidents (published total)'
                );
            }).fail(function () {
                console.warn('[caert-graphique] Chargement incidents impossible.');
            });
        }

        if (urls.targets && document.getElementById('prTargetsOfAttacks')) {
            $.getJSON(urls.targets).done(function (data) {
                renderSummaryPieChart(
                    'prTargetsOfAttacks',
                    [
                        (window.caertI18n && window.caertI18n.chartTargetsCivilian) || 'Civilians',
                        (window.caertI18n && window.caertI18n.chartTargetsMilitary) || 'Security / military',
                        (window.caertI18n && window.caertI18n.chartTargetsTerrorist) || 'Terrorists',
                    ],
                    [data.totalCivil, data.totalSecuriteMilitaire, data.totalTerroriste],
                    (window.caertI18n && window.caertI18n.chartTargetsTitle) || 'Attack targets'
                );
            }).fail(function () {
                console.warn('[caert-graphique] Chargement cibles impossible.');
            });
        }
    }

    $(initGraphiquePage);
})(window.jQuery, window);
