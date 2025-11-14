(function () {
    const defaults = {
        lat: 30.0444,
        lng: 31.2357,
        zoom: 6,
        focusZoom: 13,
    };

    function parseCoordinate(value) {
        if (typeof value === 'number') {
            return Number.isFinite(value) ? value : null;
        }

        if (typeof value !== 'string') {
            return null;
        }

        const normalized = value.trim().replace(',', '.');
        if (!normalized) {
            return null;
        }

        const num = Number(normalized);
        return Number.isFinite(num) ? num : null;
    }

    function toFixed(value) {
        return Number.parseFloat(value).toFixed(6);
    }

    function initPicker(picker) {
        if (!picker || picker.aqarandLocationPicker) {
            return;
        }

        if (!window.L) {
            return;
        }

        const mapElement = picker.querySelector('.aqarand-location-picker__map');
        if (!mapElement) {
            return;
        }

        const latInput = document.querySelector(picker.dataset.latInput);
        const lngInput = document.querySelector(picker.dataset.lngInput);

        if (!latInput || !lngInput) {
            return;
        }

        const inputLat = parseCoordinate(latInput.value);
        const inputLng = parseCoordinate(lngInput.value);
        const hasInputCoords = inputLat !== null && inputLng !== null;

        const fallbackLat = parseCoordinate(mapElement.dataset.initialLat) ?? defaults.lat;
        const fallbackLng = parseCoordinate(mapElement.dataset.initialLng) ?? defaults.lng;

        const startLat = hasInputCoords ? inputLat : fallbackLat;
        const startLng = hasInputCoords ? inputLng : fallbackLng;
        const startZoom = hasInputCoords ? defaults.focusZoom : defaults.zoom;

        const map = L.map(mapElement).setView([startLat, startLng], startZoom);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19,
        }).addTo(map);

        let marker = null;

        function placeMarker(lat, lng) {
            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.marker([lat, lng]).addTo(map);
            }
        }

        function centerMap(lat, lng, options = {}) {
            const animate = options.animate !== false;
            const keepZoomIfGreater = options.keepZoomIfGreater !== false;
            const preferredZoom = (typeof options.zoom === 'number' && Number.isFinite(options.zoom))
                ? options.zoom
                : defaults.focusZoom;

            const currentZoom = map.getZoom();
            const targetZoom = keepZoomIfGreater ? Math.max(currentZoom, preferredZoom) : preferredZoom;

            map.setView([lat, lng], targetZoom, { animate });
        }

        function clearMarker() {
            if (marker) {
                map.removeLayer(marker);
                marker = null;
            }
        }

        function clearCoordinate() {
            clearMarker();
            latInput.value = '';
            lngInput.value = '';
        }

        function setCoordinate(lat, lng, options = {}) {
            const latNum = parseCoordinate(lat);
            const lngNum = parseCoordinate(lng);

            if (latNum === null || lngNum === null) {
                if (options.clear !== false) {
                    clearCoordinate();
                }
                return;
            }

            const shouldPan = options.pan !== false;

            latInput.value = toFixed(latNum);
            lngInput.value = toFixed(lngNum);
            placeMarker(latNum, lngNum);

            if (shouldPan) {
                centerMap(latNum, lngNum, options);
            }
        }

        function getCoordinate() {
            const lat = parseCoordinate(latInput.value);
            const lng = parseCoordinate(lngInput.value);

            if (lat === null || lng === null) {
                return null;
            }

            return { lat, lng };
        }

        if (hasInputCoords) {
            placeMarker(inputLat, inputLng);
        }

        map.on('click', function (event) {
            const lat = event.latlng.lat;
            const lng = event.latlng.lng;

            setCoordinate(lat, lng);
        });

        function syncFromInputs() {
            setCoordinate(latInput.value, lngInput.value, { pan: false });
        }

        latInput.addEventListener('change', syncFromInputs);
        lngInput.addEventListener('change', syncFromInputs);

        function invalidateSize() {
            map.invalidateSize();
        }

        const controller = {
            setCoordinate: setCoordinate,
            clearCoordinate: clearCoordinate,
            getCoordinate: getCoordinate,
            invalidateSize: invalidateSize,
        };

        picker.aqarandLocationPicker = controller;
        picker.dispatchEvent(new CustomEvent('aqarandLocationPickerReady', { detail: controller }));

        picker.addEventListener('aqarandLocationPickerInvalidate', invalidateSize);

        let resizeObserver = null;

        if (typeof window.ResizeObserver !== 'undefined') {
            resizeObserver = new window.ResizeObserver(function () {
                invalidateSize();
            });

            resizeObserver.observe(mapElement);
        } else {
            window.addEventListener('resize', invalidateSize);
        }

        setTimeout(invalidateSize, 0);

        controller.destroy = function () {
            picker.removeEventListener('aqarandLocationPickerInvalidate', invalidateSize);

            if (resizeObserver) {
                resizeObserver.disconnect();
            } else {
                window.removeEventListener('resize', invalidateSize);
            }

            picker.aqarandLocationPicker = null;
        };
    }

    function initAllPickers() {
        document.querySelectorAll('.aqarand-location-picker').forEach(initPicker);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllPickers);
    } else {
        initAllPickers();
    }
})();
