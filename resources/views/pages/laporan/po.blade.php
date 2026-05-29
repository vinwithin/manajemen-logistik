@extends('layout.app')
@section('content')

    {{-- Header --}}
    <div class="card mb-3">
        <div class="card-body py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-0 fw-bold"><i class="fa fa-bar-chart text-primary"></i> Laporan Purchase Order</h5>
                <span class="text-muted small">Rekap & statistik PO lintas periode</span>
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('laporan.po') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-2">
                    <label class="form-label small fw-semibold">Dari Tanggal</label>
                    <input type="date" name="dari" class="form-control form-control-sm" value="{{ $dari }}">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small fw-semibold">Sampai Tanggal</label>
                    <input type="date" name="sampai" class="form-control form-control-sm" value="{{ $sampai }}">
                </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label small fw-semibold">CV</label>
                        <select name="cv_id" class="form-select form-select-sm">
                            <option value="">{{ $canSeeAllCv ? 'Semua CV' : 'Semua CV yang diakses' }}</option>
                            @foreach ($userCvs as $cv)
                                <option value="{{ $cv->id }}" {{ $cvId == $cv->id ? 'selected' : '' }}>
                                    {{ $cv->nama_cv }}
                                </option>
                            @endforeach
                        </select>
                    </div>
               
                <div class="col-12 col-md-2">
                    <label class="form-label small fw-semibold">Supplier</label>
                    <select name="supplier_id" class="form-select form-select-sm">
                        <option value="">Semua Supplier</option>
                        @foreach ($supplierList as $s)
                            <option value="{{ $s->id }}" {{ $supplierId == $s->id ? 'selected' : '' }}>
                                {{ $s->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small fw-semibold">Tahun Grafik</label>
                    <select name="tahun" class="form-select form-select-sm">
                        @foreach ($tahunList as $t)
                            <option value="{{ $t }}" {{ $tahun == $t ? 'selected' : '' }}>{{ $t }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fa fa-search"></i> Tampilkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="text-muted small mb-1">Total PO</div>
                    <div class="fw-bold fs-4 text-primary">{{ number_format($totalPo) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="text-muted small mb-1">Total Kendaraan</div>
                    <div class="fw-bold fs-4 text-info">{{ number_format($totalKendaraan) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="text-muted small mb-1">Total Volume</div>
                    <div class="fw-bold fs-4 text-success">{{ number_format($totalVolume, 0, ',', '.') }} kg</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="text-muted small mb-1">Total Nilai PT Sum</div>
                    <div class="fw-bold fs-4 text-warning">Rp {{ number_format($totalPtSum, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Grafik Volume per Bulan --}}
    <div class="card mb-4">
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><i class="fa fa-bar-chart text-primary"></i> Volume Pengiriman per Bulan —
                {{ $tahun }}</h6>
        </div>
        <div class="card-body">
            <canvas id="chartVolume" height="80"></canvas>
        </div>
    </div>

    {{-- Tabel Rekap PO --}}
    <div class="card">
        <div class="card-header py-2">
            <h6 class="mb-0 fw-bold"><i class="fa fa-list text-secondary"></i> Rekap PO
                <span class="text-muted fw-normal small">
                    ({{ $dari }} s/d {{ $sampai }})
                </span>
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="40">#</th>
                            <th>No. PO</th>
                            <th>Tanggal</th>
                            @if ($canSeeAllCv)
                                <th>CV</th>
                            @endif
                            <th>Supplier</th>
                            <th class="text-center">Kendaraan</th>
                            <th class="text-end">Volume (kg)</th>
                            <th class="text-end">Nilai PT Sum</th>
                            <th class="text-end">Total OA</th>
                            <th class="text-center">Status</th>
                            <th width="80">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tablePos as $i => $po)
                            @php
                                $volPo = $po->kendaraans->sum(fn($k) => $k->penerimas->sum('total_kg'));
                                $ptPo = $po->kendaraans->sum(fn($k) => $k->penerimas->sum('total_pt_sum'));
                                $oaPo = $po->kendaraans->sum(fn($k) => $k->penerimas->sum('total_oa'));
                                $suppliers = $po->kendaraans
                                    ->map(fn($k) => $k->supplier?->nama)
                                    ->filter()
                                    ->unique()
                                    ->implode(', ');
                            @endphp
                            <tr>
                                <td class="text-center text-muted">{{ $tablePos->firstItem() + $i }}</td>
                                <td><strong>{{ $po->no_po }}</strong></td>
                                <td>{{ $po->tanggal_po->format('d/m/Y') }}</td>
                                @if ($canSeeAllCv)
                                    <td>{{ $po->cv?->nama_cv ?? '-' }}</td>
                                @endif
                                <td class="text-muted small">{{ $suppliers ?: '-' }}</td>
                                <td class="text-center">{{ $po->kendaraans->count() }}</td>
                                <td class="text-end">{{ number_format($volPo, 0, ',', '.') }}</td>
                                <td class="text-end text-success fw-semibold">
                                    {{ $ptPo > 0 ? 'Rp ' . number_format($ptPo, 0, ',', '.') : '-' }}
                                </td>
                                <td class="text-end text-primary">
                                    {{ $oaPo > 0 ? 'Rp ' . number_format($oaPo, 0, ',', '.') : '-' }}
                                </td>
                                <td class="text-center">
                                    @if ($po->status === 'locked')
                                        <span class="badge bg-success">Terkunci</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Draft</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('purchase-order.show', encrypt($po->id)) }}"
                                        class="btn btn-xs btn-outline-primary py-0 px-1">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canSeeAllCv ? 11 : 10 }}" class="text-center text-muted py-4">
                                    Tidak ada data PO untuk filter yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($tablePos->count() > 0)
                        <tfoot class="table-light fw-semibold">
                            <tr>
                                <td colspan="{{ $canSeeAllCv ? 6 : 5 }}" class="text-end">Total halaman ini</td>
                                <td class="text-end">
                                    {{ number_format($tablePos->sum(fn($po) => $po->kendaraans->sum(fn($k) => $k->penerimas->sum('total_kg'))), 0, ',', '.') }}
                                    kg
                                </td>
                                <td class="text-end text-success">
                                    Rp
                                    {{ number_format($tablePos->sum(fn($po) => $po->kendaraans->sum(fn($k) => $k->penerimas->sum('total_pt_sum'))), 0, ',', '.') }}
                                </td>
                                <td class="text-end text-primary">
                                    Rp
                                    {{ number_format($tablePos->sum(fn($po) => $po->kendaraans->sum(fn($k) => $k->penerimas->sum('total_oa'))), 0, ',', '.') }}
                                </td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
        @if ($tablePos->hasPages())
            <div class="card-footer py-2">
                {{ $tablePos->links() }}
            </div>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        const ctx = document.getElementById('chartVolume').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($chartLabels),
                datasets: [{
                        label: 'Volume (kg)',
                        data: @json($chartVolume),
                        backgroundColor: 'rgba(37, 99, 235, 0.7)',
                        borderColor: 'rgba(37, 99, 235, 1)',
                        borderWidth: 1,
                        yAxisID: 'y',
                    },
                    {
                        label: 'Jumlah PO',
                        data: @json($chartPoCount),
                        type: 'line',
                        borderColor: 'rgba(234, 88, 12, 0.9)',
                        backgroundColor: 'rgba(234, 88, 12, 0.1)',
                        borderWidth: 2,
                        pointRadius: 4,
                        fill: false,
                        yAxisID: 'y2',
                        tension: 0.3,
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                if (ctx.dataset.yAxisID === 'y') {
                                    return ' Volume: ' + Number(ctx.raw).toLocaleString('id-ID') + ' kg';
                                }
                                return ' Jumlah PO: ' + ctx.raw;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Volume (kg)'
                        },
                        ticks: {
                            callback: v => Number(v).toLocaleString('id-ID')
                        }
                    },
                    y2: {
                        type: 'linear',
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Jumlah PO'
                        },
                        grid: {
                            drawOnChartArea: false
                        },
                    }
                }
            }
        });
    </script>
@endsection
