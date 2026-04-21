@extends('layout.app')
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
                                        <input type="text" class="form-control bg-light" value="{{ $po->no_po }}"
                                            readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Tanggal PO</label>
                                        <input type="text" class="form-control bg-light"
                                            value="{{ $po->tanggal_po->format('d M Y') }}" readonly>
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
                                                        ⚠️ (Omzet ≥ 48jt)
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
                        <label class="form-label">No. Polisi <span class="text-danger">*</span></label>
                        <input type="text" name="kendaraan[__KI__][no_polisi]"
                            class="form-control text-uppercase input-no-polisi" placeholder="Contoh: B 1234 XY">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Nama Sopir</label>
                        <input type="text" name="kendaraan[__KI__][nama_sopir]" class="form-control"
                            placeholder="Opsional">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">No. Surat Jalan</label>
                        <input type="text" name="kendaraan[__KI__][no_surat_jalan]" class="form-control"
                            placeholder="Opsional">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Supplier</label>
                        <select name="kendaraan[__KI__][supplier_id]" class="form-select">
                            <option value="">-- Pilih Supplier --</option>
                            @foreach ($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->initial }} — {{ $s->nama }}</option>
                            @endforeach
                        </select>
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

                {{-- Daftar Penerima dalam kendaraan --}}
                <div class="border rounded p-3 bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-semibold small text-primary">Penerima</span>
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
        <div class="card mb-2 item-penerima border-secondary">
            <div class="card-header bg-secondary bg-opacity-10 py-1 d-flex justify-content-between align-items-center">
                <span class="small fw-semibold label-penerima-no">Penerima #1</span>
                <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-penerima" disabled>
                    <i class="fa fa-times"></i> Hapus
                </button>
            </div>
            <div class="card-body py-2">
                <input type="hidden" name="kendaraan[__KI__][penerima][__PI__][id]" value="">
                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <label class="form-label small">Nama Penerima <span class="text-danger">*</span></label>
                        <input type="text" name="kendaraan[__KI__][penerima][__PI__][nama_penerima]"
                            class="form-control form-control-sm input-nama-penerima" placeholder="Nama peternak">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Tujuan</label>
                        <select name="kendaraan[__KI__][penerima][__PI__][tujuan_id]" class="form-select form-select-sm">
                            <option value="">-- Pilih Tujuan --</option>
                            @foreach ($tujuans as $t)
                                <option value="{{ $t->id }}">{{ $t->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Status Penerima</label>
                        <select name="kendaraan[__KI__][penerima][__PI__][status]"
                            class="form-select form-select-sm select-status-penerima">
                            <option value="pending">Pending</option>
                            <option value="berangkat">Berangkat</option>
                            <option value="selesai">Selesai</option>
                            <option value="batal">Batal</option>
                        </select>
                    </div>
                </div>

                {{-- Pakan --}}
                <div class="border rounded p-2 bg-white">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small text-muted">Pakan</span>
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
                <label class="form-label small">Karung</label>
                <input type="text" class="form-control form-control-sm input-jumlah-karung" placeholder="0" readonly>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Ongkos OA (Rp/kg)</label>
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
                    <i class="fa fa-times"></i>
                </button>
            </div>
        </div>
    </template>

    <script>
        var kendaraanCount = 0;

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
                    `<div class="alert alert-warning py-2 small mb-0">Omzet <strong>${fmt(omzet)}</strong> (${persen}% dari batas Rp 48jt). Mendekati batas.</div>`
                ).show();
            } else {
                box.html(
                    `<div class="text-muted small mt-1">Omzet: ${fmt(omzet)} / Rp 48.000.000 (${persen}%)</div>`
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
                $card.find('[name="kendaraan[' + ki + '][no_surat_jalan]"]').val(data.no_surat_jalan || '');
                $card.find('[name="kendaraan[' + ki + '][supplier_id]"]').val(data.supplier_id || '');
                $card.find('[name="kendaraan[' + ki + '][status]"]').val(data.status || 'pending');

                var penerimas = data.penerima || [];
                for (var pi = 0; pi < penerimas.length; pi++) {
                    addPenerima($card, ki, pi, penerimas[pi]);
                }
                if (penerimas.length === 0) addPenerima($card, ki, 0, null);
            } else {
                addPenerima($card, ki, 0, null);
            }
        }

        // ── Add penerima ──────────────────────────────────────────
        function addPenerima($kendaraanCard, ki, pi, data) {
            var $card = $(buildPenerima(ki, pi));
            $kendaraanCard.find('.list-penerima').append($card);
            updatePenerimaLabels($kendaraanCard);

            if (data) {
                $card.find('[name="kendaraan[' + ki + '][penerima][' + pi + '][id]"]').val(data.id || '');
                $card.find('[name="kendaraan[' + ki + '][penerima][' + pi + '][nama_penerima]"]').val(data.nama_penerima ||
                    '');
                $card.find('[name="kendaraan[' + ki + '][penerima][' + pi + '][tujuan_id]"]').val(data.tujuan_id || '');
                $card.find('[name="kendaraan[' + ki + '][penerima][' + pi + '][status]"]').val(data.status || 'pending');

                var pakans = data.pakans || [];
                for (var pki = 0; pki < pakans.length; pki++) {
                    addPakan($card, ki, pi, pki, pakans[pki]);
                }
                if (pakans.length === 0) addPakan($card, ki, pi, 0, null);
                updatePakanHapus($card.find('.list-pakan'));
            } else {
                addPakan($card, ki, pi, 0, null);
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

                $k.find('.list-penerima > .item-penerima').each(function() {
                    var $p = $(this);
                    var $nama = $p.find('.input-nama-penerima');
                    if (!$nama.val().trim()) {
                        $nama.addClass('is-invalid');
                        valid = false;
                    }

                    $p.find('.list-pakan .item-pakan').each(function() {
                        var $pk = $(this);
                        var $kode = $pk.find('select');
                        var $kg = $pk.find('.input-jumlah-kg');
                        if (!$kode.val()) {
                            $kode.addClass('is-invalid');
                            valid = false;
                        }
                        if (!$kg.val() || parseFloat($kg.val()) <= 0) {
                            $kg.addClass('is-invalid');
                            valid = false;
                        }
                    });
                });
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
                        'status' => $k->status,
                        'penerima' => $k->penerimas
                            ->map(function ($p) {
                                return [
                                    'id' => $p->id,
                                    'nama_penerima' => $p->nama_penerima,
                                    'tujuan_id' => $p->tujuan_id,
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
