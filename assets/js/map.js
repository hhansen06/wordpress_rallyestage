/* global L */
(function () {
    'use strict';

    var POI_STYLES = {
        ss_start: { color: '#1a7f10', fillColor: '#2ecc40', fillOpacity: 0.9, radius: 9, weight: 2 },
        flying_finish: { color: '#7f1010', fillColor: '#ff4136', fillOpacity: 0.9, radius: 9, weight: 2 },
        spectator: { color: '#0b5394', fillColor: '#0074d9', fillOpacity: 0.9, radius: 7, weight: 2 }
    };

    var POI_LABELS = {
        ss_start: 'SS Start',
        flying_finish: 'Flying Finish',
        spectator: 'Zuschauerpunkt'
    };

    function initAllMaps() {
        var containers = document.querySelectorAll('.rallyestage-map[data-mapdata]');
        console.log('[Rallyestage] initAllMaps – Karten-Container gefunden:', containers.length);

        containers.forEach(function (container) {
            if (container._rsMapInitialized) return;
            container._rsMapInitialized = true;

            var raw64 = container.getAttribute('data-mapdata');
            console.log('[Rallyestage] data-mapdata (base64, erste 80 Zeichen):', raw64 ? raw64.substring(0, 80) : 'LEER');

            var mapData;
            try {
                var raw = atob(raw64);
                console.log('[Rallyestage] JSON dekodiert (erste 200 Zeichen):', raw.substring(0, 200));
                mapData = JSON.parse(raw);
                console.log('[Rallyestage] mapData.tracks:', mapData.tracks);
            } catch (e) {
                console.error('[Rallyestage] Fehler beim Dekodieren:', e);
                return;
            }
            initMap(container.id, mapData);
        });
    }

    function initMap(mapId, mapData) {
        var tracks = mapData.tracks || [];
        console.log('[Rallyestage] initMap – mapId:', mapId, '– tracks.length:', tracks.length);
        var map = L.map(mapId);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 18
        }).addTo(map);

        var allLatLngs = [];

        tracks.forEach(function (track) {
            // Strecke als Linie zeichnen
            if (track.polyline && track.polyline.length > 0) {
                var latlngs = track.polyline.map(function (p) {
                    return [p.lat, p.lng];
                });
                allLatLngs = allLatLngs.concat(latlngs);
                L.polyline(latlngs, { color: '#c0392b', weight: 3, opacity: 0.85 }).addTo(map);
            }

            // POIs zeichnen – mit SVG-Icon wenn vorhanden, sonst CircleMarker
            (track.pois || []).forEach(function (poi) {
                var marker;
                if (poi.icon) {
                    var icon = L.icon({
                        iconUrl: poi.icon,
                        iconSize: [32, 32],
                        iconAnchor: [16, 16],
                        popupAnchor: [0, -18]
                    });
                    marker = L.marker([poi.lat, poi.lng], { icon: icon });
                } else {
                    var style = POI_STYLES[poi.type] || { color: '#555', fillColor: '#aaa', fillOpacity: 0.8, radius: 7, weight: 2 };
                    marker = L.circleMarker([poi.lat, poi.lng], style);
                }
                marker.bindPopup(buildPoiPopup(poi));
                marker.addTo(map);
                allLatLngs.push([poi.lat, poi.lng]);
            });
        });

        if (allLatLngs.length > 0) {
            map.fitBounds(L.latLngBounds(allLatLngs), { padding: [30, 30] });
        } else {
            // Fallback: Deutschland-Mitte
            map.setView([51.1657, 10.4515], 6);
        }
    }

    function buildPoiPopup(poi) {
        var label = POI_LABELS[poi.type] || poi.type;
        if (poi.name) label += ': ' + poi.name;
        if (poi.number != null) label += ' (' + poi.number + ')';
        return label;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllMaps);
    } else {
        initAllMaps();
    }
})();
