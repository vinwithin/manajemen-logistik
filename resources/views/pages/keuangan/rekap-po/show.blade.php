@extends('layout.app')
@section('content')
    {{-- Header Card --}}
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center py-3">
            <div>
                <h5 class="mb-1 fw-bold">Rekap PO — {{ $po->no_po }}</h5>
                <span class="text-muted small">
                    {{ $po->cv?->nama_cv ?? '-' }}
                    &nbsp;·&nbsp;
                    {{ $po->tanggal_po->format('d M Y') }}
                    &nbsp;·&nbsp;
                    @if ($po->status === 'locked')
                        <span class="badge bg-success">Locked</span>
                    @else
                        <span class="badge bg-secondary">Draft</span>
                    @endif
                </span>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('rekap-po.export', encrypt($po->id)) }}" class="btn btn-sm btn-success">
                    <i class="fa fa-file-excel-o"></i> Export Excel
                </a>
                <a href="{{ route('purchase-order.show', encrypt($po->id)) }}" class="btn btn-sm btn-secondary">
                    <i class="fa fa-arrow-left"></i> Kembali
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

    {{-- Section 1: Rekap Supplier (OA) --}}
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0"><i class="fa fa-handshake-o"></i> Rekap Supplier (OA)</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width:40px">#</th>
                            <th>Kendaraan</th>
                            <th>Nama Penerima</th>
                            <th class="text-end">Total KG</th>
                            <th class="text-end">Total OA (Rp)</th>
                            <th class="text-center">Status Bayar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $no = 1; @endphp
                        @forelse ($po->kendaraans as $kendaraan)
                            @foreach ($kendaraan->penerimas as $penerima)
                                <tr>
                                    <td class="text-center">{{ $no++ }}</td>
                                    <td>
                                        <span class="fw-semibold">{{ $kendaraan->no_polisi }}</span>
                                        @if ($kendaraan->supplier)
                                            <span class="text-muted small ms-1">· {{ $kendaraan->supplier->initial }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $penerima->nama_penerima }}</td>
                                    <td class="text-end">{{ number_format($penerima->total_kg, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($penerima->total_oa, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        @php $oaStatus = $penerima->oaPayment?->status; @endphp
                                        @if ($oaStatus === 'lunas')
                                            <span class="badge bg-success">Lunas</span>
                                        @elseif ($oaStatus === 'partial')
                                            <span class="badge bg-warning text-dark">Bayar Sebagian</span>
                                        @else
                                            <span class="badge bg-secondary">Belum Bayar</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Belum ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-dark">
                            <td colspan="4" class="fw-bold">Grand Total OA</td>
                            <td class="text-end fw-bold">Rp {{ number_format($grandTotalOa, 0, ',', '.') }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- Section 2: Rekap PT SUM --}}
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0"><i class="fa fa-building-o"></i> Rekap PT SUM</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width:40px">#</th>
                            <th>Kendaraan</th>
                            <th>Nama Penerima</th>
                            <th class="text-end">Total KG</th>
                            <th class="text-end">Total PT SUM (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $no = 1; @endphp
                        @forelse ($po->kendaraans as $kendaraan)
                            @foreach ($kendaraan->penerimas as $penerima)
                                <tr>
                                    <td class="text-center">{{ $no++ }}</td>
                                    <td>{{ $kendaraan->no_polisi }}</td>
                                    <td>{{ $penerima->nama_penerima }}</td>
                                    <td class="text-end">{{ number_format($penerima->total_kg, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($penerima->total_pt_sum, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">Belum ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-dark">
                            <td colspan="4" class="fw-bold">Grand Total PT SUM</td>
                            <td class="text-end fw-bold">Rp {{ number_format($grandTotalPtSum, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
