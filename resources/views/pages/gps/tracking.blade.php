@extends('layout.app')

@push('css')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #map {
            height: calc(100vh - 220px);
            min-height: 450px;
            border-radius: 8px;
        }

        .vehicle-list {
            max-height: calc(100vh - 220px);
            overflow-y: auto;
        }

        .vehicle-card {
            cursor: pointer;
            transition: all .15s;
            border-left: 4px solid transparent;
        }

        .vehicle-card:hover {
            background: #f8fafc;
        }

        .vehicle-card.active {
            border-left-color: #2563eb;
            background: #eff6ff;
        }

        .vehicle-card.type-kendaraan {
            border-left-color: #f59e0b;
        }

        .vehicle-card.type-lansir {
            border-left-color: #10b981;
        }

        .badge-kendaraan {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-lansir {
            background: #d1fae5;
            color: #065f46;
        }

        .pulse-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #10b981;
            display: inline-block;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
                transform: scale(1)
            }

            50% {
                opacity: .5;
                transform: scale(1.4)
            }
        }

        .leaflet-popup-content {
            min-width: 200px;
            font-size: 13px;
        }
    </style>
@endpush

@section('content')
    <div class="row g-3">
        <div class="col-12 col-lg-3">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0"><i class="fa fa-map-marker text-danger"></i> Kendaraan Aktif</h6>
                    <div class="d-flex align-items-center gap-2">
                        <span class="pulse-dot"></span>
                        <small class="text-muted" id="lastUpdate">—</small>
                    </div>
                </div>
                <div class="card-body p-0 vehicle-list" id="vehicleList">
                    <div class="text-center text-muted py-4 small" id="loadingVehicles">
                        <i class="fa fa-spinner fa-spin"></i> Memuat...
                    </div>
                </div>
                <div class="card-footer py-2">
                    <small class="text-muted">Auto-refresh <strong>30 detik</strong></small>
                    <button class="btn btn-xs btn-outline-primary float-end" onclick="fetchPositions()">
                        <i class="fa fa-refresh"></i> Refresh
                    </button>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-9">
            <div class="card">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fa fa-globe text-primary"></i> Peta Tracking GPS</h6>
                    <div class="d-flex flex-wrap gap-2 align-items-center small">
                        <span class="text-danger me-1"><i class="fa fa-flag"></i> Merah: marker tiba (Idtrack)</span>
                        <span class="text-primary me-2"><i class="fa fa-map-marker"></i> Warna besar: posisi GPS</span>
                        <span class="badge badge-kendaraan px-2 py-1"><i class="fa fa-truck"></i> Kendaraan PO</span>
                        <span class="badge badge-lansir px-2 py-1"><i class="fa fa-car"></i> Mobil Lansir</span>
                        <span style="background:#10b981;color:#fff;padding:2px 8px;border-radius:4px;font-size:11px;">✓
                            Tiba</span>
                        <span style="background:#f59e0b;color:#fff;padding:2px 8px;border-radius:4px;font-size:11px;">⏳
                            Jarak</span>
                    </div>
                </div>
                <div class="card-body p-2">
                    <div id="map"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const map = L.map('map').setView([-1.6101, 103.6131], 10);
        L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles &copy; Esri',
            maxZoom: 19,
        }).addTo(map);

        const iconKendaraan = L.divIcon({
            html: `<div style="background:#f59e0b;color:#fff;border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.3);font-size:14px;"><i class="fa fa-truck"></i></div>`,
            className: '',
            iconSize: [32, 32],
            iconAnchor: [16, 16],
            popupAnchor: [0, -18],
        });
        const iconLansir = L.divIcon({
            html: `<div style="background:#10b981;color:#fff;border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.3);font-size:14px;"><i class="fa fa-car"></i></div>`,
            className: '',
            iconSize: [32, 32],
            iconAnchor: [16, 16],
            popupAnchor: [0, -18],
        });

        const visitIcon = L.divIcon({
            html: '<div style="background:#b91c1c;color:#fff;border-radius:50%;width:26px;height:26px;display:flex;align-items:center;justify-content:center;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.35);font-size:12px;"><i class="fa fa-flag"></i></div>',
            className: '',
            iconSize: [26, 26],
            iconAnchor: [13, 13],
            popupAnchor: [0, -14],
        });

        let markers = {};
        let visitMarkersByDevice = {};
        let positions = [];

        function clearVisitMarkersForDevice(deviceId) {
            const id = parseInt(deviceId, 10);
            (visitMarkersByDevice[id] || []).forEach(m => map.removeLayer(m));
            visitMarkersByDevice[id] = [];
        }

        function fetchPositions() {
            $.getJSON('{{ route('gps.all-positions') }}', function(res) {
                if (!res.success) return;
                positions = res.positions;
                updateMap(positions);
                updateSidebar(positions);
                $('#lastUpdate').text(new Date().toLocaleTimeString('id-ID'));
            }).fail(function() {
                $('#vehicleList').html(
                    '<div class="text-center text-danger py-3 small">Gagal memuat data GPS.</div>');
            });
        }

        function updateMap(data) {
            const activeIds = new Set(data.map(p => p.device_id));
            Object.keys(markers).forEach(id => {
                if (!activeIds.has(parseInt(id, 10))) {
                    map.removeLayer(markers[id]);
                    delete markers[id];
                    clearVisitMarkersForDevice(id);
                }
            });

            data.forEach(p => {
                const icon = p.type === 'lansir' ? iconLansir : iconKendaraan;
                const popup = buildPopup(p);
                if (markers[p.device_id]) {
                    markers[p.device_id].setLatLng([p.lat, p.lng]).setPopupContent(popup);
                } else {
                    markers[p.device_id] = L.marker([p.lat, p.lng], {
                        icon
                    }).addTo(map).bindPopup(popup);
                }

                clearVisitMarkersForDevice(p.device_id);
                visitMarkersByDevice[p.device_id] = [];
                (p.visited_markers || []).forEach(v => {
                    if (v.lat == null || v.lng == null) return;
                    const label = v.name || ('Marker #' + v.idtrack_marker_id);
                    const when = v.arrived_at ? new Date(v.arrived_at).toLocaleString('id-ID') : '';
                    const vm = L.marker([v.lat, v.lng], {
                            icon: visitIcon
                        })
                        .addTo(map)
                        .bindPopup(
                            '<strong class="text-danger">' + label + '</strong><br><span class="small text-muted">Tiba: ' +
                            (when || '—') + '</span><br><span class="small text-muted">' + (p.label || '') + '</span>'
                        );
                    visitMarkersByDevice[p.device_id].push(vm);
                });

                checkGeofence(p);
            });

            if (data.length > 0 && Object.keys(markers).length > 0) {
                const layers = [...Object.values(markers), ...Object.values(visitMarkersByDevice).flat()];
                map.fitBounds(L.featureGroup(layers).getBounds().pad(0.2));
            }
        }

        // ── Geofence check ────────────────────────────────────────
        function checkGeofence(p) {
            $.getJSON('{{ route('gps.check-geofence') }}', {
                nopol: p.device_name,
                context: 'kendaraan',
                entity_id: p.assignable_id,
            }).done(function(res) {
                if (!res.success || !res.results || !res.results.length) return;

                var marker = markers[p.device_id];
                if (!marker) return;

                // Buat baris status geofence untuk popup
                var statusLines = res.results.map(function(r) {
                    if (!r.has_poi) return '';
                    return r.inside ?
                        `<div style="margin-top:4px;"><span style="background:#10b981;color:#fff;padding:2px 6px;border-radius:4px;font-size:11px;">✓ Tiba di ${r.tujuan || r.penerima}</span></div>` :
                        `<div style="margin-top:4px;"><span style="background:#f59e0b;color:#fff;padding:2px 6px;border-radius:4px;font-size:11px;">⏳ ${r.distance_m != null ? r.distance_m + 'm dari ' + (r.tujuan || r.penerima) : 'Belum tiba'}</span></div>`;
                }).join('');

                marker.setPopupContent(buildPopup(p) + statusLines);

                // Update badge di sidebar
                var $card = $(`.vehicle-card[data-device="${p.device_id}"]`);
                $card.find('.geofence-badge').remove();

                var anyInside = res.results.some(r => r.inside);
                var dists = res.results.filter(r => r.distance_m != null).map(r => r.distance_m);
                var minDist = dists.length ? Math.min(...dists) : null;

                var badgeHtml = anyInside ?
                    `<span class="geofence-badge ms-1" style="background:#10b981;color:#fff;padding:1px 5px;border-radius:4px;font-size:10px;">✓ Tiba</span>` :
                    (minDist != null ?
                        `<span class="geofence-badge ms-1" style="background:#f59e0b;color:#fff;padding:1px 5px;border-radius:4px;font-size:10px;">${minDist}m</span>` :
                        '');

                $card.find('.label-row').append(badgeHtml);
            });
        }

        function updateSidebar(data) {
            const list = $('#vehicleList');
            if (data.length === 0) {
                list.html('<div class="text-center text-muted py-4 small">Tidak ada kendaraan aktif.</div>');
                return;
            }

            let html = '';
            data.forEach(p => {
                const badgeClass = p.type === 'lansir' ? 'badge-lansir' : 'badge-kendaraan';
                const typeLabel = p.type === 'lansir' ? 'Mobil Lansir' : 'Kendaraan PO';
                const speed = p.speed != null ? `${p.speed} km/h` : '—';
                const driver = p.DriverName || p.driverName || '';
                const status = p.statusEng || p.StatusEng || '';
                const idtrackExtra = (driver || status) ?
                    `<div class="text-muted mt-1" style="font-size:10px;line-height:1.3;">
                        ${driver ? `<div><i class="fa fa-user"></i> ${driver}</div>` : ''}
                        ${status ? `<div><i class="fa fa-info-circle"></i> ${status}</div>` : ''}
                    </div>` : '';
                const visitHint = (p.visited_markers && p.visited_markers.length) ?
                    `<div class="small text-danger mt-1" style="font-size:10px;"><i class="fa fa-flag"></i> ${p.visited_markers.length} marker tiba</div>` : '';
                const address = p.address ?
                    `<div class="text-muted" style="font-size:11px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${p.address}</div>` :
                    '';

                html += `
                <div class="vehicle-card p-2 border-bottom type-${p.type}" data-device="${p.device_id}" onclick="focusVehicle(${p.device_id})">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="label-row fw-semibold small">${p.label}</div>
                        <span class="badge ${badgeClass} ms-1" style="font-size:10px;">${typeLabel}</span>
                    </div>
                    ${p.no_po ? `<div class="text-muted" style="font-size:11px;">PO: ${p.no_po}</div>` : ''}
                    <div class="d-flex gap-3 mt-1">
                        <span class="text-muted" style="font-size:11px;"><i class="fa fa-tachometer"></i> ${speed}</span>
                        ${p.last_update ? `<span class="text-muted" style="font-size:11px;"><i class="fa fa-clock-o"></i> ${p.last_update}</span>` : ''}
                    </div>
                    ${idtrackExtra}
                    ${visitHint}
                    ${address}
                </div>`;
            });

            list.html(html);
            $('#loadingVehicles').hide();
        }

        function buildPopup(p) {
            const driver = p.DriverName || p.driverName || '';
            const status = p.statusEng || p.StatusEng || '';
            const phone = p.phone || '';
            const imei = p.imei || '';
            const driverLine = driver ? `Driver: <strong>${driver}</strong><br>` : '';
            const statusLine = status ? `Status: <strong>${status}</strong><br>` : '';
            const phoneLine = phone ? `Telp: ${phone}<br>` : '';
            const imeiLine = imei ? `IMEI: <span class="small">${imei}</span><br>` : '';
            const visitCount = (p.visited_markers && p.visited_markers.length) ?
                `<span class="small text-danger"><i class="fa fa-flag"></i> ${p.visited_markers.length} marker Idtrack tiba</span><br>` : '';
            return `<div>
                <strong>${p.label}</strong><br>
                ${p.no_po ? `<span class="text-muted small">PO: ${p.no_po}</span><br>` : ''}
                ${visitCount}
                ${driverLine}${statusLine}${phoneLine}${imeiLine}
                ${p.speed != null ? `Kecepatan: <strong>${p.speed} km/h</strong><br>` : ''}
                ${p.address ? `<span class="text-muted small">${p.address}</span><br>` : ''}
                ${p.gps_time || p.last_update ? `<span class="text-muted small">Update: ${p.gps_time || p.last_update}</span>` : ''}
            </div>`;
        }

        function focusVehicle(deviceId) {
            const marker = markers[deviceId];
            if (!marker) return;
            map.setView(marker.getLatLng(), 15);
            marker.openPopup();
            $('.vehicle-card').removeClass('active');
            $(`.vehicle-card[data-device="${deviceId}"]`).addClass('active');
        }

        fetchPositions();
        setInterval(fetchPositions, 30000);
    </script>
@endpush
