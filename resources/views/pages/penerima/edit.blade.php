@extends('layout.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Edit Penerima</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('penerima.update', $data->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Nama Penerima <span class="text-danger">*</span></label>
                                    <input type="text" name="nama"
                                        class="form-control @error('nama') is-invalid @enderror"
                                        value="{{ old('nama', $data->nama) }}" placeholder="Nama peternak/penerima">
                                    @error('nama')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Tujuan <span class="text-danger">*</span></label>
                                    <select name="tujuan_id" class="form-select @error('tujuan_id') is-invalid @enderror">
                                        <option value="">-- Pilih Tujuan --</option>
                                        @foreach ($tujuans as $t)
                                            <option value="{{ $t->id }}"
                                                {{ old('tujuan_id', $data->tujuan_id) == $t->id ? 'selected' : '' }}>
                                                {{ $t->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('tujuan_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Ongkos Angkut (Rp/kg) <span
                                            class="text-danger">*</span></label>
                                    <input type="number" name="ongkos_angkut"
                                        class="form-control @error('ongkos_angkut') is-invalid @enderror"
                                        value="{{ old('ongkos_angkut', $data->ongkos_angkut) }}" placeholder="0"
                                        step="0.01" min="0">
                                    <small class="text-muted">Ongkos untuk lansir mobil</small>
                                    @error('ongkos_angkut')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Ongkos Bongkar (Rp/kg)</label>
                                    <input type="number" name="ongkos_bongkar"
                                        class="form-control @error('ongkos_bongkar') is-invalid @enderror"
                                        value="{{ old('ongkos_bongkar', $data->ongkos_bongkar) }}" placeholder="0"
                                        step="0.01" min="0">
                                    <small class="text-muted">Ongkos untuk tim bongkar (Bangko dan Bungo)</small>
                                    @error('ongkos_bongkar')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Telepon</label>
                                    <input type="text" name="telepon"
                                        class="form-control @error('telepon') is-invalid @enderror"
                                        value="{{ old('telepon', $data->telepon) }}" placeholder="08xx-xxxx-xxxx">
                                    @error('telepon')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-group mb-3">
                                    <label class="form-label">Alamat</label>
                                    <textarea name="alamat" class="form-control @error('alamat') is-invalid @enderror" rows="3"
                                        placeholder="Alamat lengkap penerima">{{ old('alamat', $data->alamat) }}</textarea>
                                    @error('alamat')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- Koordinat GPS --}}
                            <div class="col-12">
                                <div class="card border-info mb-3">
                                    <div class="card-header bg-info bg-opacity-10 py-2">
                                        <h6 class="mb-0 small"><i class="fa fa-map-marker text-info"></i> Koordinat Lokasi
                                            (untuk GPS Tracking)</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-2 mb-2">
                                            <div class="col-md-4">
                                                <label class="form-label small">Latitude</label>
                                                <input type="number" name="lat" id="inputLat" step="0.0000001"
                                                    class="form-control form-control-sm @error('lat') is-invalid @enderror"
                                                    value="{{ old('lat', $data->lat) }}" placeholder="-1.6101">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small">Longitude</label>
                                                <input type="number" name="lng" id="inputLng" step="0.0000001"
                                                    class="form-control form-control-sm @error('lng') is-invalid @enderror"
                                                    value="{{ old('lng', $data->lng) }}" placeholder="103.6131">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small">Radius Geofence (meter)</label>
                                                <input type="number" name="geofence_radius"
                                                    class="form-control form-control-sm"
                                                    value="{{ old('geofence_radius', $data->geofence_radius ?? 500) }}"
                                                    min="50" max="5000" placeholder="500">
                                            </div>
                                        </div>
                                        <small class="text-muted d-block mb-2">Klik peta untuk menentukan lokasi, atau isi
                                            koordinat manual.</small>
                                        <div id="pickerMap"
                                            style="height:280px; border-radius:6px; border:1px solid #dee2e6;"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input type="checkbox" name="is_aktif" class="form-check-input" id="is_aktif"
                                        value="1" {{ old('is_aktif', $data->is_aktif) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_aktif">
                                        Aktif
                                    </label>
                                </div>
                            </div>
                        </div>

                        <button class="btn btn-primary btn-sm" type="submit">Update</button>
                        <a href="{{ route('penerima.index') }}" class="btn btn-secondary btn-sm">Batal</a>
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
                map.setView(e.latlng, map.getZoom());
            });

            // Sync input manual → peta
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
        </script>
    @endpush
@endsection
