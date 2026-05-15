@extends('layout.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Edit Tujuan Pengiriman</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('tujuan.update', $data->id) }}">
                        @csrf
                        @method('put')

                        <div class="form-group mb-3">
                            <label class="form-label">Nama Tujuan <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror"
                                value="{{ old('nama', $data->nama) }}" placeholder="cth: Jambi 1, Bangko, Bungo...">
                            @error('nama')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <div class="d-flex flex-wrap gap-3 mt-1">
                                @foreach ($types as $key => $label)
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="type"
                                            id="type_{{ $key }}" value="{{ $key }}"
                                            {{ old('type', $data->type) === $key ? 'checked' : '' }}>
                                        <label class="form-check-label" for="type_{{ $key }}">
                                            {{ $label }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            @error('type')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-4">
                            <label class="form-label">Status</label>
                            <div>
                                <label>
                                    <input type="checkbox" name="is_aktif" value="1"
                                        {{ old('is_aktif', $data->is_aktif) ? 'checked' : '' }}> Aktif
                                </label>
                            </div>
                        </div>

                        {{-- Koordinat GPS & Marker Idtrack --}}
                        <div class="card border-info mb-4">
                            <div class="card-header bg-info bg-opacity-10 py-2">
                                <h6 class="mb-0 small"><i class="fa fa-map-marker text-info"></i> Koordinat & Marker Idtrack
                                    (GPS Tracking)</h6>
                            </div>
                            <div class="card-body">
                                {{-- Pilih Marker Idtrack --}}
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Marker Idtrack (POI)</label>
                                    <div class="d-flex gap-2">
                                        <select name="idtrack_marker_id" id="selectMarker"
                                            class="form-select form-select-sm">
                                            <option value="">-- Pilih Marker (opsional) --</option>
                                            @if ($data->idtrack_marker_id)
                                                <option value="{{ $data->idtrack_marker_id }}" selected>
                                                    Marker #{{ $data->idtrack_marker_id }} (tersimpan)
                                                </option>
                                            @endif
                                        </select>
                                        <button type="button" class="btn btn-sm btn-outline-info text-nowrap"
                                            id="btnLoadMarkers">
                                            <i class="fa fa-refresh"></i> Muat
                                        </button>
                                    </div>
                                    <small class="text-muted">Pilih marker dari Idtrack agar SPJ bisa di-set otomatis saat
                                        kendaraan berangkat. Prioritas SPJ memakai marker di <strong>Master Penerima</strong>;
                                        field ini tetap dipakai sebagai cadangan jika penerima belum punya marker.</small>
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-md-4">
                                        <label class="form-label small">Latitude</label>
                                        <input type="number" name="lat" id="inputLat" step="0.0000001"
                                            class="form-control form-control-sm" value="{{ old('lat', $data->lat) }}"
                                            placeholder="-1.6101">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Longitude</label>
                                        <input type="number" name="lng" id="inputLng" step="0.0000001"
                                            class="form-control form-control-sm" value="{{ old('lng', $data->lng) }}"
                                            placeholder="103.6131">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Radius Geofence (meter)</label>
                                        <input type="number" name="geofence_radius" class="form-control form-control-sm"
                                            value="{{ old('geofence_radius', $data->geofence_radius ?? 500) }}"
                                            min="50" max="5000" placeholder="500">
                                    </div>
                                </div>
                                <small class="text-muted d-block mb-2">Klik peta untuk menentukan lokasi, atau isi koordinat
                                    manual.</small>
                                <div id="pickerMap" style="height:280px; border-radius:6px; border:1px solid #dee2e6;">
                                </div>
                            </div>
                        </div>

                        <button class="btn btn-primary btn-sm" type="submit">Update</button>
                        <a href="{{ route('tujuan.index') }}" class="btn btn-secondary btn-sm">Batal</a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('css')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endpush
    @push('js')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            var initLat = {{ $data->lat ?? -1.6101 }};
            var initLng = {{ $data->lng ?? 103.6131 }};
            var hasCoord = {{ $data->lat && $data->lng ? 'true' : 'false' }};

            var map = L.map('pickerMap').setView([initLat, initLng], hasCoord ? 15 : 10);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap',
                maxZoom: 19
            }).addTo(map);

            var marker = null;

            function placeMarker(lat, lng) {
                if (marker) marker.setLatLng([lat, lng]);
                else marker = L.marker([lat, lng], {
                    draggable: true
                }).addTo(map);

                marker.on('dragend', function(e) {
                    var pos = e.target.getLatLng();
                    document.getElementById('inputLat').value = pos.lat.toFixed(7);
                    document.getElementById('inputLng').value = pos.lng.toFixed(7);
                });

                document.getElementById('inputLat').value = lat.toFixed(7);
                document.getElementById('inputLng').value = lng.toFixed(7);
            }

            if (hasCoord) placeMarker(initLat, initLng);

            map.on('click', function(e) {
                placeMarker(e.latlng.lat, e.latlng.lng);
            });

            ['inputLat', 'inputLng'].forEach(function(id) {
                document.getElementById(id).addEventListener('change', function() {
                    var lat = parseFloat(document.getElementById('inputLat').value);
                    var lng = parseFloat(document.getElementById('inputLng').value);
                    if (!isNaN(lat) && !isNaN(lng)) {
                        placeMarker(lat, lng);
                        map.setView([lat, lng], 15);
                    }
                });
            });

            // Load markers dari Idtrack
            document.getElementById('btnLoadMarkers').addEventListener('click', function() {
                var btn = this;
                btn.disabled = true;
                btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
                $.getJSON('{{ route('idtrack.markers') }}', function(res) {
                    if (!res.success) return;
                    var sel = document.getElementById('selectMarker');
                    var savedId = {{ $data->idtrack_marker_id ?? 'null' }};
                    sel.innerHTML = '<option value="">-- Pilih Marker --</option>';
                    res.markers.forEach(function(m) {
                        var opt = document.createElement('option');
                        opt.value = m.IDMarker;
                        opt.text = m.Name + (m.Address ? ' — ' + m.Address : '');
                        opt.dataset.lat = m.Lat;
                        opt.dataset.lng = m.Lng;
                        if (m.IDMarker == savedId) opt.selected = true;
                        sel.appendChild(opt);
                    });
                }).always(function() {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa fa-refresh"></i> Muat';
                });
            });

            // Saat marker dipilih, auto-fill koordinat
            document.getElementById('selectMarker').addEventListener('change', function() {
                var opt = this.options[this.selectedIndex];
                if (opt.dataset.lat && opt.dataset.lng) {
                    var lat = parseFloat(opt.dataset.lat);
                    var lng = parseFloat(opt.dataset.lng);
                    placeMarker(lat, lng);
                    map.setView([lat, lng], 15);
                }
            });
        </script>
    @endpush
@endsection
