/**
 * Carte SIG — popup pays avec statistiques globales uniquement.
 */
(function (window) {
    'use strict';

    var activeMarker = null;
    var activeCountry = null;

    function t(key, fallback) {
        return (window.caertMapI18n && window.caertMapI18n[key]) || fallback;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function buildCountryPopup(props) {
        return (
            '<div class="caert-map-popup">' +
            '<h3 class="caert-map-popup__title">' + escapeHtml(props.country) + '</h3>' +
            '<div class="caert-map-popup__stats">' +
            '<div class="caert-map-popup__stat">' +
            '<span class="caert-map-popup__value">' + props.count + '</span>' +
            '<span class="caert-map-popup__label">' + escapeHtml(t('incidents', 'Incidents')) + '</span>' +
            '</div>' +
            '<div class="caert-map-popup__stat">' +
            '<span class="caert-map-popup__value">' + props.deaths + '</span>' +
            '<span class="caert-map-popup__label">' + escapeHtml(t('deaths', 'Deaths')) + '</span>' +
            '</div>' +
            '<div class="caert-map-popup__stat">' +
            '<span class="caert-map-popup__value">' + (props.injured || 0) + '</span>' +
            '<span class="caert-map-popup__label">' + escapeHtml(t('injured', 'Injured')) + '</span>' +
            '</div>' +
            '</div>' +
            '<p class="caert-map-popup__note">' + escapeHtml(t('statsScope', 'Published incidents aggregated at national level.')) + '</p>' +
            '</div>'
        );
    }

    function markerBaseRadius(marker) {
        var count = marker.feature && marker.feature.properties ? marker.feature.properties.count : 1;
        return Math.min(16, 6 + (count || 1) * 2);
    }

    function setActiveMarker(marker, country) {
        if (activeMarker && activeMarker !== marker) {
            resetMarkerStyle(activeMarker);
        }
        activeMarker = marker;
        activeCountry = country;
        marker.setStyle({
            radius: markerBaseRadius(marker) + 3,
            weight: 3,
            fillOpacity: 0.95,
            color: '#006B3F',
            fillColor: '#C5A059',
        });
        marker.bringToFront();
    }

    function resetMarkerStyle(marker) {
        marker.setStyle({
            radius: markerBaseRadius(marker),
            weight: 1,
            fillOpacity: 0.75,
            color: '#7A1515',
            fillColor: '#A61C1C',
        });
    }

    function clearSelection() {
        if (activeMarker) {
            resetMarkerStyle(activeMarker);
            activeMarker.closePopup();
        }
        activeMarker = null;
        activeCountry = null;
    }

    function initCaertMap(config) {
        if (!window.L || !config.countriesUrl) {
            return;
        }

        var map = window.L.map('map', { zoomControl: true }).setView([2, 20], 3);
        window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap',
        }).addTo(map);

        map.on('click', function () {
            clearSelection();
        });

        fetch(config.countriesUrl)
            .then(function (r) { return r.json(); })
            .then(function (countriesGeo) {
                var layer = window.L.geoJSON(countriesGeo, {
                    pointToLayer: function (feature, latlng) {
                        return window.L.circleMarker(latlng, {
                            radius: Math.min(16, 6 + (feature.properties.count || 1) * 2),
                            fillColor: '#A61C1C',
                            color: '#7A1515',
                            weight: 1,
                            fillOpacity: 0.75,
                        });
                    },
                    onEachFeature: function (feature, markerLayer) {
                        markerLayer.feature = feature;

                        markerLayer.bindPopup(
                            buildCountryPopup(feature.properties),
                            {
                                className: 'caert-map-leaflet-popup',
                                minWidth: 220,
                                maxWidth: 280,
                                closeButton: true,
                                autoPan: true,
                            }
                        );

                        markerLayer.on('click', function (event) {
                            window.L.DomEvent.stopPropagation(event);

                            var country = feature.properties.country;
                            if (activeCountry === country) {
                                clearSelection();
                                return;
                            }

                            setActiveMarker(markerLayer, country);
                            markerLayer.openPopup();
                        });

                        markerLayer.on('popupclose', function () {
                            if (activeCountry === feature.properties.country) {
                                resetMarkerStyle(markerLayer);
                                activeMarker = null;
                                activeCountry = null;
                            }
                        });
                    },
                }).addTo(map);

                if (layer.getBounds().isValid()) {
                    map.fitBounds(layer.getBounds(), { padding: [30, 30], maxZoom: 5 });
                }
            })
            .catch(function (err) {
                console.error('[caert-map]', err);
            });
    }

    window.initCaertMap = initCaertMap;
})(window);
