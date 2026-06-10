@extends('layout.app')
@section('content')
    {{-- Header Info --}}
    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h5 class="mb-1 fw-bold">
                        <i class="fa fa-exchange text-primary"></i> {{ $header->no_transfer }}
                    </h5>
                    <div class="text-muted small mb-1">
                        {{ $header->tanggal_transfer->format('d M Y') }}
                        &nbsp;·&nbsp; <strong>{{ $header->cv?->nama_cv ?? '-' }}</strong>
                        @if ($header->tujuan)
                            &nbsp;·&nbsp; Tujuan: <strong>{{ $header->tujuan->nama }}</strong>
                        @endif
                    </div>
                    <div class="small">
                        <span class="badge bg-secondary">Pengirim: {{ $header->nama_pengirim ?? '-' }}</span>
                    </div>
                    @if ($header->catatan)
                        <div class="text-muted small mt-1"><i class="fa fa-sticky-note"></i> {{ $header->catatan }}</div>
                    @endif
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <a href="{{ route('transfer-pakan.edit', encrypt($header->id)) }}" class="btn btn-sm btn-warning">
                        <i class="fa fa-edit"></i> Edit
                    </a>
                    <form action="{{ route('transfer-pakan.destroy', encrypt($header->id)) }}" method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus transfer pakan ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="fa fa-trash"></i> Hapus
                        </button>
                    </form>
                    <a href="{{ route('transfer-pakan.export-ptsum-confirm') }}?cv_id={{ $header->cv_id }}"
                        class="btn btn-sm btn-primary">
                        <i class="fa fa-file-pdf-o"></i> PDF PT Sum
                    </a>
                    <a href="{{ route('transfer-pakan.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fa fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger py-2">{{ session('error') }}</div>
    @endif

    {{-- Summary --}}
    @php
        $allPenerimas = $header->kendaraans->flatMap(fn($k) => $k->penerimas);
        $grandTotalKg = $allPenerimas->sum('total_kg');
        $grandTotalOa = $allPenerimas->sum('total_oa');
        $grandTotalPtSum = $allPenerimas->sum('total_pt_sum');
    @endphp
    <div class="card mb-3 border-dark">
        <div class="card-body py-2">
            <div class="row g-3 text-center">
                <div class="col-md-3">
                    <div class="text-muted small">Total Kendaraan</div>
                    <div class="fw-bold fs-5">{{ $header->kendaraans->count() }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Total Penerima</div>
                    <div class="fw-bold fs-5">{{ $allPenerimas->count() }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Grand Total KG</div>
                    <div class="fw-bold fs-5">{{ number_format($grandTotalKg, 0, ',', '.') }} kg</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Grand Total PT Sum</div>
                    <div class="fw-bold fs-5 text-success">Rp {{ number_format($grandTotalPtSum, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Kendaraan & Penerima --}}
    @foreach ($header->kendaraans as $ki => $kendaraan)
        <div class="card mb-3 border-info">
            <div class="card-header text-dark py-2">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="mb-0">
                        <i class="fa fa-truck"></i> Kendaraan {{ $ki + 1 }}: <strong>{{ $kendaraan->no_polisi }}</strong>
                        @if ($kendaraan->nama_sopir)
                            <span class="fw-normal opacity-75">({{ $kendaraan->nama_sopir }})</span>
                        @endif
                    </h6>
                    <div class="d-flex gap-1 flex-wrap">
                        <span class="badge bg-warning text-dark">{{ number_format($kendaraan->total_kg, 0, ',', '.') }} kg</span>
                        <span class="badge bg-light text-dark">{{ number_format($kendaraan->total_karung, 0, ',', '.') }} karung</span>
                    </div>
                </div>
            </div>

            <div class="card-body p-3">
                @forelse ($kendaraan->penerimas as $pi => $penerima)
                    @php
                        $totalOaPenerima = $penerima->pakans->sum(fn($pk) => $pk->jumlah_kg * $pk->ongkos_oa);
                        $totalUpahPenerima = $penerima->tims->sum('total_upah');
                    @endphp
                    <div class="card mb-3 {{ $loop->last ? 'mb-0' : '' }}">
                        <div class="card-header py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="fw-bold">
                                    <i class="fa fa-user text-primary"></i> {{ $penerima->nama_penerima }}
                                </span>
                                @if ($penerima->tujuan)
                                    <span class="text-muted small">→ {{ $penerima->tujuan->nama }}</span>
                                @endif
                                @if ($penerima->no_surat_jalan)
                                    <span class="badge bg-light text-dark">SJ: {{ $penerima->no_surat_jalan }}</span>
                                @endif
                                @php
                                    $badge = match ($penerima->status) {
                                        'tiba' => 'info',
                                        'selesai' => 'success',
                                        default => 'secondary',
                                    };
                                    $label = match ($penerima->status) {
                                        'tiba' => 'Tiba',
                                        'selesai' => 'Selesai',
                                        default => 'Pending',
                                    };
                                @endphp
                                <span class="badge bg-{{ $badge }}">{{ $label }}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="badge bg-primary">{{ number_format($penerima->total_kg, 0, ',', '.') }} kg</span>

                                @if ($penerima->status === 'pending')
                                    <button class="btn btn-xs btn-info text-white btn-tiba"
                                        data-id="{{ encrypt($penerima->id) }}"
                                        data-nama="{{ $penerima->nama_penerima }}">
                                        <i class="fa fa-map-marker"></i> Tiba
                                    </button>
                                @elseif ($penerima->status === 'tiba')
                                    <form action="{{ route('transfer-pakan.penerima.update-status', encrypt($penerima->id)) }}"
                                        method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="status" value="selesai">
                                        <button type="submit" class="btn btn-xs btn-success"
                                            onclick="return confirm('Tandai penerima ini sebagai Selesai?')">
                                            <i class="fa fa-check-circle"></i> Selesai
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        <div class="card-body p-0">
                            @if (in_array($penerima->status, ['tiba', 'selesai']))
                                <div class="px-3 py-2 bg-light border-bottom">
                                    <div class="row g-2 small">
                                        <div class="col-md-4">
                                            <span class="text-muted">Waktu Tiba:</span>
                                            <strong class="ms-1">{{ $penerima->tiba_at?->format('d/m/Y H:i') ?? '-' }}</strong>
                                        </div>
                                        <div class="col-md-4">
                                            <span class="text-muted">Validasi Oleh:</span>
                                            <strong class="ms-1">{{ $penerima->validasi_oleh ?? '-' }}</strong>
                                        </div>
                                        <div class="col-md-4">
                                            @if ($penerima->bukti_tiba)
                                                <a href="{{ asset('storage/' . $penerima->bukti_tiba) }}" target="_blank"
                                                    class="btn btn-xs btn-outline-primary">
                                                    <i class="fa fa-image"></i> Lihat Bukti Tiba
                                                </a>
                                            @else
                                                <span class="text-muted small">Tidak ada bukti tiba</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if ($penerima->pakans->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="40px">#</th>
                                                <th>Kode Pakan</th>
                                                <th class="text-end">Jumlah (kg)</th>
                                                <th class="text-end">Karung</th>
                                                <th class="text-end">Ongkos OA (Rp/kg)</th>
                                                <th class="text-end">Total OA</th>
                                                <th class="text-end">Harga PT Sum (Rp/kg)</th>
                                                <th class="text-end">Total PT Sum</th>
                                                <th>Keterangan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($penerima->pakans as $idx => $pakan)
                                                <tr>
                                                    <td class="text-center text-muted">{{ $idx + 1 }}</td>
                                                    <td><strong>{{ $pakan->kodePakan?->kode ?? '-' }}</strong></td>
                                                    <td class="text-end">
                                                        {{ number_format($pakan->jumlah_kg, 0, ',', '.') }}
                                                    </td>
                                                    <td class="text-end">
                                                        {{ number_format($pakan->jumlah_karung, 0, ',', '.') }}
                                                    </td>
                                                    <td class="text-end">
                                                        @if ($pakan->ongkos_oa > 0)
                                                            Rp {{ number_format($pakan->ongkos_oa, 0, ',', '.') }}
                                                        @else
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-end">
                                                        @php $totalOaPakan = $pakan->jumlah_kg * ($pakan->ongkos_oa ?? 0); @endphp
                                                        @if ($totalOaPakan > 0)
                                                            Rp {{ number_format($totalOaPakan, 0, ',', '.') }}
                                                        @else
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-end">
                                                        @if (($pakan->harga_pt_sum ?? 0) > 0)
                                                            Rp {{ number_format($pakan->harga_pt_sum, 0, ',', '.') }}
                                                        @else
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-end">
                                                        @php $totalPtSumPakan = $pakan->jumlah_kg * ($pakan->harga_pt_sum ?? 0); @endphp
                                                        @if ($totalPtSumPakan > 0)
                                                            Rp {{ number_format($totalPtSumPakan, 0, ',', '.') }}
                                                        @else
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-muted small">{{ $pakan->keterangan ?? '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="table-light fw-bold">
                                            <tr>
                                                <td colspan="2" class="text-end">Total</td>
                                                <td class="text-end">{{ number_format($penerima->total_kg, 0, ',', '.') }}</td>
                                                <td class="text-end">{{ number_format($penerima->total_karung, 0, ',', '.') }}</td>
                                                <td></td>
                                                <td class="text-end text-primary">Rp {{ number_format($totalOaPenerima, 0, ',', '.') }}</td>
                                                <td></td>
                                                <td class="text-end text-success">Rp {{ number_format($penerima->total_pt_sum, 0, ',', '.') }}</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @else
                                <div class="text-center text-muted py-3 small">Tidak ada data pakan.</div>
                            @endif

                            {{-- Tim Bongkar --}}
                            @if ($penerima->tims->count() > 0)
                                <div class="border-top">
                                    <div class="px-3 pt-2 pb-1">
                                        <span class="small fw-semibold text-muted">
                                            <i class="fa fa-users"></i> Tim Bongkar
                                        </span>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered mb-0 align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Nama Tim</th>
                                                    <th class="text-end">Jumlah (kg)</th>
                                                    <th class="text-end">Karung</th>
                                                    <th class="text-end">Upah (Rp/kg)</th>
                                                    <th class="text-end">Total Upah</th>
                                                    <th>Keterangan</th>
                                                    <th class="text-center">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($penerima->tims as $tim)
                                                    <tr>
                                                        <td>{{ $tim->nama_tim }}</td>
                                                        <td class="text-end">{{ number_format($tim->jumlah_kg, 0, ',', '.') }}</td>
                                                        <td class="text-end">{{ $tim->jumlah_karung ? number_format($tim->jumlah_karung, 0, ',', '.') : '—' }}</td>
                                                        <td class="text-end">
                                                            @if ($tim->upah_per_kg > 0)
                                                                Rp {{ number_format($tim->upah_per_kg, 0, ',', '.') }}
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-end fw-bold">
                                                            @if ($tim->total_upah > 0)
                                                                Rp {{ number_format($tim->total_upah, 0, ',', '.') }}
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-muted small">{{ $tim->keterangan ?? '—' }}</td>
                                                        <td class="text-center">
                                                            <form action="{{ route('transfer-pakan.tim.destroy', encrypt($tim->id)) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus tim ini?')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            @if ($penerima->tims->count() > 1)
                                                <tfoot class="table-light fw-bold">
                                                    <tr>
                                                        <td colspan="4" class="text-end">Total Upah Tim</td>
                                                        <td class="text-end text-success">Rp {{ number_format($totalUpahPenerima, 0, ',', '.') }}</td>
                                                        <td></td>
                                                        <td></td>
                                                    </tr>
                                                </tfoot>
                                            @endif
                                        </table>
                                    </div>
                                </div>
                            @endif

                            {{-- Subtotal Penerima --}}
                            <div class="d-flex justify-content-between align-items-center px-3 py-2 bg-light border-top small fw-semibold text-muted">
                                <span>{{ number_format($penerima->total_kg, 0, ',', '.') }} kg · {{ number_format($penerima->total_karung, 0, ',', '.') }} karung</span>
                                <span>
                                    OA: <span class="text-primary">Rp {{ number_format($totalOaPenerima, 0, ',', '.') }}</span>
                                    @if ($totalUpahPenerima > 0)
                                        · Angkut: <span class="text-success">Rp {{ number_format($totalUpahPenerima, 0, ',', '.') }}</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="alert alert-info m-0">Belum ada penerima dalam kendaraan ini.</div>
                @endforelse

                {{-- Total per Kendaraan --}}
                @if ($kendaraan->penerimas->count() > 0)
                    @php
                        $oaKendaraan = $kendaraan->penerimas->sum(fn($p) => $p->pakans->sum(fn($pk) => $pk->jumlah_kg * $pk->ongkos_oa));
                        $upahKendaraan = $kendaraan->penerimas->flatMap->tims->sum('total_upah');
                    @endphp
                    <div class="mt-3 p-2 bg-light rounded border d-flex justify-content-between align-items-center small fw-semibold">
                        <span class="text-muted">Total Kendaraan {{ $ki + 1 }}</span>
                        <span>
                            <span class="text-dark">{{ number_format($kendaraan->total_kg, 0, ',', '.') }} kg</span>
                            · OA: <span class="text-primary">Rp {{ number_format($oaKendaraan, 0, ',', '.') }}</span>
                            @if ($upahKendaraan > 0)
                                · Angkut: <span class="text-success">Rp {{ number_format($upahKendaraan, 0, ',', '.') }}</span>
                            @endif
                        </span>
                    </div>
                @endif
            </div>
        </div>
    @endforeach

    {{-- Modal Tiba --}}
    <div class="modal fade" id="modalTiba" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title">Tandai Tiba — <span id="tibaNama"></span></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formTiba" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="status" value="tiba">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label form-label-sm">Tanggal Tiba <span class="text-danger">*</span></label>
                            <input type="date" name="tiba_at" class="form-control form-control-sm"
                                value="{{ now()->format('Y-m-d') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label form-label-sm">Nama Validator <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="nama_validator" class="form-control form-control-sm"
                                placeholder="Nama petugas penerima" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label form-label-sm">Bukti Tiba (opsional)</label>
                            <input type="file" name="bukti_tiba" class="form-control form-control-sm"
                                accept=".jpg,.jpeg,.png">
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="submit" class="btn btn-sm btn-info text-white">Konfirmasi</button>
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Selesai --}}
    <div class="modal fade" id="modalSelesai" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title">Tandai Selesai — <span id="selesaiNama"></span></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formSelesai">
                    @csrf
                    <input type="hidden" name="status" value="selesai">
                    <div class="modal-body">
                        <p class="text-muted small">Konfirmasi penerima sudah selesai menerima pakan?</p>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="submit" class="btn btn-sm btn-success">Konfirmasi</button>
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        var baseUrl = '{{ route('transfer-pakan.penerima.update-status', '__ID__') }}';

        $('.btn-tiba').on('click', function() {
            var id = $(this).data('id');
            $('#tibaNama').text($(this).data('nama'));
            $('#formTiba').attr('action', baseUrl.replace('__ID__', id));
            new bootstrap.Modal('#modalTiba').show();
        });

        $('.btn-selesai').on('click', function() {
            var id = $(this).data('id');
            $('#selesaiNama').text($(this).data('nama'));
            $('#formSelesai').attr('action', baseUrl.replace('__ID__', id));
            new bootstrap.Modal('#modalSelesai').show();
        });
    </script>
@endsection
