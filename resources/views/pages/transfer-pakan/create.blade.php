@extends('layout.app')
@section('content')
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Input Transfer Pakan Baru</h5>
                    <a href="{{ route('transfer-pakan.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fa fa-arrow-left"></i> Kembali
                    </a>
                </div>
                <div class="card-body">

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show mb-4">
                            <strong><i class="fa fa-exclamation-triangle"></i> Terdapat kesalahan:</strong>
                            <ul class="mb-0 mt-1 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li class="small">{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger py-2">{{ session('error') }}</div>
                    @endif

                    <form method="POST" action="{{ route('transfer-pakan.store') }}" id="formTransfer">
                        @csrf

                        {{-- Header --}}
                        <div class="card mb-4">
                            <div class="card-header bg-light py-2">
                                <h6 class="fw-bold mb-0">Informasi Transfer</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">No. Transfer <span class="text-danger">*</span></label>
                                        <input type="text" name="no_transfer" class="form-control text-uppercase"
                                            value="{{ old('no_transfer') }}" placeholder="Contoh: TP-2026-001" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">CV <span class="text-danger">*</span></label>
                                        <select name="cv_id" class="form-select" required>
                                            <option value="">-- Pilih CV --</option>
                                            @foreach ($userCvs as $cv)
                                                <option value="{{ $cv->id }}"
                                                    data-omzet="{{ $cv->omzet_tahun }}"
                                                    data-persen="{{ $cv->persen_omzet }}"
                                                    data-melebihi="{{ $cv->melebihi_batas ? '1' : '0' }}"
                                                    {{ old('cv_id', session('active_cv')) == $cv->id ? 'selected' : '' }}
                                                    {{ $cv->melebihi_batas ? 'disabled' : '' }}>
                                                    {{ $cv->nama_cv }}
                                                    @if ($cv->melebihi_batas)
                                                        ⚠️ (Omzet ≥ {{ number_format($batasOmzet / 1000000, 1) }}M)
                                                    @elseif($cv->persen_omzet >= 80)
                                                        ({{ $cv->persen_omzet }}%)
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Tanggal Transfer <span
                                                class="text-danger">*</span></label>
                                        <input type="date" name="tanggal_transfer" class="form-control"
                                            value="{{ old('tanggal_transfer', date('Y-m-d')) }}" required>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Pengirim <span class="text-danger">*</span></label>
                                        <select name="pengirim_id" id="selectPengirim" class="form-select" required>
                                            <option value="">-- Pilih Pengirim --</option>
                                            @foreach ($penerimaList as $p)
                                                <option value="{{ $p['id'] }}" data-nama="{{ $p['nama'] }}"
                                                    data-tujuan-id="{{ $p['tujuan_id'] }}"
                                                    {{ old('pengirim_id') == $p['id'] ? 'selected' : '' }}>
                                                    {{ $p['nama'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                        {{-- nama_pengirim dikirim via hidden, diisi dari select --}}
                                        <input type="hidden" name="nama_pengirim" id="inputNamaPengirim"
                                            value="{{ old('nama_pengirim') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Tujuan (Umum)</label>
                                        <select name="tujuan_id" id="selectTujuanHeader" class="form-select">
                                            <option value="">-- Pilih Tujuan --</option>
                                            @foreach ($tujuans as $t)
                                                <option value="{{ $t->id }}"
                                                    {{ old('tujuan_id') == $t->id ? 'selected' : '' }}>
                                                    {{ $t->nama }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Catatan</label>
                                        <input type="text" name="catatan" class="form-control"
                                            value="{{ old('catatan') }}" placeholder="Opsional">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Daftar Kendaraan --}}
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0">Daftar Kendaraan</h6>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnTambahKendaraan">
                                    <i class="fa fa-plus"></i> Tambah Kendaraan
                                </button>
                            </div>
                            <div id="listKendaraan"></div>
                        </div>

                        <div class="mt-4">
                            <button class="btn btn-primary" type="submit">
                                <i class="fa fa-save"></i> Simpan Transfer
                            </button>
                            <a href="{{ route('transfer-pakan.index') }}" class="btn btn-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        var penerimaList = @json($penerimaList);
        var kodePakanList = @json($kodePakans->map(fn($kp) => ['id' => $kp->id, 'kode' => $kp->kode, 'nama' => $kp->nama]));
        var kendaraanCount = 0;

        // Pengirim auto-fill nama + tujuan
        $('#selectPengirim').on('change', function() {
            var selected = $(this).find('option:selected');
            if ($(this).val()) {
                $('#inputNamaPengirim').val(selected.data('nama'));
                var tujuanId = selected.data('tujuan-id');
                if (tujuanId) {
                    $('#selectTujuanHeader').val(tujuanId);
                }
            } else {
                $('#inputNamaPengirim').val('');
            }
        });

        // Tambah Kendaraan
        $('#btnTambahKendaraan').on('click', function() {
            tambahKendaraan();
        });

        function tambahKendaraan() {
            var ki = kendaraanCount++;
            var html = `
        <div class="card mb-3 item-kendaraan border-primary" data-ki="${ki}">
            <div class="card-header bg-primary bg-opacity-10 py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0">🚚 Kendaraan #${ki + 1}</h6>
                <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-kendaraan">
                    <i class="fa fa-times"></i> Hapus
                </button>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small">No. Polisi <span class="text-danger">*</span></label>
                        <input type="text" name="kendaraans[${ki}][no_polisi]" class="form-control text-uppercase" placeholder="B 1234 XY" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Nama Sopir</label>
                        <input type="text" name="kendaraans[${ki}][nama_sopir]" class="form-control" placeholder="Opsional">
                    </div>
                </div>
                <div class="border rounded p-3 bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-semibold small text-primary">Daftar Penerima</span>
                        <button type="button" class="btn btn-sm btn-outline-primary btn-tambah-penerima" data-ki="${ki}">
                            <i class="fa fa-plus"></i> Tambah Penerima
                        </button>
                    </div>
                    <div class="list-penerima" data-ki="${ki}"></div>
                </div>
            </div>
        </div>
    `;
            $('#listKendaraan').append(html);
            tambahPenerima(ki);
            updateHapusKendaraan();
        }

        function updateHapusKendaraan() {
            var cards = $('.item-kendaraan');
            cards.find('.btn-hapus-kendaraan').prop('disabled', cards.length === 1);
        }

        $(document).on('click', '.btn-hapus-kendaraan', function() {
            $(this).closest('.item-kendaraan').remove();
            updateHapusKendaraan();
        });

        // Tambah Penerima
        $(document).on('click', '.btn-tambah-penerima', function() {
            tambahPenerima($(this).data('ki'));
        });

        var penerimaCount = {};

        function tambahPenerima(ki) {
            if (!penerimaCount[ki]) penerimaCount[ki] = 0;
            var pi = penerimaCount[ki]++;

            var penerimaOptions = penerimaList.map(function(p) {
                return `<option value="${p.id}" data-nama="${p.nama}" data-tujuan-id="${p.tujuan_id}" data-tujuan-nama="${p.tujuan_nama}">${p.nama}</option>`;
            }).join('');

            var kodePakanOptions = kodePakanList.map(function(kp) {
                return `<option value="${kp.id}">${kp.kode}${kp.nama ? ' - ' + kp.nama : ''}</option>`;
            }).join('');

            var html = `
        <div class="card mb-2 item-penerima border-secondary">
            <div class="card-header bg-secondary bg-opacity-10 py-1 d-flex justify-content-between align-items-center">
                <span class="small fw-semibold">👤 Penerima #${pi + 1}</span>
                <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-penerima">
                    <i class="fa fa-times"></i> Hapus
                </button>
            </div>
            <div class="card-body py-2">
                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <label class="form-label small">Nama Penerima <span class="text-danger">*</span></label>
                        <input type="hidden" name="kendaraans[${ki}][penerimas][${pi}][penerima_id]" class="input-penerima-id">
                        <select class="form-select form-select-sm select-penerima">
                            <option value="">-- Pilih Penerima --</option>
                            ${penerimaOptions}
                        </select>
                        <input type="hidden" name="kendaraans[${ki}][penerimas][${pi}][nama_penerima]"
                            class="form-control form-control-sm mt-1 input-nama-penerima"
                            placeholder="Nama penerima" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Tujuan</label>
                        <input type="hidden" name="kendaraans[${ki}][penerimas][${pi}][tujuan_id]" class="input-tujuan-id">
                        <input type="text" class="form-control form-control-sm bg-light input-tujuan-display" placeholder="Otomatis dari penerima" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">No. Surat Jalan</label>
                        <input type="text" name="kendaraans[${ki}][penerimas][${pi}][no_surat_jalan]"
                            class="form-control form-control-sm" placeholder="Opsional">
                    </div>
                </div>
                {{-- Pakan --}}
                <div class="border rounded p-2 bg-white mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small text-muted fw-semibold">Pakan <span class="text-danger">*</span></span>
                        <button type="button" class="btn btn-sm btn-outline-success btn-tambah-pakan" data-ki="${ki}" data-pi="${pi}">
                            <i class="fa fa-plus"></i> Tambah Pakan
                        </button>
                    </div>
                    <div class="list-pakan" data-ki="${ki}" data-pi="${pi}">
                        <div class="row g-2 align-items-end mb-2 item-pakan">
                            <div class="col-md-3">
                                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Kode Pakan <span class="text-danger">*</span></label>
                                <select name="kendaraans[${ki}][penerimas][${pi}][pakans][0][kode_pakan_id]" class="form-select form-select-sm" required>
                                    <option value="">-- Pilih Pakan --</option>
                                    ${kodePakanOptions}
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Jumlah (Kg) <span class="text-danger">*</span></label>
                                <input type="number" name="kendaraans[${ki}][penerimas][${pi}][pakans][0][jumlah_kg]"
                                    class="form-control form-control-sm input-kg" placeholder="Kg" step="0.01" min="0.01" required>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Karung</label>
                                <input type="number" name="kendaraans[${ki}][penerimas][${pi}][pakans][0][jumlah_karung]"
                                    class="form-control form-control-sm input-karung" placeholder="Karung" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Ongkos OA/kg</label>
                                <input type="number" name="kendaraans[${ki}][penerimas][${pi}][pakans][0][ongkos_oa]"
                                    class="form-control form-control-sm" placeholder="0" step="0.01" min="0" value="0">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Harga PT Sum/kg</label>
                                <input type="number" name="kendaraans[${ki}][penerimas][${pi}][pakans][0][harga_pt_sum]"
                                    class="form-control form-control-sm" placeholder="0" step="0.01" min="0" value="0">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label form-label-sm mb-1 text-muted d-block" style="font-size:11px;">&nbsp;</label>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-pakan w-100" disabled>
                                   Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tim Bongkar --}}
                <div class="border rounded p-2 bg-white">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small text-muted fw-semibold">Tim Bongkar <span class="text-muted">(Opsional)</span></span>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-tambah-tim" data-ki="${ki}" data-pi="${pi}">
                            <i class="fa fa-plus"></i> Tambah Tim
                        </button>
                    </div>
                    <div class="list-tim" data-ki="${ki}" data-pi="${pi}"></div>
                </div>
            </div>
        </div>
    `;

            $(`.list-penerima[data-ki="${ki}"]`).append(html);
            updateHapusPenerima(ki);
        }

        function updateHapusPenerima(ki) {
            var cards = $(`.list-penerima[data-ki="${ki}"] .item-penerima`);
            cards.find('.btn-hapus-penerima').prop('disabled', cards.length === 1);
        }

        $(document).on('click', '.btn-hapus-penerima', function() {
            var card = $(this).closest('.item-kendaraan');
            var ki = card.data('ki');
            if ($(`.list-penerima[data-ki="${ki}"] .item-penerima`).length > 1) {
                $(this).closest('.item-penerima').remove();
                updateHapusPenerima(ki);
            }
        });

        // Select penerima → auto fill
        $(document).on('change', '.select-penerima', function() {
            var card = $(this).closest('.item-penerima');
            var opt = $(this).find('option:selected');
            card.find('.input-penerima-id').val($(this).val());
            card.find('.input-nama-penerima').val(opt.data('nama') || '');
            card.find('.input-tujuan-id').val(opt.data('tujuan-id') || '');
            card.find('.input-tujuan-display').val(opt.data('tujuan-nama') || '');
        });

        // Auto karung dari kg
        $(document).on('input', '.input-kg', function() {
            var kg = parseFloat($(this).val()) || 0;
            $(this).closest('.item-pakan').find('.input-karung').val(kg > 0 ? Math.ceil(kg / 50) : '');
        });

        // Tambah Pakan
        var pakanCount = {};
        $(document).on('click', '.btn-tambah-pakan', function() {
            var ki = $(this).data('ki');
            var pi = $(this).data('pi');
            var key = ki + '_' + pi;
            if (!pakanCount[key]) pakanCount[key] = 1;
            var idx = pakanCount[key]++;

            var kodePakanOptions = kodePakanList.map(function(kp) {
                return `<option value="${kp.id}">${kp.kode}${kp.nama ? ' - ' + kp.nama : ''}</option>`;
            }).join('');

            var html = `
        <div class="row g-2 align-items-end mb-2 item-pakan">
            <div class="col-md-3">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Kode Pakan <span class="text-danger">*</span></label>
                <select name="kendaraans[${ki}][penerimas][${pi}][pakans][${idx}][kode_pakan_id]" class="form-select form-select-sm" required>
                    <option value="">-- Pilih Pakan --</option>
                    ${kodePakanOptions}
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Jumlah (Kg) <span class="text-danger">*</span></label>
                <input type="number" name="kendaraans[${ki}][penerimas][${pi}][pakans][${idx}][jumlah_kg]"
                    class="form-control form-control-sm input-kg" placeholder="Kg" step="0.01" min="0.01" required>
            </div>
            <div class="col-md-1">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Karung</label>
                <input type="number" name="kendaraans[${ki}][penerimas][${pi}][pakans][${idx}][jumlah_karung]"
                    class="form-control form-control-sm input-karung" placeholder="Karung" readonly>
            </div>
            <div class="col-md-2">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Ongkos OA/kg</label>
                <input type="number" name="kendaraans[${ki}][penerimas][${pi}][pakans][${idx}][ongkos_oa]"
                    class="form-control form-control-sm" placeholder="0" step="0.01" min="0" value="0">
            </div>
            <div class="col-md-2">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Harga PT Sum/kg</label>
                <input type="number" name="kendaraans[${ki}][penerimas][${pi}][pakans][${idx}][harga_pt_sum]"
                    class="form-control form-control-sm" placeholder="0" step="0.01" min="0" value="0">
            </div>
            <div class="col-md-1">
                <label class="form-label form-label-sm mb-1 text-muted d-block" style="font-size:11px;">&nbsp;</label>
                <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-pakan w-100">
                    <i class="fa fa-times"></i>
                </button>
            </div>
        </div>
    `;
            $(`.list-pakan[data-ki="${ki}"][data-pi="${pi}"]`).append(html);
            updateHapusPakan(ki, pi);
        });

        function updateHapusPakan(ki, pi) {
            var rows = $(`.list-pakan[data-ki="${ki}"][data-pi="${pi}"] .item-pakan`);
            rows.find('.btn-hapus-pakan').prop('disabled', rows.length === 1);
        }

        $(document).on('click', '.btn-hapus-pakan', function() {
            var container = $(this).closest('.list-pakan');
            if (container.find('.item-pakan').length > 1) {
                $(this).closest('.item-pakan').remove();
            }
        });

        // Tambah Tim
        var timCount = {};
        $(document).on('click', '.btn-tambah-tim', function() {
            var ki = $(this).data('ki');
            var pi = $(this).data('pi');
            var key = ki + '_' + pi;
            if (!timCount[key]) timCount[key] = 0;
            var idx = timCount[key]++;

            var html = `
        <div class="row g-2 align-items-end mb-2 item-tim">
            <div class="col-md-3">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Nama Tim <span class="text-danger">*</span></label>
                <input type="text" name="kendaraans[${ki}][penerimas][${pi}][tims][${idx}][nama_tim]"
                    class="form-control form-control-sm" placeholder="Nama tim" required>
            </div>
            <div class="col-md-2">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Jumlah (Kg)</label>
                <input type="number" name="kendaraans[${ki}][penerimas][${pi}][tims][${idx}][jumlah_kg]"
                    class="form-control form-control-sm input-berat-tim" placeholder="Kg" step="0.01" min="0">
            </div>
            <div class="col-md-1">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Karung</label>
                <input type="number" name="kendaraans[${ki}][penerimas][${pi}][tims][${idx}][jumlah_karung]"
                    class="form-control form-control-sm input-karung-tim" placeholder="Karung" readonly>
            </div>
            <div class="col-md-2">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Upah/kg</label>
                <input type="number" name="kendaraans[${ki}][penerimas][${pi}][tims][${idx}][upah_per_kg]"
                    class="form-control form-control-sm" placeholder="0" step="0.01" min="0" value="45">
            </div>
            <div class="col-md-2">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Keterangan</label>
                <input type="text" name="kendaraans[${ki}][penerimas][${pi}][tims][${idx}][keterangan]"
                    class="form-control form-control-sm" placeholder="Opsional">
            </div>
            <div class="col-md-2">
                <label class="form-label form-label-sm mb-1 text-muted d-block" style="font-size:11px;">&nbsp;</label>
                <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-tim w-100">
                    <i class="fa fa-times"></i> Hapus
                </button>
            </div>
        </div>
    `;
            $(`.list-tim[data-ki="${ki}"][data-pi="${pi}"]`).append(html);
        });

        $(document).on('input', '.input-berat-tim', function() {
            var berat = parseFloat($(this).val()) || 0;
            $(this).closest('.item-tim').find('.input-karung-tim').val(berat > 0 ? Math.ceil(berat / 50) : '');
        });

        $(document).on('click', '.btn-hapus-tim', function() {
            $(this).closest('.item-tim').remove();
        });

        // Init
        tambahKendaraan();
    </script>
@endsection
