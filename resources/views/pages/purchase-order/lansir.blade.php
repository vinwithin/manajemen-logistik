@extends('layout.app')
@section('content')

    {{-- Header --}}
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center py-3">
            <div>
                <h5 class="mb-1 fw-bold">
                    <i class="fa fa-truck text-info"></i> Proses Lansir —
                    <span class="text-primary">{{ $item->no_polisi }}</span>
                </h5>
                <div class="text-muted small">
                    PO: <strong>{{ $item->po->no_po }}</strong> &nbsp;·&nbsp;
                    {{ $item->po->cv?->nama_cv ?? '-' }} &nbsp;·&nbsp;
                    {{ $item->po->tanggal_po->format('d M Y') }}
                </div>
                <div class="mt-1 small">
                    @if ($item->tujuan)
                        <span class="me-2">📍 {{ $item->tujuan->nama }}</span>
                    @endif
                    @if ($item->berat)
                        <span class="me-2">⚖️ {{ number_format($item->berat, 0, ',', '.') }} kg</span>
                    @endif
                    @if ($item->nama_penerima)
                        <span>👤 {{ $item->nama_penerima }}</span>
                    @endif
                </div>
            </div>
            <a href="{{ route('purchase-order.show', encrypt($item->po_id)) }}" class="btn btn-sm btn-secondary">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger py-2">{{ session('error') }}</div>
    @endif

    {{-- ── FORM INPUT LANSIR ── --}}
    @if (!$item->po->isLocked())
        <div class="alert alert-warning py-2 mb-3">
            <i class="fa fa-lock"></i>
            <strong>PO belum terkunci.</strong> Kunci PO terlebih dahulu sebelum memproses lansir.
            <a href="{{ route('purchase-order.edit', encrypt($item->po_id)) }}" class="alert-link ms-1">Buka halaman edit
                PO →</a>
        </div>
    @elseif ($item->selesai_lansir_at)
        <div class="card mb-3">
            <div class="card-body text-center py-4 text-muted">
                <i class="fa fa-lock fa-2x mb-2 d-block text-success opacity-50"></i>
                Lansir sudah selesai. Tidak dapat menambah data baru.
            </div>
        </div>
    @else
        <form method="post" action="{{ route('po-item.lansir', $item->id) }}" id="formLansir">
            @csrf
            <div class="row g-3 mb-3">

                {{-- ── Informasi Lansir ── --}}
                <div class="col-12 col-xl-5">
                    <div class="card h-100">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fa fa-truck"></i> Informasi Lansir</h6>
                        </div>
                        <div class="card-body">

                            {{-- Validator --}}
                            <div class="form-group mb-3">
                                <label class="form-label">Nama Validator / Admin Cabang <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="validasi_oleh"
                                    class="form-control @error('validasi_oleh') is-invalid @enderror"
                                    value="{{ old('validasi_oleh', $item->validasi_oleh) }}"
                                    placeholder="Nama yang memvalidasi kedatangan">
                                @error('validasi_oleh')
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
                                        style="width:20%">
                                    <input type="text" name="mobils[0][nama_sopir]" class="form-control form-control-sm"
                                        placeholder="Nama sopir" style="width:20%">
                                    <input type="number" name="mobils[0][berat]"
                                        class="form-control form-control-sm berat-m" placeholder="Berat" step="0.01"
                                        min="0" style="width:15%">
                                    <input type="number" name="mobils[0][jumlah_karung]"
                                        class="form-control form-control-sm" placeholder="Karung" min="0"
                                        style="width:13%">
                                    <input type="number" name="mobils[0][ongkos]"
                                        class="form-control form-control-sm ongkos-m" placeholder="Ongkos" step="0.01"
                                        min="0" style="width:16%">
                                    <button type="button"
                                        class="btn btn-sm btn-outline-danger btn-hapus-mobil flex-shrink-0" disabled>
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
                                        placeholder="Nama tim / orang" style="width:35%">
                                    <input type="number" name="tims[0][berat]"
                                        class="form-control form-control-sm berat-t" placeholder="Berat" step="0.01"
                                        min="0" style="width:16%">
                                    <input type="number" name="tims[0][jumlah_karung]"
                                        class="form-control form-control-sm" placeholder="Karung" min="0"
                                        style="width:14%">
                                    <input type="number" name="tims[0][upah]"
                                        class="form-control form-control-sm upah-t" placeholder="Upah/kg" step="0.01"
                                        min="0" style="width:18%">
                                    <button type="button"
                                        class="btn btn-sm btn-outline-danger btn-hapus-tim flex-shrink-0" disabled>
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div id="previewTim" class="small text-muted mt-1" style="display:none">
                                Total upah bongkar: <strong id="totalUpah">-</strong>
                            </div>
                        </div>
                    </div>

                    <button class="btn btn-info text-white w-100" type="submit">
                        <i class="fa fa-save"></i> Simpan Data Lansir
                    </button>

                </div>
            </div>
        </form>
    @endif

    {{-- ── RIWAYAT & SELESAI ── --}}
    <div class="row g-3">

        {{-- Selesai lansir --}}
        <div class="col-12 col-xl-5">
            @if ($item->selesai_lansir_at)
                <div class="card border-success">
                    <div class="card-body py-3 d-flex align-items-center gap-3">
                        <i class="fa fa-check-circle text-success fa-2x"></i>
                        <div>
                            <div class="fw-bold text-success">Lansir Selesai</div>
                            <div class="small text-muted">{{ $item->selesai_lansir_at->format('d M Y, H:i') }}</div>
                            @if ($item->bukti_tiba)
                                <a href="{{ asset('storage/' . $item->bukti_tiba) }}" target="_blank"
                                    class="small text-primary">
                                    <i class="fa fa-file"></i> Lihat Bukti Tiba
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @elseif (!$item->po->isLocked())
                <div class="card border-secondary">
                    <div class="card-body py-3 text-muted small text-center">
                        <i class="fa fa-lock fa-2x mb-2 d-block opacity-25"></i>
                        Kunci PO terlebih dahulu untuk menyelesaikan lansir.
                    </div>
                </div>
            @else
                <div class="card border-warning">
                    <div class="card-header bg-warning bg-opacity-10">
                        <h6 class="mb-0 text-warning"><i class="fa fa-flag-checkered"></i> Tandai Lansir Selesai</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Upload bukti tiba untuk menandakan semua proses lansir telah selesai.
                            File berupa foto atau PDF (maks. 5MB).
                        </p>
                        <form method="post" action="{{ route('po-item.lansir-selesai', $item->id) }}"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Bukti Tiba <span class="text-danger">*</span></label>
                                <input type="file" name="bukti_tiba"
                                    class="form-control @error('bukti_tiba') is-invalid @enderror"
                                    accept=".jpg,.jpeg,.png,.pdf">
                                <small class="text-muted">JPG, PNG, atau PDF · Maks 5MB</small>
                                @error('bukti_tiba')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-warning w-100">
                                <i class="fa fa-flag-checkered"></i> Selesaikan Lansir
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        {{-- Riwayat --}}
        <div class="col-12 col-xl-7">

            @if ($item->lansirRecords->isEmpty())
                <div class="card">
                    <div class="card-body text-center text-muted py-4">
                        <i class="fa fa-truck fa-2x mb-2 d-block opacity-25"></i>
                        Belum ada data lansir
                    </div>
                </div>
            @else
                @foreach ($item->lansirRecords as $ei => $event)
                    @php
                        $evBerat = $event->mobils->sum('berat');
                        $evKarung = $event->mobils->sum('jumlah_karung');
                        $evOngkos = $event->mobils->sum(fn($m) => ($m->berat ?? 0) * ($m->ongkos ?? 0));
                        $evUpah = $event->tims->sum(fn($t) => ($t->berat ?? $evBerat) * ($t->upah ?? 0));
                    @endphp

                    <div class="card mb-3">
                        {{-- Event header --}}
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-secondary">Riwayat Lansir {{ $ei + 1 }}</span>
                                <span class="fw-semibold small">{{ $event->validasi_oleh }}</span>
                            </div>
                            <small class="text-muted">{{ $event->selesai_at?->format('d M Y, H:i') }}</small>
                        </div>

                        <div class="card-body p-0">

                            {{-- Tabel Mobil --}}
                            <div class="px-3 pt-3 pb-1">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small fw-semibold text-muted">🚛 Mobil Lansir</span>
                                    <span class="badge bg-info">{{ $event->mobils->count() }} mobil</span>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>No</th>
                                            <th>No. Polisi</th>
                                            <th>Nama Sopir</th>
                                            <th>Berat (kg)</th>
                                            <th>Karung</th>
                                            <th>Ongkos (Rp)</th>
                                            <th>Total Ongkos</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($event->mobils as $i => $m)
                                            @php $totalOngkos = ($m->berat ?? 0) * ($m->ongkos ?? 0); @endphp
                                            <tr>
                                                <td>{{ $i + 1 }}</td>
                                                <td><strong>{{ $m->no_polisi }}</strong></td>
                                                <td>{{ $m->nama_sopir ?? '-' }}</td>
                                                <td>{{ $m->berat ? number_format($m->berat, 2, ',', '.') . ' kg' : '-' }}
                                                </td>
                                                <td>{{ $m->jumlah_karung ?: '-' }}</td>
                                                <td>{{ $m->ongkos ? 'Rp ' . number_format($m->ongkos, 0, ',', '.') : '-' }}
                                                </td>
                                                <td>{{ $totalOngkos ? 'Rp ' . number_format($totalOngkos, 0, ',', '.') : '-' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    @if ($event->mobils->count() > 1)
                                        <tfoot class="table-light fw-bold">
                                            <tr>
                                                <td colspan="3">Total</td>
                                                <td>{{ number_format($evBerat, 2, ',', '.') }} kg</td>
                                                <td>{{ $evKarung ?: '-' }}</td>
                                                <td>-</td>
                                                <td>Rp {{ number_format($evOngkos, 0, ',', '.') }}</td>
                                            </tr>
                                        </tfoot>
                                    @endif
                                </table>
                            </div>

                            {{-- Tabel Tim --}}
                            <div class="px-3 pt-3 pb-1 border-top mt-2">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small fw-semibold text-muted">👷 Tim Bongkar</span>
                                    <span class="badge bg-secondary">{{ $event->tims->count() }} tim</span>
                                </div>
                            </div>
                            @if ($event->tims->isEmpty())
                                <div class="text-center text-muted py-2 small pb-3">Tidak ada data tim bongkar.</div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>No</th>
                                                <th>Nama Tim / Orang</th>
                                                <th>Berat (kg)</th>
                                                <th>Karung</th>
                                                <th>Upah (Rp/kg)</th>
                                                <th>Total Upah</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($event->tims as $i => $t)
                                                @php
                                                    $beratTim = $t->berat ?? $evBerat;
                                                    $totalUpah = $beratTim * ($t->upah ?? 0);
                                                @endphp
                                                <tr>
                                                    <td>{{ $i + 1 }}</td>
                                                    <td><strong>{{ $t->nama_tim }}</strong></td>
                                                    <td>{{ $t->berat ? number_format($t->berat, 2, ',', '.') . ' kg' : '-' }}
                                                    </td>
                                                    <td>{{ $t->jumlah_karung ?: '-' }}</td>
                                                    <td>{{ $t->upah ? 'Rp ' . number_format($t->upah, 0, ',', '.') : '-' }}
                                                    </td>
                                                    <td>{{ $totalUpah ? 'Rp ' . number_format($totalUpah, 0, ',', '.') : '-' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        @if ($event->tims->count() > 1)
                                            @php
                                                $grandUpah = $event->tims->sum(
                                                    fn($t) => ($t->berat ?? $evBerat) * ($t->upah ?? 0),
                                                );
                                            @endphp
                                            <tfoot class="table-light fw-bold">
                                                <tr>
                                                    <td colspan="5">Total Upah</td>
                                                    <td>Rp {{ number_format($grandUpah, 0, ',', '.') }}</td>
                                                </tr>
                                            </tfoot>
                                        @endif
                                    </table>
                                </div>
                            @endif

                            {{-- Subtotal event --}}
                            <div
                                class="d-flex justify-content-between small fw-semibold px-3 py-2 bg-light border-top text-muted">
                                <span>{{ number_format($evBerat, 2, ',', '.') }} kg &nbsp;·&nbsp; {{ $evKarung }}
                                    karung</span>
                                <span>
                                    Ongkos: Rp {{ number_format($evOngkos, 0, ',', '.') }}
                                    &nbsp;|&nbsp;
                                    Upah: Rp {{ number_format($evUpah, 0, ',', '.') }}
                                </span>
                            </div>

                        </div>
                    </div>
                @endforeach

                @php
                    $gBerat = $item->lansirRecords->sum(fn($e) => $e->mobils->sum('berat'));
                    $gKarung = $item->lansirRecords->sum(fn($e) => $e->mobils->sum('jumlah_karung'));
                    $gOngkos = $item->lansirRecords->sum(
                        fn($e) => $e->mobils->sum(fn($m) => ($m->berat ?? 0) * ($m->ongkos ?? 0)),
                    );
                    $gUpah = $item->lansirRecords->sum(function ($e) {
                        $b = $e->mobils->sum('berat');
                        return $e->tims->sum(fn($t) => ($t->berat ?? $b) * ($t->upah ?? 0));
                    });
                @endphp
                <div class="card">
                    <div class="card-body py-2 d-flex justify-content-between align-items-center fw-bold small">
                        <span class="text-primary">
                            Grand Total &nbsp;·&nbsp;
                            {{ number_format($gBerat, 2, ',', '.') }} kg &nbsp;·&nbsp; {{ $gKarung }} karung
                        </span>
                        <span class="text-muted">
                            Ongkos: Rp {{ number_format($gOngkos, 0, ',', '.') }}
                            &nbsp;|&nbsp;
                            Upah: Rp {{ number_format($gUpah, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            @endif
        </div>

    </div>

    <script>
        function fmt(n) {
            return 'Rp ' + Math.round(n || 0).toLocaleString('id-ID');
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
                var beratTim = parseFloat($(this).find('.berat-t').val()) || totalBerat;
                totalUpah += beratTim * (parseFloat($(this).find('.upah-t').val()) || 0);
            });
            if (totalUpah > 0) {
                $('#totalUpah').text(fmt(totalUpah));
                $('#previewTim').show();
            } else {
                $('#previewTim').hide();
            }
        }
        $(document).on('input', '.berat-m, .berat-t, .upah-t', updateTimPreview);

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
