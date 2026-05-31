@extends('layout.app')
@section('content')
    {{-- Header --}}
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center py-3">
            <div>
                <h5 class="mb-1 fw-bold"><i class="fa fa-plus-circle text-primary"></i> Input Manual Stok Gudang {{ $gudang->nama }}</h5>
                <span class="text-muted small">Tambahkan atau kurangi stok secara manual dengan mencatat mutasi</span>
            </div>
            <a href="{{ route('gudang.stok.show', $gudang->id) }}" class="btn btn-sm btn-secondary">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    {{-- Form Input --}}
    <div class="card">
        <div class="card-header py-3">
            <h6 class="mb-0 fw-bold">Form Input Manual</h6>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('gudang.stok.store-manual', $gudang->id) }}" method="POST">
                @csrf
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small">Kode Pakan <span class="text-danger">*</span></label>
                        <select name="kode_pakan_id" id="selectKodePakan" class="form-select" required>
                            <option value="">-- Pilih Kode Pakan --</option>
                            @foreach ($kodePakans as $pakan)
                                <option value="{{ $pakan->id }}" data-kode="{{ $pakan->kode }}" data-nama="{{ $pakan->nama }}">
                                    {{ $pakan->kode }} — {{ $pakan->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Tipe Mutasi <span class="text-danger">*</span></label>
                        <select name="tipe" id="selectTipe" class="form-select" required>
                            <option value="">-- Pilih Tipe --</option>
                            <option value="masuk">Masuk (Tambah Stok)</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label small">Stok Awal (kg)</label>
                        <input type="text" id="inputStokAwal" class="form-control bg-light" readonly value="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Jumlah (kg) <span class="text-danger">*</span></label>
                        <input type="number" name="jumlah_kg" id="inputJumlahKg" class="form-control"
                            step="0.01" min="0.01" value="{{ old('jumlah_kg') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Jumlah (karung)</label>
                        <input type="number" name="jumlah_karung" id="inputJumlahKarung" class="form-control"
                            min="0" value="{{ old('jumlah_karung') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Stok Akhir (kg)</label>
                        <input type="text" id="inputStokSaatIni" class="form-control bg-light" readonly value="0">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small">Catatan <span class="text-muted">(opsional)</span></label>
                    <textarea name="catatan" id="inputCatatan" class="form-control" rows="3">{{ old('catatan') }}</textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> Simpan Input Manual
                    </button>
                    <a href="{{ route('gudang.stok.show', $gudang->id) }}" class="btn btn-outline-secondary">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        let stokAwal = 0;

        $(document).ready(function() {
            // Ambil stok saat ini ketika memilih kode pakan
            $('#selectKodePakan').on('change', function() {
                var pakanId = $(this).val();
                if (pakanId) {
                    $.ajax({
                        url: '{{ route('gudang.stok.saldo') }}',
                        type: 'GET',
                        data: {
                            tujuan_id: '{{ $gudang->id }}',
                            kode_pakan_id: pakanId
                        },
                        success: function(response) {
                            stokAwal = parseFloat(response.stok_kg);
                            updateStokTampil();
                        }
                    });
                } else {
                    stokAwal = 0;
                    $('#inputStokAwal').val('0');
                    $('#inputStokSaatIni').val('0');
                }
            });

            // Auto hitung karung
            $('#inputJumlahKg').on('input', function() {
                var kg = parseFloat($(this).val()) || 0;
                $('#inputJumlahKarung').val(Math.ceil(kg / 50));
                updateStokTampil();
            });

            // Update stok tampil ketika tipe berubah
            $('#selectTipe, #inputJumlahKg').on('change input', function() {
                updateStokTampil();
            });
        });

        function updateStokTampil() {
            var kg = parseFloat($('#inputJumlahKg').val()) || 0;
            var tipe = $('#selectTipe').val();
            var stokBaru = stokAwal;

            if (tipe === 'masuk') {
                stokBaru = parseFloat((stokAwal + kg).toFixed(2));
            } else if (tipe === 'keluar') {
                stokBaru = parseFloat((stokAwal - kg).toFixed(2));
            }

            $('#inputStokAwal').val(stokAwal.toLocaleString('id-ID', { maximumFractionDigits: 2 }));
            $('#inputStokSaatIni').val(stokBaru.toLocaleString('id-ID', { maximumFractionDigits: 2 }));
        }
    </script>
@endsection
