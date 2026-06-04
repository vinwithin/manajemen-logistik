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
                    <a href="{{ route('transfer-pakan.export-ptsum-confirm') }}?cv_id={{ $header->cv_id }}"
                        class="btn btn-sm btn-warning">
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
        <div class="card mb-3">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <div>
                    <span class="fw-bold"><i class="fa fa-truck"></i> {{ $kendaraan->no_polisi }}</span>
                    @if ($kendaraan->nama_sopir)
                        <span class="text-muted small ms-2">· {{ $kendaraan->nama_sopir }}</span>
                    @endif
                </div>
                <span class="badge bg-primary">{{ number_format($kendaraan->total_kg, 0, ',', '.') }} kg</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Penerima</th>
                                <th>No. SJ</th>
                                <th>Tujuan</th>
                                @foreach ($kodePakanList as $kp)
                                    <th class="text-center">{{ $kp->kode }}</th>
                                @endforeach
                                <th class="text-end">Total KG</th>
                                <th class="text-end">Total PT Sum</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($kendaraan->penerimas as $pi => $penerima)
                                @php $pakanMap = $penerima->pakans->keyBy('kode_pakan_id'); @endphp
                                <tr>
                                    <td>{{ $pi + 1 }}</td>
                                    <td><strong>{{ $penerima->nama_penerima }}</strong></td>
                                    <td class="small text-muted">{{ $penerima->no_surat_jalan ?? '-' }}</td>
                                    <td>{{ $penerima->tujuan?->nama ?? '-' }}</td>
                                    @foreach ($kodePakanList as $kp)
                                        @php $pk = $pakanMap[$kp->id] ?? null; @endphp
                                        <td class="text-center">
                                            {{ $pk ? number_format($pk->jumlah_karung, 0, ',', '.') : '—' }}
                                        </td>
                                    @endforeach
                                    <td class="text-end">{{ number_format($penerima->total_kg, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($penerima->total_pt_sum, 0, ',', '.') }}</td>
                                    <td class="text-center">
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
                                        @if ($penerima->tiba_at)
                                            <div class="small text-muted mt-1" style="font-size:10px;">
                                                {{ \Carbon\Carbon::parse($penerima->tiba_at)->format('d/m/Y') }}
                                            </div>
                                        @endif
                                        @if ($penerima->validasi_oleh)
                                            <div class="small text-muted" style="font-size:10px;">
                                                <i class="fa fa-user"></i> {{ $penerima->validasi_oleh }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($penerima->status === 'pending')
                                            <button class="btn btn-xs btn-info text-white btn-tiba"
                                                data-id="{{ encrypt($penerima->id) }}"
                                                data-nama="{{ $penerima->nama_penerima }}">
                                                <i class="fa fa-map-marker"></i> Tiba
                                            </button>
                                        @elseif ($penerima->status === 'tiba')
                                            <button class="btn btn-xs btn-success btn-selesai"
                                                data-id="{{ encrypt($penerima->id) }}"
                                                data-nama="{{ $penerima->nama_penerima }}">
                                                <i class="fa fa-check"></i> Selesai
                                            </button>
                                        @else
                                            <span class="text-muted small">✓</span>
                                        @endif
                                    </td>
                                </tr>

                                {{-- Tim Bongkar --}}
                                @if ($penerima->tims->count())
                                    <tr class="table-light">
                                        <td colspan="{{ 5 + $kodePakanList->count() }}" class="small text-muted ps-4">
                                            <strong>Tim Bongkar:</strong>
                                            @foreach ($penerima->tims as $tim)
                                                {{ $tim->nama_tim }} ({{ number_format($tim->jumlah_kg, 0, ',', '.') }} kg
                                                @if ($tim->upah_per_kg)
                                                    · Rp {{ number_format($tim->upah_per_kg, 0, ',', '.') }}/kg
                                                @endif)
                                                @if (!$loop->last)
                                                    ,
                                                @endif
                                            @endforeach
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
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
