@extends('layout.app')

@push('css')
    <style>
        /* Select2 di dalam template penerima */
        .select2-container--bootstrap-5 .select2-selection {
            font-size: 0.875rem;
            min-height: calc(1.5em + 0.5rem + 2px);
        }

        .select2-container {
            width: 100% !important;
        }
    </style>
@endpush

@section('content')
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Edit PO — <span class="text-primary">{{ $po->no_po }}</span></h5>
                        <small class="text-muted">{{ $po->tanggal_po->format('d M Y') }}</small>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        @if ($po->isLocked())
                            <span class="badge bg-success fs-6"><i class="fa fa-lock"></i> Terkunci</span>
                            <button type="button" class="btn btn-sm btn-warning"
                                onclick="confirmUnlock({{ $po->id }})">
                                <i class="fa fa-unlock"></i> Buka Kunci
                            </button>
                        @else
                            <span class="badge bg-warning text-dark">Draft</span>
                            <button type="button" class="btn btn-sm btn-success"
                                onclick="confirmLock({{ $po->id }})">
                                <i class="fa fa-lock"></i> Kunci PO
                            </button>
                        @endif
                        <a href="{{ route('purchase-order.show', encrypt($po->id)) }}"
                            class="btn btn-sm btn-info text-white">
                            <i class="fa fa-eye"></i> Detail
                        </a>
                        <a href="{{ route('purchase-order.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fa fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show py-2">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show py-2">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- Error summary dari validasi server --}}
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                            <strong><i class="fa fa-exclamation-triangle"></i> Terdapat kesalahan pada form:</strong>
                            <ul class="mb-0 mt-1 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li class="small">{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if ($po->isLocked())
                        <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
                            <i class="fa fa-lock fa-lg"></i>
                            <div>
                                <strong>PO sudah dikunci.</strong>
                                Semua field hanya bisa dilihat. Klik <strong>Buka Kunci</strong> untuk mengaktifkan kembali
                                mode edit.
                            </div>
                        </div>
                    @endif

                    <form method="post" action="{{ route('purchase-order.update', $po->id) }}" id="formPO">
                        @csrf
                        @method('PUT')

                        {{-- Header PO (read-only) --}}
                        <div class="card mb-4">
                            <div class="card-header bg-light py-2">
                                <h6 class="fw-bold mb-0">Informasi PO</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">No. PO dari PT SUM</label>
                                        <input type="text" class="form-control" name="no_po" value="{{ $po->no_po }}"
                                            >
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Tanggal PO</label>
                                        <input type="date" name="tanggal_po" class="form-control"
                                           value="{{ \Carbon\Carbon::parse($po->tanggal_po)->format('Y-m-d') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">CV <span class="text-muted">(Opsional)</span></label>
                                        <select name="cv_id" id="selectCv"
                                            class="form-select @error('cv_id') is-invalid @enderror">
                                            <option value="">-- Pilih CV --</option>
                                            @foreach ($cvList as $cv)
                                                <option value="{{ $cv->id }}" data-omzet="{{ $cv->omzet_tahun }}"
                                                    data-persen="{{ $cv->persen_omzet }}"
                                                    data-melebihi="{{ $cv->melebihi_batas ? '1' : '0' }}"
                                                    {{ old('cv_id', $po->cv_id) == $cv->id ? 'selected' : '' }}
                                                    {{ $cv->melebihi_batas && $po->cv_id != $cv->id ? 'disabled' : '' }}>
                                                    {{ $cv->nama_cv }}
                                                    @if ($cv->melebihi_batas)
                                                        ⚠️ (Omzet ≥ {{ number_format($batasOmzet / 1000000, 1) }}M)
                                                    @elseif($cv->persen_omzet >= 80)
                                                        ({{ $cv->persen_omzet }}%)
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('cv_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div id="cvWarning" class="mt-2" style="display:none"></div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Catatan</label>
                                        <input type="text" name="catatan" class="form-control"
                                            value="{{ old('catatan', $po->catatan) }}" placeholder="Opsional">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Daftar Kendaraan --}}
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0">Daftar Kendaraan</h6>
                                @if (!$po->isLocked())
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnTambahKendaraan">
                                        <i class="fa fa-plus"></i> Tambah Kendaraan
                                    </button>
                                @endif
                            </div>
                            @error('kendaraan')
                                <div class="alert alert-danger py-2 small">{{ $message }}</div>
                            @enderror
                            <div id="listKendaraan"></div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            @if (!$po->isLocked())
                                <button class="btn btn-primary" type="submit">
                                    <i class="fa fa-save"></i> Simpan Perubahan
                                </button>
                            @endif
                            <a href="{{ route('purchase-order.show', encrypt($po->id)) }}" class="btn btn-secondary">
                                {{ $po->isLocked() ? 'Kembali' : 'Batal' }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Template: Kendaraan --}}
    <template id="tmplKendaraan">
        <div class="card mb-3 item-kendaraan border-primary">
            <div class="card-header bg-primary bg-opacity-10 py-2 d-flex justify-content-between align-items-center">
                <span class="fw-semibold label-kendaraan-no">Kendaraan #1</span>
                <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-kendaraan" disabled>
                    <i class="fa fa-trash"></i> Hapus Kendaraan
                </button>
            </div>
            <div class="card-body">
                <input type="hidden" name="kendaraan[__KI__][id]" value="">
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label">No. Polisi</label>
                        <input type="text" name="kendaraan[__KI__][no_polisi]"
                            class="form-control text-uppercase input-no-polisi" placeholder="Contoh: B 1234 XY">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Nama Sopir</label>
                        <input type="text" name="kendaraan[__KI__][nama_sopir]" class="form-control"
                            placeholder="Opsional">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Supplier</label>
                        <select name="kendaraan[__KI__][supplier_id]" class="form-select input-supplier">
                            <option value="">-- Pilih Supplier --</option>
                            @foreach ($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->initial }} — {{ $s->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Jenis Kendaraan <span class="text-muted small">(untuk menentukan
                                ongkos)</span></label>
                        <select name="kendaraan[__KI__][jenis_kendaraan]" class="form-select input-jenis-kendaraan"
                            disabled>
                            <option value="">-- Pilih Supplier Dulu --</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Jumlah Muatan (kg) <span class="text-muted small">— total
                                kendaraan</span></label>
                        <input type="number" name="kendaraan[__KI__][jumlah_kg]" class="form-control input-muatan-kg"
                            placeholder="0" step="0.01" min="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Jumlah Muatan (karung)</label>
                        <input type="number" name="kendaraan[__KI__][jumlah_karung]"
                            class="form-control input-muatan-karung" placeholder="0" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tujuan <span class="text-muted small">(untuk menentukan
                                ongkos)</span></label>
                        <select name="kendaraan[__KI__][tujuan_id]" class="form-select input-tujuan-id" disabled>
                            <option value="">-- Pilih Supplier Dulu --</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Ongkos Angkut (Rp/kg) <span class="text-muted small">— per
                                kendaraan</span></label>
                        <input type="number" name="kendaraan[__KI__][ongkos_angkut]"
                            class="form-control input-ongkos-angkut" placeholder="0" step="0.01" min="0"
                            value="0">
                        <small class="text-muted input-oa-info"></small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status Kendaraan</label>
                        <select name="kendaraan[__KI__][status]" class="form-select select-status-kendaraan">
                            <option value="pending">Pending</option>
                            <option value="berangkat">Berangkat</option>
                            <option value="selesai">Selesai</option>
                            <option value="batal">Batal</option>
                        </select>
                    </div>
                </div>

                {{-- Indikator selisih muatan vs penerima --}}
                <div class="alert py-2 small mb-3 indikator-muatan" style="display:none"></div>

                {{-- Section Down Payment (DP) --}}
                <div class="border-top pt-3 mt-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0 text-success"><i class="fa fa-money"></i> Down Payment (DP) Supplier</h6>
                        <small class="text-muted">Opsional</small>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label small">Nominal DP (Rp)</label>
                            <input type="text" class="form-control form-control-sm input-dp-nominal-display"
                                placeholder="0" value="0">
                            <input type="hidden" name="kendaraan[__KI__][dp_nominal]" class="input-dp-nominal"
                                value="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Persentase (%)</label>
                            <input type="number" name="kendaraan[__KI__][dp_persen]"
                                class="form-control form-control-sm input-dp-persen bg-light" placeholder="0"
                                step="0.01" min="0" max="100" readonly>
                            <small class="text-muted">Auto-calculated</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Tanggal Bayar</label>
                            <input type="date" name="kendaraan[__KI__][dp_tanggal]"
                                class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Metode Pembayaran</label>
                            <select name="kendaraan[__KI__][dp_metode]" class="form-select form-select-sm">
                                <option value="">-- Pilih --</option>
                                <option value="transfer">Transfer Bank</option>
                                <option value="tunai">Tunai</option>
                                <option value="giro">Giro</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Keterangan DP</label>
                            <textarea name="kendaraan[__KI__][dp_keterangan]" class="form-control form-control-sm" rows="2"
                                placeholder="Catatan pembayaran DP (opsional)"></textarea>
                        </div>
                        <div class="col-12">
                            <div class="alert alert-info py-2 small mb-0 info-dp" style="display:none;">
                                <strong>Info Tagihan:</strong>
                                <span class="info-dp-text"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Daftar Penerima dalam kendaraan --}}
                <div class="border rounded p-3 bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-semibold small text-primary">Penerima </span>
                        <button type="button" class="btn btn-sm btn-outline-primary btn-tambah-penerima">
                            <i class="fa fa-plus"></i> Tambah Penerima
                        </button>
                    </div>
                    <div class="list-penerima"></div>
                </div>
            </div>
        </div>
    </template>

    {{-- Template: Penerima --}}
    <template id="tmplPenerima">
        <div class="card mb-2 item-penerima border-secondary" data-ongkos-oa="0">
            <div class="card-header bg-secondary bg-opacity-10 py-1 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <span class="small fw-semibold label-penerima-no">Penerima #1</span>
                    <span class="badge bg-light text-secondary border oa-info-badge" style="display:none!important">
                        OA: <span class="oa-info-value">0</span> Rp/kg
                    </span>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-penerima" disabled>
                    <i class="fa fa-times"></i> Hapus
                </button>
            </div>
            <div class="card-body py-2">
                <input type="hidden" name="kendaraan[__KI__][penerima][__PI__][id]" value="">
                <div class="row g-2 mb-2">
                    <div class="col-md-5">
                        <label class="form-label small">Nama Penerima <span class="text-danger">*</span></label>
                        <input type="hidden" name="kendaraan[__KI__][penerima][__PI__][penerima_id]"
                            class="input-penerima-id" value="">
                        <select name="kendaraan[__KI__][penerima][__PI__][nama_penerima]"
                            class="form-select form-select-sm input-nama-penerima">
                            <option value="">-- Pilih Penerima --</option>
                            @foreach ($penerimas as $p)
                                <option value="{{ $p->nama }}" data-penerima-id="{{ $p->id }}"
                                    data-tujuan-id="{{ $p->tujuan_id }}"
                                    data-tujuan-nama="{{ $p->tujuan->nama ?? '' }}">
                                    {{ $p->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Tujuan</label>
                        <input type="hidden" name="kendaraan[__KI__][penerima][__PI__][tujuan_id]"
                            class="input-tujuan-id" value="">
                        <input type="text" class="form-control form-control-sm input-tujuan-display bg-light"
                            placeholder="Otomatis dari penerima" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">No. Surat Jalan</label>
                        <input type="text" name="kendaraan[__KI__][penerima][__PI__][no_surat_jalan]"
                            class="form-control form-control-sm" placeholder="Opsional">
                    </div>
                    <div class="col-md-3" style="display:none">
                        <label class="form-label small">Status Penerima </label>
                        <select name="kendaraan[__KI__][penerima][__PI__][status]"
                            class="form-select form-select-sm select-status-penerima">
                            <option value="pending">Pending</option>
                            <option value="berangkat">Berangkat</option>
                            <option value="tiba">Tiba</option>
                            <option value="selesai">Selesai</option>
                            <option value="batal">Batal</option>
                        </select>
                    </div>
                </div>

                {{-- Pakan --}}
                <div class="border rounded p-2 bg-white">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small text-muted">Pakan <span class="text-danger">*</span></span>
                        <button type="button" class="btn btn-sm btn-outline-success btn-tambah-pakan">
                            <i class="fa fa-plus"></i> Tambah Pakan
                        </button>
                    </div>
                    <div class="list-pakan"></div>
                </div>
            </div>
        </div>
    </template>

    {{-- Template: Pakan --}}
    <template id="tmplPakan">
        <div class="row g-2 align-items-end mb-2 item-pakan">
            <div class="col-md-4">
                <label class="form-label small">Kode Pakan <span class="text-danger">*</span></label>
                <select name="kendaraan[__KI__][penerima][__PI__][pakans][__PKI__][kode_pakan_id]"
                    class="form-select form-select-sm">
                    <option value="">-- Pilih --</option>
                    @foreach ($kodePakans as $kp)
                        <option value="{{ $kp->id }}">{{ $kp->kode }}{{ $kp->nama ? ' - ' . $kp->nama : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Jumlah (kg) <span class="text-danger">*</span></label>
                <input type="number" name="kendaraan[__KI__][penerima][__PI__][pakans][__PKI__][jumlah_kg]"
                    class="form-control form-control-sm input-jumlah-kg" placeholder="0" step="0.01" min="0.01">
            </div>
            <div class="col-md-1">
                <label class="form-label small">Karung <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-sm input-jumlah-karung" placeholder="0" readonly>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Ongkos OA (Rp/kg) <span class="text-danger">*</span></label>
                <input type="number" name="kendaraan[__KI__][penerima][__PI__][pakans][__PKI__][ongkos_oa]"
                    class="form-control form-control-sm" placeholder="0" step="0.01" min="0" value="0">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Harga PT SUM (Rp/kg)</label>
                <input type="number" name="kendaraan[__KI__][penerima][__PI__][pakans][__PKI__][harga_pt_sum]"
                    class="form-control form-control-sm" placeholder="0" step="0.01" min="0" value="0">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-pakan w-100" disabled>
                  Hapus
                </button>
            </div>
        </div>
    </template>

    <script>
        var kendaraanCount = 0;
        var batasOmzet = {{ $batasOmzet }};
        var batasOmzetM = {{ $batasOmzet / 1000000 }};
        var batasOmzetFormatted = 'Rp ' + batasOmzet.toLocaleString('id-ID');

        function fmt(n) {
            return 'Rp ' + Math.round(n).toLocaleString('id-ID');
        }

        $('#selectCv').on('change', function() {
            var opt = $(this).find('option:selected');
            var omzet = parseFloat(opt.data('omzet')) || 0;
            var persen = parseFloat(opt.data('persen')) || 0;
            var melebihi = opt.data('melebihi') == '1';
            var box = $('#cvWarning');
            if (!$(this).val()) {
                box.hide().html('');
                return;
            }
            if (melebihi) {
                box.html(
                    `<div class="alert alert-danger py-2 small mb-0"><i class="fa fa-exclamation-triangle"></i> <strong>Omzet CV ini sudah ${fmt(omzet)} (${persen}%).</strong> Gunakan CV lain.</div>`
                ).show();
            } else if (persen >= 80) {
                box.html(
                    `<div class="alert alert-warning py-2 small mb-0">Omzet <strong>${fmt(omzet)}</strong> (${persen}% dari batas Rp ${batasOmzetM}M). Mendekati batas.</div>`
                ).show();
            } else {
                box.html(
                    `<div class="text-muted small mt-1">Omzet: ${fmt(omzet)} / ${batasOmzetFormatted} (${persen}%)</div>`
                ).show();
            }
        });
        if ($('#selectCv').val()) $('#selectCv').trigger('change');

        // ── Build helpers ─────────────────────────────────────────
        function buildKendaraan(ki) {
            return document.getElementById('tmplKendaraan').innerHTML.replace(/__KI__/g, ki);
        }

        function buildPenerima(ki, pi) {
            return document.getElementById('tmplPenerima').innerHTML.replace(/__KI__/g, ki).replace(/__PI__/g, pi);
        }

        function buildPakan(ki, pi, pki) {
            return document.getElementById('tmplPakan').innerHTML.replace(/__KI__/g, ki).replace(/__PI__/g, pi).replace(
                /__PKI__/g, pki);
        }

        function getKendaraanIndex($card) {
            var name = $card.find('[name^="kendaraan["]').first().attr('name');
            if (!name) return 0;
            var m = name.match(/^kendaraan\[(\d+)\]/);
            return m ? parseInt(m[1]) : 0;
        }

        function getPenerimaIndex($card) {
            var name = $card.find('[name*="][penerima]["]').first().attr('name');
            if (!name) return 0;
            var m = name.match(/\]\[penerima\]\[(\d+)\]/);
            return m ? parseInt(m[1]) : 0;
        }

        // ── Add kendaraan ─────────────────────────────────────────
        function addKendaraan(data) {
            var ki = kendaraanCount++;
            var $card = $(buildKendaraan(ki));
            $('#listKendaraan').append($card);
            updateKendaraanLabels();

            if (data) {
                $card.find('[name="kendaraan[' + ki + '][id]"]').val(data.id || '');
                $card.find('[name="kendaraan[' + ki + '][no_polisi]"]').val(data.no_polisi || '');
                $card.find('[name="kendaraan[' + ki + '][nama_sopir]"]').val(data.nama_sopir || '');
                $card.find('[name="kendaraan[' + ki + '][status]"]').val(data.status || 'pending');
                if (data.jumlah_kg) {
                    $card.find('.input-muatan-kg').val(data.jumlah_kg);
                    updateMuatanKarung($card);
                }

                // Set DP fields
                if (data.dp_nominal) {
                    var dpNominal = parseFloat(data.dp_nominal) || 0;
                    $card.find('.input-dp-nominal').val(dpNominal);
                    if (typeof formatRupiah === 'function') {
                        $card.find('.input-dp-nominal-display').val(formatRupiah(dpNominal));
                    } else {
                        $card.find('.input-dp-nominal-display').val(dpNominal);
                    }
                }
                if (data.dp_tanggal) {
                    $card.find('[name="kendaraan[' + ki + '][dp_tanggal]"]').val(data.dp_tanggal);
                }
                if (data.dp_metode) {
                    $card.find('[name="kendaraan[' + ki + '][dp_metode]"]').val(data.dp_metode);
                }
                if (data.dp_keterangan) {
                    $card.find('[name="kendaraan[' + ki + '][dp_keterangan]"]').val(data.dp_keterangan);
                }

                // Set supplier dan populate jenis kendaraan dropdown
                if (data.supplier_id) {
                    $card.find('[name="kendaraan[' + ki + '][supplier_id]"]').val(data.supplier_id);

                    var $jenisSelect = $card.find('.input-jenis-kendaraan');
                    var $tujuanSelect = $card.find('.input-tujuan-id');
                    $jenisSelect.html('<option value="">-- Loading... --</option>').prop('disabled', true);
                    $tujuanSelect.html('<option value="">-- Loading... --</option>').prop('disabled', true);

                    var savedJenis = data.jenis_kendaraan || '';
                    var savedTujuan = data.tujuan_id || '';
                    $.ajax({
                        url: '{{ route('supplier.get-jenis-kendaraan') }}',
                        method: 'GET',
                        data: {
                            supplier_id: data.supplier_id
                        },
                        success: function(response) {
                            if (response.success) {
                                $card.data('ongkos-map', response.ongkos_map || {});

                                if (response.jenis_kendaraan.length > 0) {
                                    var jenisOpts = '<option value="">-- Pilih Jenis Kendaraan --</option>';
                                    response.jenis_kendaraan.forEach(function(jenis) {
                                        jenisOpts += '<option value="' + jenis + '">' + jenis +
                                            '</option>';
                                    });
                                    $jenisSelect.html(jenisOpts).prop('disabled', false);
                                    if (savedJenis) $jenisSelect.val(savedJenis);
                                } else {
                                    $jenisSelect.html(
                                            '<option value="">-- Tidak Ada Jenis Kendaraan --</option>')
                                        .prop('disabled', true);
                                }

                                if (response.tujuans.length > 0) {
                                    var tujuanOpts = '<option value="">-- Pilih Tujuan --</option>';
                                    response.tujuans.forEach(function(tujuan) {
                                        tujuanOpts += '<option value="' + tujuan.id + '">' + tujuan
                                            .nama + '</option>';
                                    });
                                    $tujuanSelect.html(tujuanOpts).prop('disabled', false);
                                    if (savedTujuan) $tujuanSelect.val(savedTujuan);
                                } else {
                                    $tujuanSelect.html('<option value="">-- Tidak Ada Tujuan --</option>')
                                        .prop('disabled', true);
                                }

                                // Restore ongkos angkut
                                var savedOa = parseFloat(data.ongkos_angkut) || 0;
                                $card.find('.input-ongkos-angkut').val(savedOa);
                                if (savedOa > 0) {
                                    $card.find('.input-oa-info').text('Rp ' + savedOa.toLocaleString('id-ID') +
                                        '/kg');
                                }
                            }
                        },
                        error: function() {
                            $jenisSelect.html('<option value="">-- Error Loading --</option>').prop('disabled',
                                true);
                            $tujuanSelect.html('<option value="">-- Error Loading --</option>').prop('disabled',
                                true);
                        }
                    });
                }

                var penerimas = data.penerima || [];
                for (var pi = 0; pi < penerimas.length; pi++) {
                    addPenerima($card, ki, pi, penerimas[pi]);
                }
            }
        }

        // ── Add penerima ──────────────────────────────────────────
        function addPenerima($kendaraanCard, ki, pi, data) {
            var $card = $(buildPenerima(ki, pi));
            $kendaraanCard.find('.list-penerima').append($card);
            updatePenerimaLabels($kendaraanCard);

            // Init select2 pada dropdown penerima yang baru ditambahkan
            initSelect2Penerima($card.find('.select2-penerima'));

            if (data) {
                $card.find('[name="kendaraan[' + ki + '][penerima][' + pi + '][id]"]').val(data.id || '');
                $card.find('.input-penerima-id').val(data.penerima_id || '');

                // Set dropdown nama penerima
                var $select = $card.find('.input-nama-penerima');
                if (data.nama_penerima) {
                    // Set nilai native select langsung
                    $select.val(data.nama_penerima);

                    // Set tujuan dan penerima_id langsung dari data server
                    $card.find('.input-penerima-id').val(data.penerima_id || '');
                    $card.find('.input-tujuan-id').val(data.tujuan_id || '');
                    var tujuanDisplay = data.tujuan_nama || '';
                    if (!tujuanDisplay && data.tujuan_id) {
                        tujuanDisplay = $select.find('option[data-tujuan-id="' + data.tujuan_id + '"]').first().data(
                            'tujuan-nama') || '';
                    }
                    $card.find('.input-tujuan-display').val(tujuanDisplay);
                }

                $card.find('[name="kendaraan[' + ki + '][penerima][' + pi + '][no_surat_jalan]"]').val(data.no_do || '');
                $card.find('[name="kendaraan[' + ki + '][penerima][' + pi + '][status]"]').val(data.status || 'pending');

                // Set data-ongkos-oa dari pakan pertama (jika ada) untuk auto-fill baris pakan baru
                if (data.pakans && data.pakans.length > 0 && data.pakans[0].ongkos_oa > 0) {
                    var existingOa = parseFloat(data.pakans[0].ongkos_oa) || 0;
                    $card.attr('data-ongkos-oa', existingOa);
                    if (existingOa > 0) {
                        $card.find('.oa-info-value').text(existingOa.toLocaleString('id-ID'));
                        $card.find('.oa-info-badge').show();
                    }
                }

                var pakans = data.pakans || [];
                for (var pki = 0; pki < pakans.length; pki++) {
                    addPakan($card, ki, pi, pki, pakans[pki]);
                }
                updatePakanHapus($card.find('.list-pakan'));
            }
        }

        // ── Add pakan ─────────────────────────────────────────────
        function addPakan($penerimaCard, ki, pi, pki, data) {
            var $row = $(buildPakan(ki, pi, pki));
            $penerimaCard.find('.list-pakan').append($row);

            if (data) {
                $row.find('[name*="[kode_pakan_id]"]').val(data.kode_pakan_id || '');
                $row.find('.input-jumlah-kg').val(data.jumlah_kg || '');
                $row.find('[name*="[ongkos_oa]"]').val(data.ongkos_oa || 0);
                $row.find('[name*="[harga_pt_sum]"]').val(data.harga_pt_sum || 0);
                updateKarung($row.find('.input-jumlah-kg'));
            } else {
                // Baris pakan baru: auto-fill ongkos OA dari data attribute penerima
                var savedOa = parseFloat($penerimaCard.attr('data-ongkos-oa')) || 0;
                if (savedOa > 0) {
                    $row.find('[name*="[ongkos_oa]"]').val(savedOa);
                }
            }
        }

        // ── Label & button updates ────────────────────────────────
        function updateKendaraanLabels() {
            var $cards = $('#listKendaraan .item-kendaraan');
            $cards.each(function(i) {
                $(this).find('.label-kendaraan-no').text('Kendaraan #' + (i + 1));
                $(this).find('.btn-hapus-kendaraan').prop('disabled', $cards.length === 1);
            });
        }

        function updatePenerimaLabels($kendaraanCard) {
            var $cards = $kendaraanCard.find('.list-penerima > .item-penerima');
            $cards.each(function(i) {
                $(this).find('.label-penerima-no').text('Penerima #' + (i + 1));
                $(this).find('.btn-hapus-penerima').prop('disabled', $cards.length === 1);
            });
        }

        function updatePakanHapus($listPakan) {
            var $rows = $listPakan.find('.item-pakan');
            $rows.find('.btn-hapus-pakan').prop('disabled', $rows.length === 1);
        }

        function updateKarung($input) {
            var kg = parseFloat($input.val()) || 0;
            $input.closest('.item-pakan').find('.input-jumlah-karung').val(kg > 0 ? Math.ceil(kg / 50) : '');
            var $kCard = $input.closest('.item-kendaraan');
            if ($kCard.length) updateIndikatorMuatan($kCard);
        }

        // ── Auto-calc karung muatan kendaraan ─────────────────────
        function updateMuatanKarung($kCard) {
            var kg = parseFloat($kCard.find('.input-muatan-kg').val()) || 0;
            $kCard.find('.input-muatan-karung').val(kg > 0 ? Math.ceil(kg / 50) : '');
            updateIndikatorMuatan($kCard);
        }

        // ── Indikator selisih muatan vs total pakan penerima ──────
        function updateIndikatorMuatan($kCard) {
            var muatan = parseFloat($kCard.find('.input-muatan-kg').val()) || 0;
            if (muatan <= 0) {
                $kCard.find('.indikator-muatan').hide();
                return;
            }

            var totalPenerima = 0;
            $kCard.find('.input-jumlah-kg').each(function() {
                totalPenerima += parseFloat($(this).val()) || 0;
            });

            var selisih = muatan - totalPenerima;
            var $ind = $kCard.find('.indikator-muatan');

            if (Math.abs(selisih) < 0.01) {
                $ind.removeClass('alert-warning alert-danger').addClass('alert-success')
                    .html('<i class="fa fa-check-circle"></i> <strong>Cocok!</strong> Total pakan penerima (' +
                        totalPenerima.toLocaleString('id-ID') + ' kg) = muatan kendaraan.').show();
            } else if (selisih > 0) {
                $ind.removeClass('alert-success alert-danger').addClass('alert-warning')
                    .html('<i class="fa fa-exclamation-triangle"></i> Sisa muatan belum dialokasikan: <strong>' +
                        selisih.toLocaleString('id-ID') + ' kg</strong> (muatan ' + muatan.toLocaleString('id-ID') +
                        ' kg, penerima ' + totalPenerima.toLocaleString('id-ID') + ' kg)').show();
            } else {
                $ind.removeClass('alert-success alert-warning').addClass('alert-danger')
                    .html(
                        '<i class="fa fa-times-circle"></i> Total pakan penerima <strong>melebihi</strong> muatan: kelebihan <strong>' +
                        Math.abs(selisih).toLocaleString('id-ID') + ' kg</strong>').show();
            }
        }

        // ── Events ────────────────────────────────────────────────
        $('#btnTambahKendaraan').on('click', function() {
            addKendaraan(null);
        });

        $(document).on('click', '.btn-hapus-kendaraan', function() {
            if ($('#listKendaraan .item-kendaraan').length > 1) {
                $(this).closest('.item-kendaraan').remove();
                updateKendaraanLabels();
            }
        });

        $(document).on('click', '.btn-tambah-penerima', function() {
            var $kCard = $(this).closest('.item-kendaraan');
            var ki = getKendaraanIndex($kCard);
            var pi = $kCard.find('.list-penerima > .item-penerima').length;
            addPenerima($kCard, ki, pi, null);
            // Tambah satu baris pakan kosong sebagai starter
            var $pCard = $kCard.find('.list-penerima > .item-penerima').last();
            addPakan($pCard, ki, pi, 0, null);
        });

        $(document).on('click', '.btn-hapus-penerima', function() {
            var $kCard = $(this).closest('.item-kendaraan');
            var $list = $kCard.find('.list-penerima');
            if ($list.find('.item-penerima').length > 1) {
                $(this).closest('.item-penerima').remove();
                updatePenerimaLabels($kCard);
            }
        });

        $(document).on('click', '.btn-tambah-pakan', function() {
            var $pCard = $(this).closest('.item-penerima');
            var $kCard = $pCard.closest('.item-kendaraan');
            var ki = getKendaraanIndex($kCard);
            var pi = getPenerimaIndex($pCard);
            var pki = $pCard.find('.list-pakan .item-pakan').length;
            addPakan($pCard, ki, pi, pki, null);
            updatePakanHapus($pCard.find('.list-pakan'));
        });

        $(document).on('click', '.btn-hapus-pakan', function() {
            var $list = $(this).closest('.list-pakan');
            if ($list.find('.item-pakan').length > 1) {
                $(this).closest('.item-pakan').remove();
                updatePakanHapus($list);
            }
        });

        $(document).on('input', '.input-jumlah-kg', function() {
            updateKarung($(this));
        });

        // Auto-calc karung muatan kendaraan
        $(document).on('input', '.input-muatan-kg', function() {
            updateMuatanKarung($(this).closest('.item-kendaraan'));
        });

        // ── Populate Jenis Kendaraan dropdown saat Supplier dipilih ─────
        $(document).on('change', '.input-supplier', function() {
            var $kCard = $(this).closest('.item-kendaraan');
            var $jenisSelect = $kCard.find('.input-jenis-kendaraan');
            var $tujuanSelect = $kCard.find('.input-tujuan-id');
            var supplierId = $(this).val();

            // Reset
            $jenisSelect.html('<option value="">-- Loading... --</option>').prop('disabled', true);
            $tujuanSelect.html('<option value="">-- Loading... --</option>').prop('disabled', true);
            $kCard.removeData('ongkos-map');
            $kCard.find('.input-ongkos-angkut').val(0);
            $kCard.find('.input-oa-info').text('');

            if (!supplierId) {
                $jenisSelect.html('<option value="">-- Pilih Supplier Dulu --</option>').prop('disabled', true);
                $tujuanSelect.html('<option value="">-- Pilih Supplier Dulu --</option>').prop('disabled', true);
                return;
            }

            $.ajax({
                url: '{{ route('supplier.get-jenis-kendaraan') }}',
                method: 'GET',
                data: {
                    supplier_id: supplierId
                },
                success: function(response) {
                    if (response.success) {
                        $kCard.data('ongkos-map', response.ongkos_map || {});

                        if (response.jenis_kendaraan.length > 0) {
                            var jenisOpts = '<option value="">-- Pilih Jenis Kendaraan --</option>';
                            response.jenis_kendaraan.forEach(function(jenis) {
                                jenisOpts += '<option value="' + jenis + '">' + jenis +
                                    '</option>';
                            });
                            $jenisSelect.html(jenisOpts).prop('disabled', false);
                        } else {
                            $jenisSelect.html(
                                '<option value="">-- Tidak Ada Jenis Kendaraan --</option>').prop(
                                'disabled', true);
                        }

                        if (response.tujuans.length > 0) {
                            var tujuanOpts = '<option value="">-- Pilih Tujuan --</option>';
                            response.tujuans.forEach(function(tujuan) {
                                tujuanOpts += '<option value="' + tujuan.id + '">' + tujuan
                                    .nama + '</option>';
                            });
                            $tujuanSelect.html(tujuanOpts).prop('disabled', false);
                        } else {
                            $tujuanSelect.html('<option value="">-- Tidak Ada Tujuan --</option>').prop(
                                'disabled', true);
                            alertify.warning('Supplier ini belum memiliki tujuan yang terdaftar.');
                        }
                    }
                },
                error: function() {
                    $jenisSelect.html('<option value="">-- Error Loading --</option>').prop('disabled',
                        true);
                    $tujuanSelect.html('<option value="">-- Error Loading --</option>').prop('disabled',
                        true);
                    alertify.error('Gagal mengambil data supplier');
                }
            });
        });

        // ── Auto-fill OA kendaraan saat tujuan atau jenis kendaraan berubah ──
        function updateOaKendaraan($kCard) {
            var ongkosMap = $kCard.data('ongkos-map') || {};
            var tujuanId = $kCard.find('.input-tujuan-id').val();
            var jenis = $kCard.find('.input-jenis-kendaraan').val() || '';

            if (!tujuanId) {
                $kCard.find('.input-ongkos-angkut').val(0);
                $kCard.find('.input-oa-info').text('');
                return;
            }

            var oa = 0;
            if (ongkosMap[tujuanId]) {
                if (jenis && ongkosMap[tujuanId][jenis] !== undefined) {
                    oa = ongkosMap[tujuanId][jenis];
                } else if (ongkosMap[tujuanId][''] !== undefined) {
                    oa = ongkosMap[tujuanId][''];
                } else {
                    var vals = Object.values(ongkosMap[tujuanId]);
                    if (vals.length > 0) oa = vals[0];
                }
            }

            $kCard.find('.input-ongkos-angkut').val(oa);
            if (oa > 0) {
                $kCard.find('.input-oa-info').text('Auto dari supplier: Rp ' + oa.toLocaleString('id-ID') + '/kg');
            } else {
                $kCard.find('.input-oa-info').text('Tidak ada data OA, isi manual');
            }
        }

        $(document).on('change', '.input-tujuan-id', function() {
            var $kCard = $(this).closest('.item-kendaraan');
            if ($kCard.length) updateOaKendaraan($kCard);
        });

        // ── Dropdown penerima: gunakan native select + change event ──
        function initSelect2Penerima($select) {
            // Tidak menggunakan select2 agar set nilai programatik lebih reliable
            // Native select sudah cukup
        }

        // ── Saat penerima dipilih: auto-fill tujuan ───────────────
        $(document).on('change', '.input-nama-penerima', function() {
            var $pCard = $(this).closest('.item-penerima');
            var $opt = $(this).find('option:selected');
            var tujuanId = $opt.data('tujuan-id') || '';
            var tujuanNama = $opt.data('tujuan-nama') || '';
            var penerimaId = $opt.data('penerima-id') || '';

            $pCard.find('.input-penerima-id').val(penerimaId);
            $pCard.find('.input-tujuan-id').val(tujuanId);
            $pCard.find('.input-tujuan-display').val(tujuanNama);

            if (tujuanId) {
                triggerOngkosAutoFill($pCard);
            }
        });

        // Saat penerima di-clear (pilih opsi kosong)
        $(document).on('change', '.input-nama-penerima', function() {
            if (!$(this).val()) {
                var $pCard = $(this).closest('.item-penerima');
                $pCard.find('.input-penerima-id').val('');
                $pCard.find('.input-tujuan-id').val('');
                $pCard.find('.input-tujuan-display').val('');
                $pCard.find('.tujuan-warning').remove();
                $pCard.find('.input-tujuan-display').removeClass(
                    'border-warning border-danger border-success border-2');
                $pCard.find('.oa-info-text').hide();
                $pCard.find('.oa-info-badge').hide();
                $pCard.attr('data-ongkos-oa', 0);
            }
        });
        // ── Auto-fill Ongkos OA Pengiriman (dari Supplier + Tujuan + Jenis Kendaraan) ─────
        function triggerOngkosAutoFill($pCard) {
            var $kCard = $pCard.closest('.item-kendaraan');
            var supplierId = $kCard.find('.input-supplier').val();
            var tujuanId = $pCard.find('.input-tujuan-id').val();
            var jenisKendaraan = $kCard.find('.input-jenis-kendaraan').val();

            $pCard.find('.tujuan-warning').remove();
            $pCard.find('.input-tujuan-display').removeClass('border-warning border-danger border-success border-2');

            if (!supplierId || !tujuanId) return;

            $.ajax({
                url: '{{ route('supplier.get-ongkos') }}',
                method: 'GET',
                data: {
                    supplier_id: supplierId,
                    tujuan_id: tujuanId,
                    jenis_kendaraan: jenisKendaraan || null
                },
                success: function(response) {
                    if (response.success) {
                        var oa = response.ongkos_angkut;

                        // Simpan di data attribute agar baris pakan baru bisa auto-fill
                        $pCard.attr('data-ongkos-oa', oa);

                        // Update semua baris pakan yang sudah ada
                        $pCard.find('[name*="[ongkos_oa]"]').val(oa);

                        // Update badge info OA
                        if (oa > 0) {
                            $pCard.find('.oa-info-value').text(oa.toLocaleString('id-ID'));
                            $pCard.find('.oa-info-badge').show();
                        }

                        if (!response.has_relation) {
                            $pCard.find('.input-tujuan-display').addClass('border-danger border-2');
                            var tujuanNama = $pCard.find('.input-tujuan-display').val();
                            var warningHtml = `<div class="alert alert-danger alert-dismissible fade show py-2 small mt-1 tujuan-warning" role="alert">
                                <i class="fa fa-exclamation-triangle"></i> <strong>Peringatan!</strong>
                                Supplier tidak melayani tujuan "${tujuanNama}"${jenisKendaraan ? ' dengan kendaraan "' + jenisKendaraan + '"' : ''}.
                                Isi ongkos OA secara manual.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>`;
                            $pCard.find('.input-tujuan-display').closest('.col-md-4').append(warningHtml);
                            alertify.warning('Supplier tidak melayani tujuan tersebut. Isi ongkos OA manual.');
                        } else {
                            $pCard.find('.input-tujuan-display').addClass('border-success border-2');
                        }
                    }
                },
                error: function() {
                    alertify.error('Gagal mengambil ongkos angkut');
                }
            });
        }

        // Trigger saat jenis kendaraan berubah
        $(document).on('change', '.input-jenis-kendaraan', function() {
            var $kCard = $(this).closest('.item-kendaraan');
            updateOaKendaraan($kCard);
            $kCard.find('.item-penerima').each(function() {
                var $pCard = $(this);
                if ($pCard.find('.input-tujuan-id').val()) {
                    triggerOngkosAutoFill($pCard);
                }
            });
        });

        // ── Validasi ──────────────────────────────────────────────
        $('#formPO').on('submit', function(e) {
            var valid = true;
            $('.is-invalid').removeClass('is-invalid');
            $('.err-client').remove();

            var $kendaraans = $('#listKendaraan .item-kendaraan');
            if ($kendaraans.length === 0) {
                $('#listKendaraan').before(
                    '<div class="alert alert-danger py-2 small err-client">Minimal satu kendaraan wajib diisi.</div>'
                );
                valid = false;
            }

            $kendaraans.each(function() {
                var $k = $(this);
                var $polisi = $k.find('.input-no-polisi');
                if (!$polisi.val().trim()) {
                    $polisi.addClass('is-invalid');
                    valid = false;
                }
                // Penerima dan pakan bersifat opsional
            });

            if (!valid) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: ($('.is-invalid').first().length ? ($('.is-invalid').first().offset().top -
                        100) : 0)
                }, 300);
            }
        });

        $(document).on('input change', '.is-invalid', function() {
            $(this).removeClass('is-invalid');
        });

        // ── DP Handler Script - Load sebelum init untuk memastikan fungsi tersedia ──────────────────
    </script>
    <script src="{{ asset('js/po-dp-handler.js') }}"></script>
    <script>
        // ── Init: load data existing dari server ──────────────────
        @php
            $kendaraanData = $po->kendaraans
                ->map(function ($k) {
                    return [
                        'id' => $k->id,
                        'no_polisi' => $k->no_polisi,
                        'nama_sopir' => $k->nama_sopir,
                        'no_surat_jalan' => $k->no_surat_jalan,
                        'supplier_id' => $k->supplier_id,
                        'jenis_kendaraan' => $k->jenis_kendaraan,
                        'tujuan_id' => $k->tujuan_id,
                        'ongkos_angkut' => $k->ongkos_angkut,
                        'jumlah_kg' => $k->jumlah_kg,
                        'status' => $k->status,
                        'dp_nominal' => $k->dp_nominal,
                        'dp_persen' => $k->dp_persen,
                        'dp_tanggal' => $k->dp_tanggal?->format('Y-m-d'),
                        'dp_metode' => $k->dp_metode,
                        'dp_keterangan' => $k->dp_keterangan,
                        'penerima' => $k->penerimas
                            ->map(function ($p) {
                                return [
                                    'id' => $p->id,
                                    'penerima_id' => $p->penerima_id,
                                    'nama_penerima' => $p->nama_penerima,
                                    'tujuan_id' => $p->tujuan_id,
                                    'tujuan_nama' => $p->tujuan->nama ?? '',
                                    'no_do' => $p->no_do,
                                    'status' => $p->status,
                                    'pakans' => $p->pakans
                                        ->map(function ($pk) {
                                            return [
                                                'kode_pakan_id' => $pk->kode_pakan_id,
                                                'jumlah_kg' => $pk->jumlah_kg,
                                                'ongkos_oa' => $pk->ongkos_oa,
                                                'harga_pt_sum' => $pk->harga_pt_sum,
                                            ];
                                        })
                                        ->values()
                                        ->toArray(),
                                ];
                            })
                            ->values()
                            ->toArray(),
                    ];
                })
                ->values()
                ->toArray();
        @endphp

        var existingData = @json($kendaraanData);
        if (existingData.length > 0) {
            existingData.forEach(function(k) {
                addKendaraan(k);
            });
        } else {
            addKendaraan(null);
        }

        // Highlight field yang error dari server (setelah data di-render)
        @if ($errors->any())
            @foreach ($errors->keys() as $key)
                @php
                    $bracketKey = preg_replace('/\.(\w+)/', '[$1]', $key);
                @endphp
                $('[name="{{ $bracketKey }}"]').addClass('is-invalid');
            @endforeach
        @endif

        // ── Lock / Unlock ─────────────────────────────────────────
        function confirmLock(id) {
            alertify.confirm(
                "Kunci PO?",
                "PO yang dikunci tidak dapat diedit. Semua kendaraan harus berstatus selesai atau batal. Lanjutkan?",
                function() {
                    $.post('/purchase-order/' + id + '/lock', {
                            _token: '{{ csrf_token() }}'
                        })
                        .done(function(res) {
                            if (res.success) {
                                alertify.success(res.message);
                                setTimeout(function() {
                                    location.reload();
                                }, 800);
                            } else {
                                alertify.error(res.message);
                            }
                        })
                        .fail(function() {
                            alertify.error('Gagal mengunci PO.');
                        });
                },
                function() {}
            );
        }

        function confirmUnlock(id) {
            alertify.confirm(
                "Buka Kunci PO?",
                "PO akan kembali ke status Draft dan bisa diedit. Lanjutkan?",
                function() {
                    $.post('/purchase-order/' + id + '/unlock', {
                            _token: '{{ csrf_token() }}'
                        })
                        .done(function(res) {
                            if (res.success) {
                                alertify.success(res.message);
                                setTimeout(function() {
                                    location.reload();
                                }, 800);
                            } else {
                                alertify.error(res.message);
                            }
                        })
                        .fail(function() {
                            alertify.error('Gagal membuka kunci PO.');
                        });
                },
                function() {}
            );
        }

        // Disable semua input jika PO terkunci
        @if ($po->isLocked())
            $('#formPO input, #formPO select, #formPO textarea, #formPO button[type="button"]').prop('disabled', true);
        @endif
    </script>
@endsection
