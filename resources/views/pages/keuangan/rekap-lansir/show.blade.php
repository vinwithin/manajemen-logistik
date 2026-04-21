@extends('layout.app')
@section('content')
    {{-- Header --}}
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center py-3">
            <div>
                <h5 class="mb-1 fw-bold">Rekap Lansir — {{ $po->no_po }}</h5>
                <span class="text-muted small">
                    {{ $po->cv?->nama_cv ?? '-' }} &nbsp;·&nbsp; {{ $po->tanggal_po->format('d M Y') }}
                </span>
            </div>
            <div class="d-flex gap-2">
                @can('rekap-lansir.view')
                    <a href="{{ route('rekap-lansir.export', encrypt($po->id)) }}" class="btn btn-sm btn-success">
                        <i class="fa fa-file-excel-o"></i> Export Excel
                    </a>
                @endcan
                <a href="{{ route('purchase-order.show', encrypt($po->id)) }}" class="btn btn-sm btn-secondary">
                    <i class="fa fa-arrow-left"></i> Kembali ke PO
                </a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3">
        {{-- Rekap Mobil --}}
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fa fa-truck"></i> Rekap Mobil Lansir</h6>
                </div>
                <div class="card-body p-0">
                    @if ($rekapMobil->isEmpty())
                        <div class="p-3 text-muted text-center">Belum ada data lansir mobil.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>No. Polisi</th>
                                        <th class="text-end">Berat (kg)</th>
                                        <th class="text-end">Ongkos (Rp/kg)</th>
                                        <th class="text-end">Total (Rp)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rekapMobil as $event)
                                        @if ($event->mobils->isEmpty())
                                            <tr>
                                                <td colspan="4" class="text-muted text-center small">
                                                    Event #{{ $event->id }} — belum ada mobil
                                                </td>
                                            </tr>
                                        @else
                                            @foreach ($event->mobils as $mobil)
                                                <tr>
                                                    <td>{{ $mobil->no_polisi }}</td>
                                                    <td class="text-end">
                                                        {{ number_format($mobil->berat ?? 0, 0, ',', '.') }}</td>
                                                    <td class="text-end">
                                                        {{ number_format($mobil->ongkos ?? 0, 0, ',', '.') }}</td>
                                                    <td class="text-end">
                                                        {{ number_format($mobil->total_ongkos, 0, ',', '.') }}</td>
                                                </tr>
                                            @endforeach
                                        @endif
                                        <tr class="table-secondary">
                                            <td colspan="3" class="fw-semibold small">Subtotal Event
                                                #{{ $event->id }}</td>
                                            <td class="text-end fw-semibold">Rp
                                                {{ number_format($event->total_ongkos, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="table-dark">
                                        <td colspan="3" class="fw-bold">Grand Total</td>
                                        <td class="text-end fw-bold">Rp {{ number_format($grandTotalMobil, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </div>
                <div class="card-footer">
                    @php $statusMobil = $paymentMobil?->status ?? \App\Models\LansirPayment::STATUS_BELUM; @endphp
                    @if ($statusMobil === \App\Models\LansirPayment::STATUS_SUDAH)
                        <span class="badge bg-success fs-6"><i class="fa fa-check-circle"></i> Sudah Bayar</span>
                        <div class="small text-muted mt-1">
                            Tgl: {{ $paymentMobil->tanggal_bayar->format('d/m/Y') }}
                            @if ($paymentMobil->catatan)
                                · {{ $paymentMobil->catatan }}
                            @endif
                            <br>Oleh: {{ $paymentMobil->dibayar_oleh }}
                        </div>
                    @else
                        <span class="badge bg-secondary fs-6"><i class="fa fa-clock-o"></i> Belum Bayar</span>
                        @can('rekap-lansir.bayar')
                            <button class="btn btn-sm btn-primary ms-2 btn-tandai-bayar" data-tipe="mobil"
                                data-bs-toggle="modal" data-bs-target="#modalBayar">
                                <i class="fa fa-money"></i> Tandai Bayar
                            </button>
                        @endcan
                    @endif
                </div>
            </div>
        </div>

        {{-- Rekap Tim --}}
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fa fa-users"></i> Rekap Tim Bongkar</h6>
                </div>
                <div class="card-body p-0">
                    @if ($rekapTim->isEmpty())
                        <div class="p-3 text-muted text-center">Belum ada data lansir tim.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama Tim</th>
                                        <th class="text-end">Berat (kg)</th>
                                        <th class="text-end">Upah (Rp/kg)</th>
                                        <th class="text-end">Total (Rp)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rekapTim as $event)
                                        @php $totalBerat = $event->total_berat; @endphp
                                        @if ($event->tims->isEmpty())
                                            <tr>
                                                <td colspan="4" class="text-muted text-center small">
                                                    Event #{{ $event->id }} — belum ada tim
                                                </td>
                                            </tr>
                                        @else
                                            @foreach ($event->tims as $tim)
                                                <tr>
                                                    <td>{{ $tim->nama_tim }}</td>
                                                    <td class="text-end">{{ number_format($totalBerat, 0, ',', '.') }}</td>
                                                    <td class="text-end">{{ number_format($tim->upah ?? 0, 0, ',', '.') }}
                                                    </td>
                                                    <td class="text-end">
                                                        {{ number_format($totalBerat * ($tim->upah ?? 0), 0, ',', '.') }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                        <tr class="table-secondary">
                                            <td colspan="3" class="fw-semibold small">Subtotal Event
                                                #{{ $event->id }}</td>
                                            <td class="text-end fw-semibold">Rp
                                                {{ number_format($event->total_upah, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="table-dark">
                                        <td colspan="3" class="fw-bold">Grand Total</td>
                                        <td class="text-end fw-bold">Rp {{ number_format($grandTotalTim, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </div>
                <div class="card-footer">
                    @php $statusTim = $paymentTim?->status ?? \App\Models\LansirPayment::STATUS_BELUM; @endphp
                    @if ($statusTim === \App\Models\LansirPayment::STATUS_SUDAH)
                        <span class="badge bg-success fs-6"><i class="fa fa-check-circle"></i> Sudah Bayar</span>
                        <div class="small text-muted mt-1">
                            Tgl: {{ $paymentTim->tanggal_bayar->format('d/m/Y') }}
                            @if ($paymentTim->catatan)
                                · {{ $paymentTim->catatan }}
                            @endif
                            <br>Oleh: {{ $paymentTim->dibayar_oleh }}
                        </div>
                    @else
                        <span class="badge bg-secondary fs-6"><i class="fa fa-clock-o"></i> Belum Bayar</span>
                        @can('rekap-lansir.bayar')
                            <button class="btn btn-sm btn-primary ms-2 btn-tandai-bayar" data-tipe="tim" data-bs-toggle="modal"
                                data-bs-target="#modalBayar">
                                <i class="fa fa-money"></i> Tandai Bayar
                            </button>
                        @endcan
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tandai Bayar --}}
    <div class="modal fade" id="modalBayar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Tandai Pembayaran — <span id="modalBayarTipe"></span></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('rekap-lansir.bayar', encrypt($po->id)) }}">
                    @csrf
                    <input type="hidden" name="tipe" id="inputTipe">
                    <div class="modal-body">
                        @if ($errors->any())
                            <div class="alert alert-danger py-2">
                                <ul class="mb-0 small">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <div class="mb-3">
                            <label class="form-label">Tanggal Bayar <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_bayar"
                                class="form-control @error('tanggal_bayar') is-invalid @enderror"
                                value="{{ old('tanggal_bayar', date('Y-m-d')) }}" required>
                            @error('tanggal_bayar')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea name="catatan" class="form-control @error('catatan') is-invalid @enderror" rows="3"
                                placeholder="Opsional">{{ old('catatan') }}</textarea>
                            @error('catatan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.btn-tandai-bayar').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var tipe = this.dataset.tipe;
                document.getElementById('inputTipe').value = tipe;
                document.getElementById('modalBayarTipe').textContent = tipe === 'mobil' ? 'Mobil Lansir' :
                    'Tim Bongkar';
            });
        });

        @if ($errors->any() && old('tipe'))
            // Re-open modal if there are validation errors
            document.addEventListener('DOMContentLoaded', function() {
                var modal = new bootstrap.Modal(document.getElementById('modalBayar'));
                document.getElementById('inputTipe').value = '{{ old('tipe') }}';
                document.getElementById('modalBayarTipe').textContent =
                    '{{ old('tipe') === 'mobil' ? 'Mobil Lansir' : 'Tim Bongkar' }}';
                modal.show();
            });
        @endif
    </script>
@endsection
