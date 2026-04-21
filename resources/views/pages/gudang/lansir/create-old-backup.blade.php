@extends('layout.app')
@section('content')
    @if (session('error'))
        <div class="alert alert-danger py-2">{{ session('error') }}</div>
    @endif

    <form method="post" action="{{ route('gudang.lansir.store') }}" id="formLansir">
        @csrf
        <div class="row g-3">

            {{-- ── Informasi Lansir ── --}}
            <div class="col-12 col-xl-5">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="fa fa-archive"></i> Informasi Lansir</h6>
                        <a href="{{ route('gudang.lansir.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fa fa-arrow-left"></i> Batal
                        </a>
                    </div>
                    <div class="card-body">

                        {{-- Gudang --}}
                        <div class="form-group mb-3">
                            <label class="form-label">Gudang <span class="text-danger">*</span></label>
                            <select name="tujuan_id" id="selectGudang"
                                class="form-select @error('tujuan_id') is-invalid @enderror">
                                <option value="">-- Pilih Gudang --</option>
                                @foreach ($gudangs as $gudang)
                                    <option value="{{ $gudang->id }}"
                                        {{ old('tujuan_id') == $gudang->id ? 'selected' : '' }}>
                                        {{ $gudang->nama }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tujuan_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Kode Pakan --}}
                        <div class="form-group mb-3">
                            <label class="form-label">Kode Pakan <span class="text-danger">*</span></label>
                            <select name="kode_pakan_id" id="selectPakan"
                                class="form-select @error('kode_pakan_id') is-invalid @enderror">
                                <option value="">-- Pilih Kode Pakan --</option>
                                @foreach ($kodePakans as $pakan)
                                    <option value="{{ $pakan->id }}"
                                        {{ old('kode_pakan_id') == $pakan->id ? 'selected' : '' }}>
                                        {{ $pakan->kode }} — {{ $pakan->nama }}
                                    </option>
                                @endforeach
                            </select>
                            @error('kode_pakan_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Saldo tersedia (real-time) --}}
                        <div id="saldoInfo" class="alert alert-info py-2 small mb-3" style="display:none">
                            <i class="fa fa-info-circle"></i>
                            Saldo tersedia: <strong id="saldoKg">-</strong> kg
                            (<span id="saldoKarung">-</span> karung)
                        </div>
                        <div id="saldoLoading" class="text-muted small mb-3" style="display:none">
                            <i class="fa fa-spinner fa-spin"></i> Memuat saldo...
                        </div>

                        {{-- Jumlah kg --}}
                        <div class="form-group mb-3">
                            <label class="form-label">Jumlah (kg) <span class="text-danger">*</span></label>
                            <input type="number" name="jumlah_kg" id="inputJumlahKg"
                                class="form-control @error('jumlah_kg') is-invalid @enderror"
                                value="{{ old('jumlah_kg') }}" placeholder="0.00" step="0.01" min="0.01">
                            @error('jumlah_kg')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Jumlah karung --}}
                        <div class="form-group mb-3">
                            <label class="form-label">Jumlah Karung <span class="text-muted">(Opsional)</span></label>
                            <input type="number" name="jumlah_karung"
                                class="form-control @error('jumlah_karung') is-invalid @enderror"
                                value="{{ old('jumlah_karung') }}" placeholder="0" min="0">
                            @error('jumlah_karung')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Catatan --}}
                        <div class="form-group mb-3">
                            <label class="form-label">Catatan <span class="text-muted">(Opsional)</span></label>
                            <textarea name="catatan" rows="2" class="form-control @error('catatan') is-invalid @enderror"
                                placeholder="Catatan tambahan...">{{ old('catatan') }}</textarea>
                            @error('catatan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>
            </div>

            {{-- ── Mobil & Tim ── --}}
            <div class="col-12 col-xl-7">

                {{-- Mobil Lansir --}}
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">🚛 Mobil Lansir <span class="text-danger">*</span></h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnTambahMobil">
                            <i class="fa fa-plus"></i> Tambah Mobil
                        </button>
                    </div>
                    <div class="card-body pb-2">
                        <div class="row g-1 mb-1 small fw-semibold text-muted d-none d-md-flex">
                            <div class="col-1"></div>
                            <div class="col-2">No. Polisi <span class="text-danger">*</span></div>
                            <div class="col-2">Nama Sopir</div>
                            <div class="col-2">Berat (kg)</div>
                            <div class="col-2">Karung</div>
                            <div class="col-2">Ongkos (Rp)</div>
                        </div>
                        <div id="listMobil">
                            <div class="mobil-row d-flex gap-2 align-items-center mb-2">
                                <span class="badge bg-info mobil-num flex-shrink-0">1</span>
                                <input type="text" name="mobils[0][no_polisi]"
                                    class="form-control form-control-sm text-uppercase" placeholder="No. Polisi *"
                                    style="width:20%" value="{{ old('mobils.0.no_polisi') }}">
                                <input type="text" name="mobils[0][nama_sopir]" class="form-control form-control-sm"
                                    placeholder="Nama sopir" style="width:20%" value="{{ old('mobils.0.nama_sopir') }}">
                                <input type="number" name="mobils[0][berat]"
                                    class="form-control form-control-sm berat-m" placeholder="Berat" step="0.01"
                                    min="0" style="width:15%" value="{{ old('mobils.0.berat') }}">
                                <input type="number" name="mobils[0][jumlah_karung]"
                                    class="form-control form-control-sm" placeholder="Karung" min="0"
                                    style="width:13%" value="{{ old('mobils.0.jumlah_karung') }}">
                                <input type="number" name="mobils[0][ongkos]"
                                    class="form-control form-control-sm ongkos-m" placeholder="Ongkos" step="0.01"
                                    min="0" style="width:16%" value="{{ old('mobils.0.ongkos') }}">
                                <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-mobil flex-shrink-0"
                                    disabled>
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div id="previewMobil" class="small text-muted mt-1" style="display:none">
                            Total ongkos lansir: <strong id="totalOngkos">-</strong>
                        </div>
                    </div>
                </div>

                {{-- Tim Bongkar --}}
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">👷 Tim Bongkar <span class="text-muted small">(Opsional)</span></h6>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnTambahTim">
                            <i class="fa fa-plus"></i> Tambah Tim
                        </button>
                    </div>
                    <div class="card-body pb-2">
                        <div class="row g-1 mb-1 small fw-semibold text-muted d-none d-md-flex">
                            <div class="col-1"></div>
                            <div class="col-4">Nama Tim / Orang</div>
                            <div class="col-2">Berat (kg)</div>
                            <div class="col-2">Karung</div>
                            <div class="col-2">Upah (Rp/kg)</div>
                        </div>
                        <div id="listTim">
                            <div class="tim-row d-flex gap-2 align-items-center mb-2">
                                <span class="badge bg-secondary tim-num flex-shrink-0">1</span>
                                <input type="text" name="tims[0][nama_tim]" class="form-control form-control-sm"
                                    placeholder="Nama tim / orang" style="width:35%"
                                    value="{{ old('tims.0.nama_tim') }}">
                                <input type="number" name="tims[0][berat]" class="form-control form-control-sm berat-t"
                                    placeholder="Berat" step="0.01" min="0" style="width:16%"
                                    value="{{ old('tims.0.berat') }}">
                                <input type="number" name="tims[0][jumlah_karung]" class="form-control form-control-sm"
                                    placeholder="Karung" min="0" style="width:14%"
                                    value="{{ old('tims.0.jumlah_karung') }}">
                                <input type="number" name="tims[0][upah]" class="form-control form-control-sm upah-t"
                                    placeholder="Upah/kg" step="0.01" min="0" style="width:18%"
                                    value="{{ old('tims.0.upah') }}">
                                <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-tim flex-shrink-0"
                                    disabled>
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div id="previewTim" class="small text-muted mt-1" style="display:none">
                            Total upah bongkar: <strong id="totalUpah">-</strong>
                        </div>
                    </div>
                </div>

                <button class="btn btn-primary w-100" type="submit" id="btnSubmit">
                    <i class="fa fa-save"></i> Simpan Lansir Gudang
                </button>
            </div>

        </div>
    </form>

    <script>
        function fmt(n) {
            return 'Rp ' + Math.round(n || 0).toLocaleString('id-ID');
        }

        // ── Saldo real-time ────────────────────────────────────
        var saldoKgAvailable = null;

        function loadSaldo() {
            var gudangId = $('#selectGudang').val();
            var pakanId = $('#selectPakan').val();

            if (!gudangId || !pakanId) {
                $('#saldoInfo').hide();
                saldoKgAvailable = null;
                return;
            }

            $('#saldoLoading').show();
            $('#saldoInfo').hide();

            $.get('/gudang/stok/saldo', {
                tujuan_id: gudangId,
                kode_pakan_id: pakanId
            }, function(res) {
                saldoKgAvailable = parseFloat(res.stok_kg) || 0;
                $('#saldoKg').text(Number(res.stok_kg).toLocaleString('id-ID', {
                    minimumFractionDigits: 2
                }));
                $('#saldoKarung').text(Number(res.stok_karung).toLocaleString('id-ID'));
                $('#saldoInfo').show();
            }).fail(function() {
                saldoKgAvailable = 0;
                $('#saldoKg').text('0.00');
                $('#saldoKarung').text('0');
                $('#saldoInfo').show();
            }).always(function() {
                $('#saldoLoading').hide();
            });
        }

        $('#selectGudang, #selectPakan').on('change', loadSaldo);

        // Trigger on load if old values exist
        if ($('#selectGudang').val() && $('#selectPakan').val()) {
            loadSaldo();
        }

        // ── Mobil preview ──────────────────────────────────────
        function updateMobilPreview() {
            var total = 0;
            $('.mobil-row').each(function() {
                var b = parseFloat($(this).find('.berat-m').val()) || 0;
                var o = parseFloat($(this).find('.ongkos-m').val()) || 0;
                total += b * o;
            });
            if (total > 0) {
                $('#totalOngkos').text(fmt(total));
                $('#previewMobil').show();
            } else {
                $('#previewMobil').hide();
            }
        }
        $(document).on('input', '.berat-m, .ongkos-m', updateMobilPreview);

        // ── Tim preview ────────────────────────────────────────
        function updateTimPreview() {
            var totalBerat = 0;
            $('.mobil-row').each(function() {
                totalBerat += parseFloat($(this).find('.berat-m').val()) || 0;
            });
            var totalUpah = 0;
            $('.tim-row').each(function() {
                totalUpah += totalBerat * (parseFloat($(this).find('.upah-t').val()) || 0);
            });
            if (totalUpah > 0) {
                $('#totalUpah').text(fmt(totalUpah));
                $('#previewTim').show();
            } else {
                $('#previewTim').hide();
            }
        }
        $(document).on('input', '.berat-m, .upah-t', updateTimPreview);

        // ── Tambah / hapus mobil ───────────────────────────────
        $('#btnTambahMobil').on('click', function() {
            var idx = $('.mobil-row').length;
            $('#listMobil').append(`<div class="mobil-row d-flex gap-2 align-items-center mb-2">
                <span class="badge bg-info mobil-num flex-shrink-0">${idx + 1}</span>
                <input type="text" name="mobils[${idx}][no_polisi]" class="form-control form-control-sm text-uppercase" placeholder="No. Polisi *" style="width:20%">
                <input type="text" name="mobils[${idx}][nama_sopir]" class="form-control form-control-sm" placeholder="Nama sopir" style="width:20%">
                <input type="number" name="mobils[${idx}][berat]" class="form-control form-control-sm berat-m" placeholder="Berat" step="0.01" min="0" style="width:15%">
                <input type="number" name="mobils[${idx}][jumlah_karung]" class="form-control form-control-sm" placeholder="Karung" min="0" style="width:13%">
                <input type="number" name="mobils[${idx}][ongkos]" class="form-control form-control-sm ongkos-m" placeholder="Ongkos" step="0.01" min="0" style="width:16%">
                <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-mobil flex-shrink-0"><i class="fa fa-times"></i></button>
            </div>`);
            reindexMobil();
        });

        $(document).on('click', '.btn-hapus-mobil', function() {
            if ($('.mobil-row').length > 1) {
                $(this).closest('.mobil-row').remove();
                reindexMobil();
                updateMobilPreview();
                updateTimPreview();
            }
        });

        function reindexMobil() {
            $('.mobil-row').each(function(i) {
                $(this).find('.mobil-num').text(i + 1);
                $(this).find('[name]').each(function() {
                    $(this).attr('name', $(this).attr('name').replace(/mobils\[\d+\]/, 'mobils[' + i +
                        ']'));
                });
                $(this).find('.btn-hapus-mobil').prop('disabled', $('.mobil-row').length === 1);
            });
        }

        // ── Tambah / hapus tim ─────────────────────────────────
        $('#btnTambahTim').on('click', function() {
            var idx = $('.tim-row').length;
            $('#listTim').append(`<div class="tim-row d-flex gap-2 align-items-center mb-2">
                <span class="badge bg-secondary tim-num flex-shrink-0">${idx + 1}</span>
                <input type="text" name="tims[${idx}][nama_tim]" class="form-control form-control-sm" placeholder="Nama tim / orang" style="width:35%">
                <input type="number" name="tims[${idx}][berat]" class="form-control form-control-sm berat-t" placeholder="Berat" step="0.01" min="0" style="width:16%">
                <input type="number" name="tims[${idx}][jumlah_karung]" class="form-control form-control-sm" placeholder="Karung" min="0" style="width:14%">
                <input type="number" name="tims[${idx}][upah]" class="form-control form-control-sm upah-t" placeholder="Upah/kg" step="0.01" min="0" style="width:18%">
                <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-tim flex-shrink-0"><i class="fa fa-times"></i></button>
            </div>`);
            reindexTim();
        });

        $(document).on('click', '.btn-hapus-tim', function() {
            if ($('.tim-row').length > 1) {
                $(this).closest('.tim-row').remove();
                reindexTim();
                updateTimPreview();
            }
        });

        function reindexTim() {
            $('.tim-row').each(function(i) {
                $(this).find('.tim-num').text(i + 1);
                $(this).find('[name]').each(function() {
                    $(this).attr('name', $(this).attr('name').replace(/tims\[\d+\]/, 'tims[' + i + ']'));
                });
                $(this).find('.btn-hapus-tim').prop('disabled', $('.tim-row').length === 1);
            });
        }

        // ── Validasi frontend ──────────────────────────────────
        $('#formLansir').on('submit', function(e) {
            var jumlahKg = parseFloat($('#inputJumlahKg').val()) || 0;

            if (saldoKgAvailable !== null && jumlahKg > saldoKgAvailable) {
                e.preventDefault();
                alertify.error('Jumlah kg melebihi saldo tersedia (' +
                    Number(saldoKgAvailable).toLocaleString('id-ID', {
                        minimumFractionDigits: 2
                    }) + ' kg).');
                return;
            }

            // Pastikan minimal satu no polisi terisi
            var adaPolisi = false;
            $('[name^="mobils"][name$="[no_polisi]"]').each(function() {
                if ($(this).val().trim()) {
                    adaPolisi = true;
                    return false;
                }
            });
            if (!adaPolisi) {
                e.preventDefault();
                alertify.error('Minimal satu No. Polisi mobil lansir wajib diisi.');
            }
        });
    </script>
@endsection
