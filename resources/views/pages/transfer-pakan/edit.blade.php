@extends('layout.app')
@section('content')
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Edit Transfer Pakan &mdash; {{ $header->no_transfer }}</h5>
                    <a href="{{ route('transfer-pakan.show', encrypt($header->id)) }}" class="btn btn-sm btn-secondary">
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

                    <form method="POST" action="{{ route('transfer-pakan.update', encrypt($header->id)) }}"
                        id="formTransfer">
                        @csrf
                        @method('PUT')

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
                                            value="{{ old('no_transfer', $header->no_transfer) }}" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">CV <span class="text-danger">*</span></label>
                                        <select name="cv_id" class="form-select" required>
                                            <option value="">-- Pilih CV --</option>
                                            @foreach ($cvList as $cv)
                                                <option value="{{ $cv->id }}"
                                                    data-omzet="{{ $cv->omzet_tahun }}"
                                                    data-persen="{{ $cv->persen_omzet }}"
                                                    data-melebihi="{{ $cv->melebihi_batas ? '1' : '0' }}"
                                                    {{ old('cv_id', $header->cv_id) == $cv->id ? 'selected' : '' }}
                                                    {{ $cv->melebihi_batas && $header->cv_id != $cv->id ? 'disabled' : '' }}>
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
                                            value="{{ old('tanggal_transfer', $header->tanggal_transfer->format('Y-m-d')) }}"
                                            required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Pengirim <span class="text-danger">*</span></label>
                                        <select name="pengirim_id" id="selectPengirim" class="form-select" required>
                                            <option value="">-- Pilih Pengirim --</option>
                                            @foreach ($penerimaList as $p)
                                                <option value="{{ $p['id'] }}" data-nama="{{ $p['nama'] }}"
                                                    data-tujuan-id="{{ $p['tujuan_id'] }}"
                                                    {{ old('pengirim_id', $header->pengirim_id) == $p['id'] ? 'selected' : '' }}>
                                                    {{ $p['nama'] }}</option>
                                            @endforeach
                                        </select>
                                        <input type="hidden" name="nama_pengirim" id="inputNamaPengirim"
                                            value="{{ old('nama_pengirim', $header->nama_pengirim) }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Tujuan (Umum)</label>
                                        <select name="tujuan_id" id="selectTujuanHeader" class="form-select">
                                            <option value="">-- Pilih Tujuan --</option>
                                            @foreach ($tujuans as $t)
                                                <option value="{{ $t->id }}"
                                                    {{ old('tujuan_id', $header->tujuan_id) == $t->id ? 'selected' : '' }}>
                                                    {{ $t->nama }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Catatan</label>
                                        <input type="text" name="catatan" class="form-control"
                                            value="{{ old('catatan', $header->catatan) }}" placeholder="Opsional">
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
                                <i class="fa fa-save"></i> Simpan Perubahan
                            </button>
                            <a href="{{ route('transfer-pakan.show', encrypt($header->id)) }}"
                                class="btn btn-secondary">Batal</a>
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
        var penerimaCount = {};
        var pakanCount = {};
        var timCount = {};

        // Pengirim auto-fill
        $('#selectPengirim').on('change', function() {
            var sel = $(this).find('option:selected');
            if ($(this).val()) {
                $('#inputNamaPengirim').val(sel.data('nama'));
                if (sel.data('tujuan-id')) $('#selectTujuanHeader').val(sel.data('tujuan-id'));
            } else {
                $('#inputNamaPengirim').val('');
            }
        });

        $('#btnTambahKendaraan').on('click', function() {
            tambahKendaraan();
        });

        function tambahKendaraan() {
            var ki = kendaraanCount++;
            var html = '<div class="card mb-3 item-kendaraan border-primary" data-ki="' + ki + '">' +
                '<div class="card-header bg-primary bg-opacity-10 py-2 d-flex justify-content-between align-items-center">' +
                '<h6 class="mb-0">\uD83D\uDE9A Kendaraan #' + (ki + 1) + '</h6>' +
                '<button type="button" class="btn btn-sm btn-outline-danger btn-hapus-kendaraan"><i class="fa fa-times"></i> Hapus</button>' +
                '</div><div class="card-body">' +
                '<div class="row g-3 mb-3">' +
                '<div class="col-md-6"><label class="form-label small">No. Polisi <span class="text-danger">*</span></label>' +
                '<input type="text" name="kendaraans[' + ki +
                '][no_polisi]" class="form-control text-uppercase input-no-polisi" placeholder="B 1234 XY" required></div>' +
                '<div class="col-md-6"><label class="form-label small">Nama Sopir</label>' +
                '<input type="text" name="kendaraans[' + ki +
                '][nama_sopir]" class="form-control input-nama-sopir" placeholder="Opsional"></div>' +
                '</div>' +
                '<div class="border rounded p-3 bg-light">' +
                '<div class="d-flex justify-content-between align-items-center mb-2">' +
                '<span class="fw-semibold small text-primary">Daftar Penerima</span>' +
                '<button type="button" class="btn btn-sm btn-outline-primary btn-tambah-penerima" data-ki="' + ki +
                '"><i class="fa fa-plus"></i> Tambah Penerima</button>' +
                '</div><div class="list-penerima" data-ki="' + ki + '"></div>' +
                '</div></div></div>';
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

        $(document).on('click', '.btn-tambah-penerima', function() {
            tambahPenerima($(this).data('ki'));
        });

        function tambahPenerima(ki) {
            if (!penerimaCount[ki]) penerimaCount[ki] = 0;
            var pi = penerimaCount[ki]++;
            var penerimaOpts = penerimaList.map(function(p) {
                return '<option value="' + p.id + '" data-nama="' + p.nama + '" data-tujuan-id="' + p.tujuan_id +
                    '" data-tujuan-nama="' + p.tujuan_nama + '">' +
                    p.nama + '</option>';
            }).join('');
            var html = '<div class="card mb-2 item-penerima border-secondary" data-ki="' + ki +
                '" data-pi="' + pi + '">' +
                '<div class="card-header bg-secondary bg-opacity-10 py-1 d-flex justify-content-between align-items-center">' +
                '<span class="small fw-semibold">\uD83D\uDC64 Penerima #' + (pi + 1) + '</span>' +
                '<button type="button" class="btn btn-sm btn-outline-danger btn-hapus-penerima"><i class="fa fa-times"></i> Hapus</button>' +
                '</div><div class="card-body py-2">' +
                '<div class="row g-2 mb-2">' +
                '<div class="col-md-4"><label class="form-label small">Nama Penerima <span class="text-danger">*</span></label>' +
                '<input type="hidden" name="kendaraans[' + ki + '][penerimas][' + pi +
                '][penerima_id]" class="input-penerima-id">' +
                '<select class="form-select form-select-sm select-penerima"><option value="">-- Pilih Penerima --</option>' +
                penerimaOpts + '</select>' +
                '<input type="hidden" name="kendaraans[' + ki + '][penerimas][' + pi +
                '][nama_penerima]" class="input-nama-penerima"></div>' +
                '<div class="col-md-4"><label class="form-label small">Tujuan</label>' +
                '<input type="hidden" name="kendaraans[' + ki + '][penerimas][' + pi +
                '][tujuan_id]" class="input-tujuan-id">' +
                '<input type="text" class="form-control form-control-sm bg-light input-tujuan-display" placeholder="Otomatis dari penerima" readonly></div>' +
                '<div class="col-md-4"><label class="form-label small">No. Surat Jalan</label>' +
                '<input type="text" name="kendaraans[' + ki + '][penerimas][' + pi +
                '][no_surat_jalan]" class="form-control form-control-sm input-no-sj" placeholder="Opsional"></div>' +
                '</div>' +
                '<div class="border rounded p-2 bg-white mb-2">' +
                '<div class="d-flex justify-content-between align-items-center mb-1">' +
                '<span class="small text-muted fw-semibold">Pakan <span class="text-danger">*</span></span>' +
                '<button type="button" class="btn btn-sm btn-outline-success btn-tambah-pakan" data-ki="' + ki +
                '" data-pi="' + pi + '"><i class="fa fa-plus"></i> Tambah Pakan</button>' +
                '</div><div class="list-pakan" data-ki="' + ki + '" data-pi="' + pi + '"></div>' +
                '</div>' +
                '<div class="border rounded p-2 bg-white">' +
                '<div class="d-flex justify-content-between align-items-center mb-1">' +
                '<span class="small text-muted fw-semibold">Tim Bongkar <span class="text-muted">(Opsional)</span></span>' +
                '<button type="button" class="btn btn-sm btn-outline-secondary btn-tambah-tim" data-ki="' + ki +
                '" data-pi="' + pi + '"><i class="fa fa-plus"></i> Tambah Tim</button>' +
                '</div><div class="list-tim" data-ki="' + ki + '" data-pi="' + pi + '"></div>' +
                '</div></div></div>';
            $('.list-penerima[data-ki="' + ki + '"]').append(html);
            addPakanRow(ki, pi, null);
            updateHapusPenerima(ki);
        }

        function updateHapusPenerima(ki) {
            var cards = $('.list-penerima[data-ki="' + ki + '"] .item-penerima');
            cards.find('.btn-hapus-penerima').prop('disabled', cards.length === 1);
        }

        $(document).on('click', '.btn-hapus-penerima', function() {
            var ki = $(this).closest('.item-kendaraan').data('ki');
            if ($('.list-penerima[data-ki="' + ki + '"] .item-penerima').length > 1) {
                $(this).closest('.item-penerima').remove();
                updateHapusPenerima(ki);
            }
        });

        $(document).on('change', '.select-penerima', function() {
            var card = $(this).closest('.item-penerima');
            var opt = $(this).find('option:selected');
            card.find('.input-penerima-id').val($(this).val());
            card.find('.input-nama-penerima').val(opt.data('nama') || '');
            card.find('.input-tujuan-id').val(opt.data('tujuan-id') || '');
            card.find('.input-tujuan-display').val(opt.data('tujuan-nama') || '');
        });

        $(document).on('input', '.input-kg', function() {
            var kg = parseFloat($(this).val()) || 0;
            $(this).closest('.item-pakan').find('.input-karung').val(kg > 0 ? Math.ceil(kg / 50) : '');
        });

        function addPakanRow(ki, pi, data) {
            if (!pakanCount[ki + '_' + pi]) pakanCount[ki + '_' + pi] = 0;
            var idx = pakanCount[ki + '_' + pi]++;
            var opts = kodePakanList.map(function(kp) {
                return '<option value="' + kp.id + '">' + kp.kode + (kp.nama ? ' - ' + kp.nama : '') + '</option>';
            }).join('');
            var jKg = data ? (data.jumlah_kg || '') : '';
            var jKrg = data ? (data.jumlah_karung || '') : '';
            var oa = data ? (data.ongkos_oa != null ? data.ongkos_oa : 0) : 0;
            var pts = data ? (data.harga_pt_sum != null ? data.harga_pt_sum : 0) : 0;
            var html = '<div class="row g-2 align-items-end mb-2 item-pakan">' +
                '<div class="col-md-3"><label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Kode Pakan <span class="text-danger">*</span></label>' +
                '<select name="kendaraans[' + ki + '][penerimas][' + pi + '][pakans][' + idx +
                '][kode_pakan_id]" class="form-select form-select-sm" required>' +
                '<option value="">-- Pilih Pakan --</option>' + opts + '</select></div>' +
                '<div class="col-md-2"><label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Jumlah (Kg) <span class="text-danger">*</span></label>' +
                '<input type="number" name="kendaraans[' + ki + '][penerimas][' + pi + '][pakans][' + idx +
                '][jumlah_kg]" class="form-control form-control-sm input-kg" placeholder="Kg" step="0.01" min="0.01" required value="' +
                jKg + '"></div>' +
                '<div class="col-md-1"><label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Karung</label>' +
                '<input type="number" name="kendaraans[' + ki + '][penerimas][' + pi + '][pakans][' + idx +
                '][jumlah_karung]" class="form-control form-control-sm input-karung" placeholder="Karung" readonly value="' +
                jKrg + '"></div>' +
                '<div class="col-md-2"><label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Ongkos OA/kg</label>' +
                '<input type="number" name="kendaraans[' + ki + '][penerimas][' + pi + '][pakans][' + idx +
                '][ongkos_oa]" class="form-control form-control-sm" step="0.01" min="0" value="' + oa + '"></div>' +
                '<div class="col-md-2"><label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Harga PT Sum/kg</label>' +
                '<input type="number" name="kendaraans[' + ki + '][penerimas][' + pi + '][pakans][' + idx +
                '][harga_pt_sum]" class="form-control form-control-sm" step="0.01" min="0" value="' + pts + '"></div>' +
                '<div class="col-md-1"><label class="form-label form-label-sm mb-1 text-muted d-block" style="font-size:11px;">&nbsp;</label>' +
                '<button type="button" class="btn btn-sm btn-outline-danger btn-hapus-pakan w-100"><i class="fa fa-times"></i></button></div>' +
                '</div>';
            var $row = $(html);
            $('.list-pakan[data-ki="' + ki + '"][data-pi="' + pi + '"]').append($row);
            if (data && data.kode_pakan_id) $row.find('select').val(data.kode_pakan_id);
            updateHapusPakan(ki, pi);
        }

        $(document).on('click', '.btn-tambah-pakan', function() {
            addPakanRow($(this).data('ki'), $(this).data('pi'), null);
        });

        function updateHapusPakan(ki, pi) {
            var rows = $('.list-pakan[data-ki="' + ki + '"][data-pi="' + pi + '"] .item-pakan');
            rows.find('.btn-hapus-pakan').prop('disabled', rows.length === 1);
        }

        $(document).on('click', '.btn-hapus-pakan', function() {
            var container = $(this).closest('.list-pakan');
            if (container.find('.item-pakan').length > 1) $(this).closest('.item-pakan').remove();
        });

        function addTimRow(ki, pi, data) {
            if (!timCount[ki + '_' + pi]) timCount[ki + '_' + pi] = 0;
            var idx = timCount[ki + '_' + pi]++;
            var nTim = data ? (data.nama_tim || '') : '';
            var jKg = data ? (data.jumlah_kg || '') : '';
            var jKrg = data ? (data.jumlah_karung || '') : '';
            var upah = data ? (data.upah_per_kg || 45) : 45;
            var ket = data ? (data.keterangan || '') : '';
            var html = '<div class="row g-2 align-items-end mb-2 item-tim">' +
                '<div class="col-md-3"><label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Nama Tim <span class="text-danger">*</span></label>' +
                '<input type="text" name="kendaraans[' + ki + '][penerimas][' + pi + '][tims][' + idx +
                '][nama_tim]" class="form-control form-control-sm" placeholder="Nama tim" required value="' + nTim +
                '"></div>' +
                '<div class="col-md-2"><label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Jumlah (Kg)</label>' +
                '<input type="number" name="kendaraans[' + ki + '][penerimas][' + pi + '][tims][' + idx +
                '][jumlah_kg]" class="form-control form-control-sm input-berat-tim" step="0.01" min="0" value="' + jKg +
                '"></div>' +
                '<div class="col-md-1"><label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Karung</label>' +
                '<input type="number" name="kendaraans[' + ki + '][penerimas][' + pi + '][tims][' + idx +
                '][jumlah_karung]" class="form-control form-control-sm input-karung-tim" readonly value="' + jKrg +
                '"></div>' +
                '<div class="col-md-2"><label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Upah/kg</label>' +
                '<input type="number" name="kendaraans[' + ki + '][penerimas][' + pi + '][tims][' + idx +
                '][upah_per_kg]" class="form-control form-control-sm" step="0.01" min="0" value="' + upah + '"></div>' +
                '<div class="col-md-2"><label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Keterangan</label>' +
                '<input type="text" name="kendaraans[' + ki + '][penerimas][' + pi + '][tims][' + idx +
                '][keterangan]" class="form-control form-control-sm" placeholder="Opsional" value="' + ket + '"></div>' +
                '<div class="col-md-2"><label class="form-label form-label-sm mb-1 text-muted d-block" style="font-size:11px;">&nbsp;</label>' +
                '<button type="button" class="btn btn-sm btn-outline-danger btn-hapus-tim w-100"><i class="fa fa-times"></i> Hapus</button></div>' +
                '</div>';
            $('.list-tim[data-ki="' + ki + '"][data-pi="' + pi + '"]').append(html);
        }

        $(document).on('click', '.btn-tambah-tim', function() {
            addTimRow($(this).data('ki'), $(this).data('pi'), null);
        });

        $(document).on('input', '.input-berat-tim', function() {
            var berat = parseFloat($(this).val()) || 0;
            $(this).closest('.item-tim').find('.input-karung-tim').val(berat > 0 ? Math.ceil(berat / 50) : '');
        });

        $(document).on('click', '.btn-hapus-tim', function() {
            $(this).closest('.item-tim').remove();
        });

        // Init — render data existing
        @php
            $existingData = $header->kendaraans
                ->map(
                    fn($k) => [
                        'no_polisi' => $k->no_polisi,
                        'nama_sopir' => $k->nama_sopir,
                        'penerimas' => $k->penerimas
                            ->map(
                                fn($p) => [
                                    'penerima_id' => $p->penerima_id,
                                    'nama_penerima' => $p->nama_penerima,
                                    'tujuan_id' => $p->tujuan_id,
                                    'tujuan_nama' => $p->tujuan?->nama ?? '',
                                    'no_surat_jalan' => $p->no_surat_jalan,
                                    'pakans' => $p->pakans
                                        ->map(
                                            fn($pk) => [
                                                'kode_pakan_id' => $pk->kode_pakan_id,
                                                'jumlah_kg' => $pk->jumlah_kg,
                                                'jumlah_karung' => $pk->jumlah_karung,
                                                'ongkos_oa' => $pk->ongkos_oa,
                                                'harga_pt_sum' => $pk->harga_pt_sum,
                                            ],
                                        )
                                        ->values(),
                                    'tims' => $p->tims
                                        ->map(
                                            fn($t) => [
                                                'nama_tim' => $t->nama_tim,
                                                'jumlah_kg' => $t->jumlah_kg,
                                                'jumlah_karung' => $t->jumlah_karung,
                                                'upah_per_kg' => $t->upah_per_kg,
                                                'keterangan' => $t->keterangan,
                                            ],
                                        )
                                        ->values(),
                                ],
                            )
                            ->values(),
                    ],
                )
                ->values();
        @endphp
        var existingKendaraans = @json($existingData);

        if (existingKendaraans.length > 0) {
            $.each(existingKendaraans, function(kIdx, kendaraan) {
                tambahKendaraan();
                var ki = kendaraanCount - 1;
                var $kCard = $('#listKendaraan .item-kendaraan').last();

                $kCard.find('.input-no-polisi').val(kendaraan.no_polisi || '');
                $kCard.find('.input-nama-sopir').val(kendaraan.nama_sopir || '');

                // Hapus penerima default kosong & reset counter
                $kCard.find('.list-penerima .item-penerima').remove();
                penerimaCount[ki] = 0;

                $.each(kendaraan.penerimas, function(pIdx, penerima) {
                    tambahPenerima(ki);
                    var pi = penerimaCount[ki] - 1;
                    var $p = $kCard.find('.item-penerima[data-ki="' + ki + '"][data-pi="' + pi + '"]');

                    $p.find('.select-penerima').val(penerima.penerima_id || '');
                    $p.find('.input-penerima-id').val(penerima.penerima_id || '');
                    $p.find('.input-nama-penerima').val(penerima.nama_penerima || '');
                    $p.find('.input-tujuan-id').val(penerima.tujuan_id || '');
                    $p.find('.input-tujuan-display').val(penerima.tujuan_nama || '');
                    $p.find('.input-no-sj').val(penerima.no_surat_jalan || '');

                    // Hapus baris pakan default + reset counter
                    $p.find('.list-pakan .item-pakan').remove();
                    pakanCount[ki + '_' + pi] = 0;

                    if (penerima.pakans.length > 0) {
                        $.each(penerima.pakans, function(pkIdx, pakan) {
                            addPakanRow(ki, pi, pakan);
                        });
                    } else {
                        addPakanRow(ki, pi, null);
                    }

                    $.each(penerima.tims, function(tIdx, tim) {
                        addTimRow(ki, pi, tim);
                    });
                });

                updateHapusPenerima(ki);
            });
        } else {
            tambahKendaraan();
        }
    </script>
@endsection
