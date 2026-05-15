@extends('layout.app')
@section('content')
    @php
        $poBadge = match ($po->status) {
            'locked' => ['color' => 'success', 'label' => 'Terkunci'],
            default => ['color' => 'secondary', 'label' => 'Draft'],
        };
        $statusBadge = [
            'pending' => ['color' => 'secondary', 'label' => 'Pending'],
            'berangkat' => ['color' => 'warning', 'label' => 'Berangkat'],
            'tiba' => ['color' => 'info', 'label' => 'Tiba'],
            'selesai' => ['color' => 'success', 'label' => 'Selesai'],
            'batal' => ['color' => 'danger', 'label' => 'Batal'],
        ];

        // Flatten semua penerima dari semua kendaraan
        $allPenerimas = $po->kendaraans->flatMap(fn($k) => $k->penerimas);

        // Grand totals
        $grandTotalKg = $allPenerimas->sum('total_kg');
        $grandTotalOa = $allPenerimas->sum('total_oa');
        $grandTotalPtSum = $allPenerimas->sum('total_pt_sum');

        // Grand total karung per kode pakan
        $grandKarung = [];
        foreach ($kodePakanList as $kp) {
            $grandKarung[$kp->id] = 0;
        }
        foreach ($allPenerimas as $penerima) {
            foreach ($penerima->pakans as $pakan) {
                if (isset($grandKarung[$pakan->kode_pakan_id])) {
                    $grandKarung[$pakan->kode_pakan_id] += $pakan->jumlah_karung;
                }
            }
        }
    @endphp

    {{-- Header --}}
    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h5 class="mb-1 fw-bold">{{ $po->no_po }}</h5>
                    <div class="text-muted small mb-1">
                        {{ $po->tanggal_po->format('d M Y') }} &nbsp;·&nbsp;
                        <strong>{{ $po->cv?->nama_cv ?? '-' }}</strong>
                    </div>
                    <span class="badge bg-{{ $poBadge['color'] }}">{{ $poBadge['label'] }}</span>
                    @if ($po->catatan)
                        <div class="text-muted small mt-1"><i class="fa fa-sticky-note"></i> {{ $po->catatan }}</div>
                    @endif
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    @if ($po->status === 'draft')
                        <a href="{{ route('purchase-order.edit', encrypt($po->id)) }}" class="btn btn-sm btn-warning">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                    @endif

                    <a href="{{ route('purchase-order.export-po', encrypt($po->id)) }}" class="btn btn-sm btn-success">
                        <i class="fa fa-file-excel-o"></i> Export Excel
                    </a>
                    <a href="{{ route('purchase-order.export-po-pdf-supplier', encrypt($po->id)) }}"
                        class="btn btn-sm btn-danger">
                        <i class="fa fa-file-pdf-o"></i> PDF Supplier
                    </a>
                    <a href="{{ route('purchase-order.export-po-pdf-ptsum', encrypt($po->id)) }}"
                        class="btn btn-sm btn-warning">
                        <i class="fa fa-file-pdf-o"></i> PDF PT Sum
                    </a>
                    <a href="{{ route('rekap-po.show', encrypt($po->id)) }}" class="btn btn-sm btn-info text-white">
                        <i class="fa fa-table"></i> Rekap PO
                    </a>
                    <a href="{{ route('purchase-order.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fa fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    @forelse ($po->kendaraans as $ki => $kendaraan)
        @php
            $kBadge = $statusBadge[$kendaraan->status] ?? ['color' => 'secondary', 'label' => $kendaraan->status];
        @endphp
        <div class="card mb-3">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <div>
                    <span class="fw-bold"><i class="fa fa-truck"></i> {{ $kendaraan->no_polisi }}</span>
                    @if ($kendaraan->nama_sopir)
                        <span class="text-muted small ms-2">· {{ $kendaraan->nama_sopir }}</span>
                    @endif
                    @if ($kendaraan->no_surat_jalan)
                        <span class="text-muted small ms-2">· SJ: {{ $kendaraan->no_surat_jalan }}</span>
                    @endif
                    @if ($kendaraan->supplier)
                        <span class="badge bg-light text-dark border ms-2">{{ $kendaraan->supplier->initial }}</span>
                    @endif
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary">{{ number_format($kendaraan->total_kg, 0, ',', '.') }} kg</span>
                    <span class="badge bg-{{ $kBadge['color'] }}">{{ $kBadge['label'] }}</span>

                    {{-- GPS Assignment Status --}}
                    @if ($kendaraan->activeGps)
                        <span class="badge bg-success" title="GPS Device Assigned">
                            <i class="fa fa-satellite-dish"></i> {{ $kendaraan->activeGps->device_name ?? 'GPS Active' }}
                        </span>
                        <button type="button" class="btn btn-xs btn-outline-danger btn-unassign-gps"
                            data-kendaraan-id="{{ $kendaraan->id }}" data-nopol="{{ $kendaraan->no_polisi }}"
                            title="Lepas GPS">
                            <i class="fa fa-times"></i>
                        </button>
                    @else
                        <button type="button" class="btn btn-xs btn-outline-warning btn-auto-assign-gps"
                            data-kendaraan-id="{{ $kendaraan->id }}" data-nopol="{{ $kendaraan->no_polisi }}"
                            title="Auto Assign GPS berdasarkan Nopol">
                            <i class="fa fa-satellite-dish"></i> Auto Assign GPS
                        </button>
                    @endif

                    <button type="button" class="btn btn-xs btn-outline-info btn-lihat-gps"
                        data-kendaraan-id="{{ $kendaraan->id }}" data-nopol="{{ $kendaraan->no_polisi }}" title="Lihat Lokasi GPS">
                        <i class="fa fa-map-marker"></i> Lokasi
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                {{-- Informasi Down Payment (DP) --}}
                @if ($kendaraan->dp_nominal > 0)
                    <div class="border-bottom bg-light p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0 text-success"><i class="fa fa-money"></i> Informasi Down Payment (DP)</h6>
                            @if ($kendaraan->dpPayment)
                                <a href="{{ route('keuangan.oa.index') }}?search={{ $po->no_po }}"
                                    class="btn btn-sm btn-outline-success" title="Lihat di Keuangan Pembayaran Supplier">
                                    <i class="fa fa-external-link"></i> Lihat Pembayaran
                                </a>
                            @endif
                        </div>
                        <div class="row g-2">
                            <div class="col-md-3">
                                <div class="small text-muted">Total Tagihan Supplier</div>
                                <div class="fw-bold">Rp
                                    {{ number_format($kendaraan->total_tagihan_supplier, 0, ',', '.') }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="small text-muted">Down Payment (DP)</div>
                                <div class="fw-bold text-success">
                                    Rp {{ number_format($kendaraan->dp_nominal, 0, ',', '.') }}
                                    @if ($kendaraan->dp_persen)
                                        <span
                                            class="badge bg-success ms-1">{{ number_format($kendaraan->dp_persen, 1) }}%</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="small text-muted">Sisa Tagihan</div>
                                <div class="fw-bold text-danger">Rp
                                    {{ number_format($kendaraan->sisa_tagihan, 0, ',', '.') }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="small text-muted">Status Pembayaran</div>
                                <div>
                                    <span class="badge {{ $kendaraan->status_pembayaran_badge }}">
                                        {{ $kendaraan->status_pembayaran }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @if ($kendaraan->dp_tanggal || $kendaraan->dp_metode || $kendaraan->dp_keterangan)
                            <div class="row g-2 mt-2 pt-2 border-top">
                                @if ($kendaraan->dp_tanggal)
                                    <div class="col-md-4">
                                        <div class="small text-muted">Tanggal Bayar</div>
                                        <div>{{ $kendaraan->dp_tanggal->format('d M Y') }}</div>
                                    </div>
                                @endif
                                @if ($kendaraan->dp_metode)
                                    <div class="col-md-4">
                                        <div class="small text-muted">Metode Pembayaran</div>
                                        <div class="text-capitalize">{{ $kendaraan->dp_metode }}</div>
                                    </div>
                                @endif
                                @if ($kendaraan->dp_keterangan)
                                    <div class="col-md-12">
                                        <div class="small text-muted">Keterangan</div>
                                        <div class="small">{{ $kendaraan->dp_keterangan }}</div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" rowspan="2">#</th>
                                <th rowspan="2">Nama Penerima</th>
                                <th rowspan="2">Tujuan</th>
                                @foreach ($kodePakanList as $kp)
                                    <th class="text-center">{{ $kp->kode }}</th>
                                @endforeach
                                <th class="text-end" rowspan="2">Total KG</th>
                                <th class="text-end" rowspan="2">Total OA</th>
                                <th class="text-end" rowspan="2">Total PT SUM</th>
                                <th class="text-center" rowspan="2">Status</th>
                                @if ($po->isLocked())
                                    <th class="text-center" rowspan="2">Aksi</th>
                                @endif
                            </tr>
                            <tr>
                                @foreach ($kodePakanList as $kp)
                                    <th class="text-center text-muted small">(karung)</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($kendaraan->penerimas as $pi => $penerima)
                                @php
                                    $pBadge = $statusBadge[$penerima->status] ?? [
                                        'color' => 'secondary',
                                        'label' => $penerima->status,
                                    ];
                                    $pakanMap = $penerima->pakans->keyBy('kode_pakan_id');
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $pi + 1 }}</td>
                                    <td>
                                        {{ $penerima->nama_penerima }}
                                        @if ($penerima->tujuan && $penerima->tujuan->type === 'gudang')
                                            <span class="badge bg-info text-white ms-1"
                                                title="Pakan akan otomatis masuk ke stok gudang saat ditandai Tiba">
                                                <i class="fa fa-warehouse"></i> Gudang
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ $penerima->tujuan?->nama ?? '-' }}</td>
                                    @foreach ($kodePakanList as $kp)
                                        @php $pk = $pakanMap[$kp->id] ?? null; @endphp
                                        <td class="text-center">
                                            @if ($pk)
                                                <span
                                                    title="OA: Rp {{ number_format($pk->ongkos_oa, 0, ',', '.') }}/kg · PT: Rp {{ number_format($pk->harga_pt_sum, 0, ',', '.') }}/kg">
                                                    {{ number_format($pk->jumlah_karung, 0, ',', '.') }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="text-end">{{ number_format($penerima->total_kg, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($penerima->total_oa, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($penerima->total_pt_sum, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $pBadge['color'] }}">{{ $pBadge['label'] }}</span>
                                        @if ($penerima->validasi_oleh)
                                            <div class="text-muted small mt-1">{{ $penerima->validasi_oleh }}</div>
                                            <div class="text-muted small">{{ $penerima->tiba_at?->format('d/m/Y') }}
                                            </div>
                                        @endif
                                    </td>
                                    @if ($po->isLocked())
                                        <td class="text-center">
                                            @if ($kendaraan->status === 'pending')
                                                @if ($pi === 0)
                                                    <button class="btn btn-xs btn-warning btn-aksi-kendaraan"
                                                        data-id="{{ $kendaraan->id }}"
                                                        data-polisi="{{ $kendaraan->no_polisi }}"
                                                        data-target="berangkat">
                                                        <i class="fa fa-truck"></i> Berangkat
                                                    </button>
                                                    <button class="btn btn-xs btn-outline-danger btn-aksi-kendaraan ms-1"
                                                        data-id="{{ $kendaraan->id }}"
                                                        data-polisi="{{ $kendaraan->no_polisi }}" data-target="batal">
                                                        <i class="fa fa-times"></i> Batal
                                                    </button>
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            @elseif ($kendaraan->status === 'berangkat')
                                                {{-- Kendaraan sedang jalan — aksi per penerima --}}
                                                @if (in_array($penerima->status, ['pending', 'berangkat']))
                                                    <button class="btn btn-xs btn-info text-white btn-selesai-penerima"
                                                        data-id="{{ $penerima->id }}"
                                                        data-nama="{{ $penerima->nama_penerima }}"
                                                        data-tujuan-type="{{ $penerima->tujuan?->type ?? '' }}">
                                                        <i class="fa fa-map-marker"></i> Tiba
                                                    </button>
                                                @elseif ($penerima->status === 'tiba')
                                                    {{-- Sudah tiba — pilih: Selesai langsung atau Lansir --}}
                                                    @if ($penerima->tujuan && $penerima->tujuan->type === 'gudang')
                                                        {{-- Jika gudang, langsung selesai (stok sudah masuk otomatis) --}}
                                                        <button class="btn btn-xs btn-success btn-aksi-penerima"
                                                            data-id="{{ $penerima->id }}"
                                                            data-nama="{{ $penerima->nama_penerima }}"
                                                            data-target="selesai">
                                                            <i class="fa fa-check"></i> Selesai
                                                        </button>
                                                        {{-- Tombol lansir gudang untuk pengeluaran stok --}}
                                                        <a href="{{ route('gudang.stok.show', ['id' => $penerima->tujuan_id]) }}"
                                                            class="btn btn-xs btn-warning ms-1">
                                                            <i class="fa fa-truck"></i> Lihat Stok
                                                        </a>
                                                    @else
                                                        {{-- Jika bukan gudang, proses lansir penerima normal --}}
                                                        <button class="btn btn-xs btn-success btn-aksi-penerima"
                                                            data-id="{{ $penerima->id }}"
                                                            data-nama="{{ $penerima->nama_penerima }}"
                                                            data-target="selesai">
                                                            <i class="fa fa-check"></i> Selesai
                                                        </button>
                                                        <a href="{{ route('po-penerima.lansir-page', encrypt($penerima->id)) }}"
                                                            class="btn btn-xs btn-warning ms-1">
                                                            <i class="fa fa-truck"></i> Lansir
                                                        </a>
                                                    @endif
                                                    @if ($penerima->bukti_tiba)
                                                        <a href="{{ asset('storage/' . $penerima->bukti_tiba) }}"
                                                            target="_blank" class="btn btn-xs btn-outline-secondary ms-1">
                                                            <i class="fa fa-file"></i> Bukti
                                                        </a>
                                                    @endif
                                                @elseif ($penerima->status === 'selesai')
                                                    @if ($penerima->tujuan && $penerima->tujuan->type === 'gudang')
                                                        {{-- Jika gudang, tampilkan link ke lansir gudang --}}
                                                        <a href="{{ route('gudang.lansir.index') }}"
                                                            class="btn btn-xs btn-warning">
                                                            <i class="fa fa-truck"></i> Lansir Gudang
                                                        </a>
                                                    @else
                                                        {{-- Jika bukan gudang, tampilkan riwayat lansir penerima --}}
                                                        @if ($penerima->lansirs->count() > 0)
                                                            <a href="{{ route('po-penerima.lansir-page', encrypt($penerima->id)) }}"
                                                                class="btn btn-xs btn-info text-white">
                                                                <i class="fa fa-history"></i> Riwayat Lansir
                                                            </a>
                                                        @endif
                                                    @endif
                                                    @if ($penerima->bukti_tiba)
                                                        <a href="{{ asset('storage/' . $penerima->bukti_tiba) }}"
                                                            target="_blank" class="btn btn-xs btn-outline-secondary ms-1">
                                                            <i class="fa fa-file"></i> Bukti
                                                        </a>
                                                    @endif
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            @elseif ($kendaraan->status === 'selesai')
                                                @if ($penerima->tujuan && $penerima->tujuan->type === 'gudang')
                                                    {{-- Jika gudang, tampilkan link ke lansir gudang --}}
                                                    <a href="{{ route('gudang.lansir.index') }}"
                                                        class="btn btn-xs btn-warning">
                                                        <i class="fa fa-truck"></i> Lansir Gudang
                                                    </a>
                                                @else
                                                    {{-- Jika bukan gudang, tampilkan riwayat lansir penerima --}}
                                                    @if ($penerima->lansirs->count() > 0)
                                                        <a href="{{ route('po-penerima.lansir-page', encrypt($penerima->id)) }}"
                                                            class="btn btn-xs btn-info text-white">
                                                            <i class="fa fa-history"></i> Riwayat Lansir
                                                        </a>
                                                    @endif
                                                @endif
                                                @if ($penerima->bukti_tiba)
                                                    <a href="{{ asset('storage/' . $penerima->bukti_tiba) }}"
                                                        target="_blank" class="btn btn-xs btn-outline-secondary ms-1">
                                                        <i class="fa fa-file"></i> Bukti
                                                    </a>
                                                @endif
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 7 + $kodePakanList->count() }}" class="text-center text-muted py-2">
                                        Belum ada penerima.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($kendaraan->penerimas->count() > 0)
                            <tfoot class="table-light fw-semibold">
                                <tr>
                                    <td colspan="{{ 3 }}" class="text-end">Subtotal kendaraan</td>
                                    @foreach ($kodePakanList as $kp)
                                        <td class="text-center">
                                            {{ number_format($kendaraan->penerimas->flatMap->pakans->where('kode_pakan_id', $kp->id)->sum('jumlah_karung'), 0, ',', '.') }}
                                        </td>
                                    @endforeach
                                    <td class="text-end">{{ number_format($kendaraan->total_kg, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($kendaraan->total_oa, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($kendaraan->total_pt_sum, 0, ',', '.') }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    @empty
        <div class="alert alert-info">Belum ada kendaraan dalam PO ini.</div>
    @endforelse

    {{-- Grand Total --}}
    @if ($po->kendaraans->count() > 0)
        <div class="card mb-3 border-dark">
            <div class="card-body py-2">
                <div class="row g-3 text-center">
                    <div class="col-md-4">
                        <div class="text-muted small">Grand Total KG</div>
                        <div class="fw-bold fs-5">{{ number_format($grandTotalKg, 0, ',', '.') }} kg</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Grand Total OA</div>
                        <div class="fw-bold fs-5 text-primary">Rp {{ number_format($grandTotalOa, 0, ',', '.') }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Grand Total PT SUM</div>
                        <div class="fw-bold fs-5 text-success">Rp {{ number_format($grandTotalPtSum, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal: Tiba (upload bukti + validator) --}}
    <div class="modal fade" id="modalSelesai" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title">Tandai Tiba — <span id="selesaiNama"></span></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Pakan sudah tiba di lokasi penerima. Setelah ini pilih
                        <strong>Selesai</strong> (langsung) atau <strong>Lansir</strong> (proses lansir dulu).
                    </p>
                    <div class="alert alert-info py-2 mb-3" id="notifGudang" style="display:none;">
                        <i class="fa fa-info-circle"></i> <strong>Tujuan Gudang:</strong> Pakan akan otomatis masuk ke stok
                        gudang setelah ditandai tiba.
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-sm">Nama Validator <span class="text-danger">*</span></label>
                        <input type="text" id="selesaiValidator" class="form-control form-control-sm"
                            placeholder="Nama admin / petugas">
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-sm">Tanggal Tiba <span class="text-danger">*</span></label>
                        <input type="date" id="selesaiTanggal" class="form-control form-control-sm">
                        <small class="text-muted">Hanya tanggal (boleh diisi mundur).</small>
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm">Bukti Tiba <span class="text-danger">*</span></label>
                        <input type="file" id="selesaiBukti" class="form-control form-control-sm"
                            accept=".jpg,.jpeg,.png,.pdf">
                        <small class="text-muted">JPG, PNG, PDF · Maks 5MB</small>
                    </div>
                    <div id="errSelesai" class="text-danger small mt-1" style="display:none"></div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-info text-white btn-sm" id="btnKonfirmasiSelesai">
                        <i class="fa fa-map-marker"></i> Konfirmasi Tiba
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if ($po->isLocked())
        <script>
            var activePenerimaId = null;

            function formatDateInput(d) {
                var pad = function(n) {
                    return String(n).padStart(2, '0');
                };
                return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
            }

            // Tombol Berangkat / Batal — aksi di level KENDARAAN
            $(document).on('click', '.btn-aksi-kendaraan', function() {
                var id = $(this).data('id');
                var polisi = $(this).data('polisi');
                var target = $(this).data('target');
                var label = target === 'berangkat' ? 'Berangkat' : 'Batal';

                alertify.confirm(
                    label + ' — ' + polisi,
                    'Tandai kendaraan <strong>' + polisi + '</strong> sebagai <strong>' + label + '</strong>?' +
                    (target === 'berangkat' ?
                        '<br><small class="text-muted">Semua penerima di kendaraan ini otomatis sedang diantar.</small>' :
                        ''),
                    function() {
                        $.ajax({
                            url: '/purchase-order/kendaraan/' + id + '/status',
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                status: target
                            },
                            success: function(res) {
                                if (res.success) {
                                    alertify.success(res.message);
                                    setTimeout(function() {
                                        location.reload();
                                    }, 700);
                                } else {
                                    alertify.error(res.message);
                                }
                            },
                            error: function() {
                                alertify.error('Gagal memperbarui status.');
                            }
                        });
                    },
                    function() {}
                ).set('labels', {
                    ok: label,
                    cancel: 'Batal'
                });
            });

            // Tombol Tiba — buka modal upload bukti
            $(document).on('click', '.btn-selesai-penerima', function() {
                activePenerimaId = $(this).data('id');
                var namaPenerima = $(this).data('nama');
                var tujuanType = $(this).data('tujuan-type');

                $('#selesaiNama').text(namaPenerima);
                $('#selesaiValidator').val('');
                $('#selesaiTanggal').val(formatDateInput(new Date()));
                $('#selesaiBukti').val('');
                $('#errSelesai').hide().text('');

                // Tampilkan notifikasi jika tujuan adalah gudang
                if (tujuanType === 'gudang') {
                    $('#notifGudang').show();
                } else {
                    $('#notifGudang').hide();
                }

                new bootstrap.Modal(document.getElementById('modalSelesai')).show();
            });

            // Konfirmasi Tiba
            $('#btnKonfirmasiSelesai').on('click', function() {
                var validator = $('#selesaiValidator').val().trim();
                var tanggal = $('#selesaiTanggal').val().trim();
                var file = $('#selesaiBukti')[0].files[0];
                $('#errSelesai').hide().text('');

                if (!validator) {
                    $('#errSelesai').text('Nama validator wajib diisi.').show();
                    return;
                }
                if (!tanggal) {
                    $('#errSelesai').text('Tanggal tiba wajib diisi.').show();
                    return;
                }
                if (!file) {
                    $('#errSelesai').text('Bukti tiba wajib diunggah.').show();
                    return;
                }

                var fd = new FormData();
                fd.append('_token', '{{ csrf_token() }}');
                fd.append('status', 'tiba');
                fd.append('validasi_oleh', validator);
                fd.append('tanggal_tiba', tanggal);
                fd.append('bukti_tiba', file);

                $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

                $.ajax({
                    url: '/purchase-order/penerima/' + activePenerimaId + '/status',
                    method: 'POST',
                    data: fd,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        if (res.success) {
                            alertify.success(res.message);
                            bootstrap.Modal.getInstance(document.getElementById('modalSelesai')).hide();
                            setTimeout(function() {
                                location.reload();
                            }, 700);
                        } else {
                            $('#errSelesai').text(res.message).show();
                        }
                    },
                    error: function(xhr) {
                        var msg = xhr.responseJSON?.errors ?
                            Object.values(xhr.responseJSON.errors).flat().join(' ') :
                            (xhr.responseJSON?.message || 'Gagal menyimpan.');
                        $('#errSelesai').text(msg).show();
                    },
                    complete: function() {
                        $('#btnKonfirmasiSelesai').prop('disabled', false).html(
                            '<i class="fa fa-map-marker"></i> Konfirmasi Tiba');
                    }
                });
            });

            // Tombol Selesai langsung (dari status tiba, tanpa modal)
            $(document).on('click', '.btn-aksi-penerima', function() {
                var id = $(this).data('id');
                var nama = $(this).data('nama');
                var target = $(this).data('target');
                if (target !== 'selesai') return;

                alertify.confirm(
                    'Selesai — ' + nama,
                    'Tandai penerima <strong>' + nama + '</strong> sebagai <strong>Selesai</strong>?',
                    function() {
                        $.ajax({
                            url: '/purchase-order/penerima/' + id + '/status',
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                status: 'selesai'
                            },
                            success: function(res) {
                                if (res.success) {
                                    alertify.success(res.message);
                                    setTimeout(function() {
                                        location.reload();
                                    }, 700);
                                } else {
                                    alertify.error(res.message);
                                }
                            },
                            error: function() {
                                alertify.error('Gagal memperbarui status.');
                            }
                        });
                    },
                    function() {}
                ).set('labels', {
                    ok: 'Selesai',
                    cancel: 'Batal'
                });
            });
        </script>
    @endif

    {{-- Modal GPS Lokasi Kendaraan --}}
    <div class="modal fade" id="modalGps" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title"><i class="fa fa-map-marker text-danger"></i> Lokasi GPS — <span
                            id="gpsNopol"></span></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    {{-- Info strip --}}
                    <div id="gpsInfo" class="px-3 py-2 bg-light border-bottom d-none">
                        <div class="row g-2 small">
                            <div class="col-auto"><i class="fa fa-tachometer text-primary"></i> Kecepatan: <strong
                                    id="gpsSpeed">—</strong></div>
                            <div class="col-auto"><i class="fa fa-clock-o text-muted"></i> Update: <strong
                                    id="gpsTime">—</strong></div>
                            <div class="col-12 col-md"><i class="fa fa-map-pin text-muted"></i> <span id="gpsAddress">—</span>
                            </div>
                        </div>
                        <div class="row g-2 small mt-1 pt-1 border-top">
                            <div class="col-6 col-md-3"><i class="fa fa-user text-secondary"></i> Driver: <strong
                                    id="gpsDriverName">—</strong></div>
                            <div class="col-6 col-md-3"><i class="fa fa-info-circle text-secondary"></i> Status: <strong
                                    id="gpsStatusEng">—</strong></div>
                            <div class="col-6 col-md-3"><i class="fa fa-phone text-secondary"></i> <span id="gpsPhone">—</span></div>
                            <div class="col-6 col-md-3 text-break"><i class="fa fa-mobile text-secondary"></i> IMEI: <span
                                    id="gpsImei">—</span></div>
                        </div>
                    </div>
                    <div id="gpsLoading" class="text-center py-5">
                        <i class="fa fa-spinner fa-spin fa-2x text-muted"></i>
                        <div class="text-muted mt-2 small">Mengambil posisi GPS...</div>
                    </div>
                    <div id="gpsError" class="text-center py-5 d-none">
                        <i class="fa fa-exclamation-triangle fa-2x text-warning"></i>
                        <div class="text-muted mt-2 small" id="gpsErrorMsg">Kendaraan tidak ditemukan di GPS tracker.
                        </div>
                    </div>
                    <div id="gpsLegend" class="px-3 py-1 border-bottom small d-none bg-white text-muted">
                        <span class="text-danger me-3"><i class="fa fa-flag"></i> Merah: sudah tiba di marker (Idtrack)</span>
                        <span class="text-primary"><i class="fa fa-truck"></i> Biru: posisi kendaraan sekarang</span>
                    </div>
                    <div id="gpsMapWrap" style="height:380px; display:none;">
                        <div id="gpsMap" style="height:100%;"></div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <a id="gpsOpenFull" href="{{ route('gps.map') }}" target="_blank"
                        class="btn btn-sm btn-outline-primary">
                        <i class="fa fa-external-link"></i> Buka Peta Penuh
                    </a>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Assign GPS --}}
    <div class="modal fade" id="modalAssignGps" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title"><i class="fa fa-satellite-dish text-warning"></i> Assign GPS Device — <span
                            id="assignNopol"></span></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formAssignGps">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">GPS Device <span class="text-danger">*</span></label>
                            <select id="assignDeviceId" class="form-select" required>
                                <option value="">-- Pilih GPS Device --</option>
                            </select>
                            <div class="form-text">Pilih GPS device yang akan di-assign ke kendaraan ini</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan (opsional)</label>
                            <textarea id="assignCatatan" class="form-control" rows="2" placeholder="Catatan tambahan..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-warning">
                            <i class="fa fa-check"></i> Assign GPS
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('css')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endpush

    @push('js')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script src="{{ asset('js/gps-auto-assign.js') }}"></script>
        <script>
            var gpsMap = null;
            var gpsMarker = null;
            var gpsVisitMarkers = [];

            function clearGpsVisitMarkers() {
                if (!gpsMap) return;
                gpsVisitMarkers.forEach(function(m) {
                    gpsMap.removeLayer(m);
                });
                gpsVisitMarkers = [];
            }

            $(document).on('click', '.btn-lihat-gps', function() {
                var nopol = $(this).data('nopol');
                var kendaraanId = $(this).data('kendaraan-id');
                $('#gpsNopol').text(nopol);
                $('#gpsLoading').show();
                $('#gpsError').addClass('d-none');
                $('#gpsMapWrap').hide();
                $('#gpsLegend').addClass('d-none');
                $('#gpsInfo').addClass('d-none');

                var modal = new bootstrap.Modal(document.getElementById('modalGps'));
                modal.show();

                var reqData = {
                    nopol: nopol
                };
                if (kendaraanId) {
                    reqData.po_kendaraan_id = kendaraanId;
                }

                $.getJSON('{{ route('gps.position-by-nopol') }}', reqData)
                    .done(function(res) {
                        $('#gpsLoading').hide();
                        if (!res.success || !res.lat || !res.lng) {
                            $('#gpsErrorMsg').text(res.message || 'Posisi tidak tersedia.');
                            $('#gpsError').removeClass('d-none');
                            return;
                        }

                        $('#gpsSpeed').text(res.speed != null ? res.speed + ' km/h' : '—');
                        $('#gpsTime').text(res.gps_time || '—');
                        $('#gpsAddress').text(res.address || '—');
                        $('#gpsDriverName').text(res.DriverName || res.driverName || '—');
                        $('#gpsStatusEng').text(res.statusEng || res.StatusEng || '—');
                        $('#gpsPhone').text(res.phone || '—');
                        $('#gpsImei').text(res.imei || '—');
                        $('#gpsInfo').removeClass('d-none');

                        $('#gpsMapWrap').show();
                        $('#gpsLegend').removeClass('d-none');

                        if (!gpsMap) {
                            gpsMap = L.map('gpsMap').setView([res.lat, res.lng], 15);
                            L.tileLayer(
                                'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                                    attribution: 'Tiles &copy; Esri',
                                    maxZoom: 19,
                                }).addTo(gpsMap);
                        } else {
                            clearGpsVisitMarkers();
                            gpsMap.setView([res.lat, res.lng], 15);
                        }

                        var visitIcon = L.divIcon({
                            html: '<div style="background:#b91c1c;color:#fff;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.35);font-size:13px;"><i class="fa fa-flag"></i></div>',
                            className: '',
                            iconSize: [28, 28],
                            iconAnchor: [14, 14],
                            popupAnchor: [0, -16],
                        });

                        (res.visited_markers || []).forEach(function(v) {
                            if (v.lat == null || v.lng == null) return;
                            var label = v.name || ('Marker #' + v.idtrack_marker_id);
                            var when = v.arrived_at ? new Date(v.arrived_at).toLocaleString('id-ID') : '';
                            var vm = L.marker([v.lat, v.lng], {
                                    icon: visitIcon
                                })
                                .addTo(gpsMap)
                                .bindPopup('<strong class="text-danger">' + label + '</strong><br><span class="small text-muted">Tiba: ' +
                                    (when || '—') + '</span>');
                            gpsVisitMarkers.push(vm);
                        });

                        var truckIcon = L.divIcon({
                            html: `<div style="background:#1d4ed8;color:#fff;border-radius:50%;width:36px;height:36px;
                                        display:flex;align-items:center;justify-content:center;
                                        border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.4);font-size:16px;">
                                        <i class="fa fa-truck"></i></div>`,
                            className: '',
                            iconSize: [36, 36],
                            iconAnchor: [18, 18],
                            popupAnchor: [0, -20],
                        });

                        var driverLine = (res.DriverName || res.driverName) ?
                            ('Driver: <b>' + (res.DriverName || res.driverName) + '</b><br>') : '';
                        var statusLine = (res.statusEng || res.StatusEng) ?
                            ('Status: <b>' + (res.statusEng || res.StatusEng) + '</b><br>') : '';
                        var phoneLine = res.phone ? ('Telp: ' + res.phone + '<br>') : '';
                        var imeiLine = res.imei ? ('IMEI: <span class="small">' + res.imei + '</span><br>') : '';

                        var popupContent =
                            `<strong>${nopol}</strong> <span class="badge bg-primary">Sekarang</span><br>
                            ${driverLine}${statusLine}${phoneLine}${imeiLine}
                            ${res.speed != null ? 'Kecepatan: <b>' + res.speed + ' km/h</b><br>' : ''}
                            ${res.address ? '<span class="text-muted small">' + res.address + '</span><br>' : ''}
                            ${res.gps_time ? '<span class="text-muted small">Update: ' + res.gps_time + '</span>' : ''}`;

                        if (gpsMarker) {
                            gpsMarker.setLatLng([res.lat, res.lng]).setIcon(truckIcon).setPopupContent(popupContent);
                        } else {
                            gpsMarker = L.marker([res.lat, res.lng], {
                                    icon: truckIcon
                                })
                                .addTo(gpsMap)
                                .bindPopup(popupContent)
                                .openPopup();
                        }

                        var bounds = L.latLngBounds([
                            [res.lat, res.lng]
                        ]);
                        (res.visited_markers || []).forEach(function(v) {
                            if (v.lat != null && v.lng != null) bounds.extend([v.lat, v.lng]);
                        });
                        if ((res.visited_markers || []).length > 0) {
                            gpsMap.fitBounds(bounds.pad(0.2));
                        }

                        // Invalidate size karena modal baru dibuka
                        setTimeout(function() {
                            gpsMap.invalidateSize();
                        }, 200);
                    })
                    .fail(function(xhr) {
                        $('#gpsLoading').hide();
                        var msg = xhr.responseJSON?.message || 'Gagal mengambil data GPS.';
                        $('#gpsErrorMsg').text(msg);
                        $('#gpsError').removeClass('d-none');
                    });
            });

            // Reset peta saat modal ditutup
            document.getElementById('modalGps').addEventListener('hidden.bs.modal', function() {
                if (gpsMap) {
                    gpsMap.remove();
                    gpsMap = null;
                    gpsMarker = null;
                    gpsVisitMarkers = [];
                }
            });

            // ── GPS Assignment ────────────────────────────────────────
            var currentKendaraanId = null;

            // Button: Assign GPS
            $(document).on('click', '.btn-assign-gps', function() {
                currentKendaraanId = $(this).data('kendaraan-id');
                var nopol = $(this).data('nopol');

                $('#assignNopol').text(nopol);
                $('#assignDeviceId').html('<option value="">Loading...</option>');
                $('#assignCatatan').val('');

                var modal = new bootstrap.Modal(document.getElementById('modalAssignGps'));
                modal.show();

                // Load GPS devices
                $.getJSON('{{ route('gps.devices') }}', function(res) {
                    if (res.success && res.devices) {
                        var options = '<option value="">-- Pilih GPS Device --</option>';
                        res.devices.forEach(function(device) {
                            var deviceId = device.DeviceID || device.device_id;
                            var deviceName = device.DeviceName || device.device_name || device.Nopol ||
                                'Device ' + deviceId;
                            var isAssigned = device.is_assigned;
                            var disabled = isAssigned ? ' disabled' : '';
                            var badge = isAssigned ? ' (Sudah di-assign)' : '';
                            options += '<option value="' + deviceId + '"' + disabled + '>' +
                                deviceName + badge + '</option>';
                        });
                        $('#assignDeviceId').html(options);
                    } else {
                        $('#assignDeviceId').html('<option value="">Tidak ada device tersedia</option>');
                    }
                }).fail(function() {
                    $('#assignDeviceId').html('<option value="">Gagal memuat device</option>');
                });
            });

            // Form: Submit Assign GPS
            $('#formAssignGps').on('submit', function(e) {
                e.preventDefault();

                var deviceId = $('#assignDeviceId').val();
                var catatan = $('#assignCatatan').val();

                if (!deviceId) {
                    alertify.error('Pilih GPS device terlebih dahulu');
                    return;
                }

                $.ajax({
                    url: '/gps/kendaraan/' + currentKendaraanId + '/assign',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        device_id: deviceId,
                        catatan: catatan
                    },
                    success: function(res) {
                        if (res.success) {
                            alertify.success(res.message || 'GPS berhasil di-assign');
                            bootstrap.Modal.getInstance(document.getElementById('modalAssignGps')).hide();
                            setTimeout(function() {
                                location.reload();
                            }, 700);
                        } else {
                            alertify.error(res.message || 'Gagal assign GPS');
                        }
                    },
                    error: function(xhr) {
                        var msg = xhr.responseJSON?.message || 'Gagal assign GPS';
                        alertify.error(msg);
                    }
                });
            });

            // Button: Unassign GPS
            $(document).on('click', '.btn-unassign-gps', function() {
                var kendaraanId = $(this).data('kendaraan-id');
                var nopol = $(this).data('nopol');

                alertify.confirm(
                    'Lepas GPS',
                    'Yakin ingin melepas GPS dari kendaraan <strong>' + nopol + '</strong>?',
                    function() {
                        $.ajax({
                            url: '/gps/kendaraan/' + kendaraanId + '/unassign',
                            method: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(res) {
                                if (res.success) {
                                    alertify.success(res.message || 'GPS berhasil dilepas');
                                    setTimeout(function() {
                                        location.reload();
                                    }, 700);
                                } else {
                                    alertify.error(res.message || 'Gagal melepas GPS');
                                }
                            },
                            error: function(xhr) {
                                var msg = xhr.responseJSON?.message || 'Gagal melepas GPS';
                                alertify.error(msg);
                            }
                        });
                    },
                    function() {}
                ).set('labels', {
                    ok: 'Ya, Lepas',
                    cancel: 'Batal'
                });
            });
        </script>
    @endpush
@endsection
