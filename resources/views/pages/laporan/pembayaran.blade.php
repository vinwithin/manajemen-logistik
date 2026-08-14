@extends('layout.app')
@section('content')
    <style>
        .summary-card .card-header {
            min-height: 58px;
        }

        .summary-metric {
            min-width: 0;
        }

        .summary-label {
            color: #6c757d;
            font-size: 11px;
            line-height: 1.2;
        }

        .summary-value {
            font-size: 15px;
            font-weight: 700;
            line-height: 1.25;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .summary-value-main {
            font-size: 18px;
        }

        .summary-status {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 4px;
        }

        @media (min-width: 1400px) {
            .summary-value {
                font-size: 14px;
            }

            .summary-value-main {
                font-size: 17px;
            }
        }
    </style>

    {{-- Header --}}
    <div class="card mb-3">
        <div class="card-body py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-0 fw-bold"><i class="fa fa-money text-success"></i> Laporan Pembayaran</h5>
                <span class="text-muted small">Ringkasan tagihan OA, lansir PO, dan lansir gudang</span>
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('laporan.pembayaran') }}" class="row g-2 align-items-end">
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
                            <option value="">{{$canSeeAllCv ? 'Semua CV' : 'Semua CV (Hanya CV yang diakses)'}}</option>
                            @foreach ($userCvs as $cv)
                                <option value="{{ $cv->id }}" {{ $cvId == $cv->id ? 'selected' : '' }}>
                                    {{ $cv->nama_cv }}
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

    {{-- Ringkasan Cards --}}
    <div class="row g-3 mb-4">

        {{-- OA Payment --}}
        <div class="col-12 col-lg-6 col-xxl-3">
            <div class="card h-100 border-0 shadow-sm summary-card">
                <div class="card-header bg-primary bg-opacity-10 p-3">
                    <h6 class="mb-0 fw-bold text-primary small">
                        <i class="fa fa-credit-card"></i> OA Payment (dari PO)
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2 text-center">
                        <div class="col-12 col-sm-6 summary-metric">
                            <div class="summary-label">Total Tagihan</div>
                            <div class="summary-value text-primary">Rp {{ number_format($oaTotalTagihan, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-12 col-sm-6 summary-metric">
                            <div class="summary-label">Sudah Dibayar</div>
                            <div class="summary-value text-success">Rp {{ number_format($oaTotalBayar, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-12 col-sm-6 summary-metric">
                            <div class="summary-label">Outstanding</div>
                            <div class="summary-value text-danger">Rp {{ number_format($oaTotalSisa, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-12 col-sm-6 summary-metric">
                            <div class="summary-label">Status</div>
                            <div class="summary-status">
                                <span class="badge bg-success">{{ $oaLunas }} Lunas</span>
                                <span class="badge bg-warning text-dark">{{ $oaBelum }} Belum</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent py-2 text-end">
                    <a href="{{ route('keuangan.oa.index') }}" class="small text-primary">
                        Lihat Detail <i class="fa fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        {{-- Lansir PO --}}
        <div class="col-12 col-lg-6 col-xxl-3">
            <div class="card h-100 border-0 shadow-sm summary-card">
                <div class="card-header bg-info bg-opacity-10 p-3">
                    <h6 class="mb-0 fw-bold text-info small">
                        <i class="fa fa-truck"></i> Lansir PO (Mobil & Tim)
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2 text-center">
                        <div class="col-12 summary-metric">
                            <div class="summary-label">Total Tagihan OA Lansir</div>
                            <div class="summary-value text-info">Rp {{ number_format($lansirPoTotalOa, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-12 summary-metric">
                            <div class="summary-label">Total Tagihan Upah Mobil Lokal</div>
                            <div class="summary-value text-info">Rp {{ number_format($lansirPoSudahBayarMobil, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-12 summary-metric">
                            <div class="summary-label">Total Upah Tim Bongkar</div>
                            <div class="summary-value text-info">Rp {{ number_format($lansirPoSudahBayarTim, 0, ',', '.') }}</div>
                        </div>
                        
                    </div>
                </div>
                <div class="card-footer bg-transparent py-2 text-end">
                    <a href="{{ route('rekap-lansir.index') }}" class="small text-info">
                        Lihat Detail <i class="fa fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        {{-- Lansir Gudang --}}
        <div class="col-12 col-lg-6 col-xxl-3">
            <div class="card h-100 border-0 shadow-sm summary-card">
                <div class="card-header bg-warning bg-opacity-10 p-3">
                    <h6 class="mb-0 fw-bold text-warning small">
                        <i class="fa fa-archive"></i> Lansir Gudang
                        <span class="badge bg-secondary fw-normal ms-1" style="font-size:9px;">Informatif</span>
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2 text-center">
                        <div class="col-12 col-sm-6 summary-metric">
                            <div class="summary-label">Ongkos OA</div>
                            <div class="summary-value text-warning">Rp {{ number_format($gudangTotalOa, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-12 col-sm-6 summary-metric">
                            <div class="summary-label">Upah Angkut</div>
                            <div class="summary-value" style="color:#ea580c;">Rp {{ number_format($gudangTotalAngkut, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-12 summary-metric">
                            <div class="summary-label">Total Keseluruhan</div>
                            <div class="summary-value summary-value-main text-dark">Rp {{ number_format($gudangTotalOa + $gudangTotalAngkut, 0, ',', '.') }}</div>
                            <div class="summary-label">dari {{ $gudangHeaders->count() }} lansir
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent py-2 text-end">
                    <a href="{{ route('gudang.lansir.index') }}" class="small text-warning">
                        Lihat Detail <i class="fa fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        {{-- Transfer Pakan --}}
        <div class="col-12 col-lg-6 col-xxl-3">
            <div class="card h-100 border-0 shadow-sm summary-card">
                <div class="card-header bg-success bg-opacity-10 p-3">
                    <h6 class="mb-0 fw-bold text-success small">
                        <i class="fa fa-exchange"></i> Transfer Pakan
                        <span class="badge bg-secondary fw-normal ms-1" style="font-size:9px;">Informatif</span>
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2 text-center">
                        <div class="col-12 col-sm-6 summary-metric">
                            <div class="summary-label">Ongkos OA</div>
                            <div class="summary-value text-success">Rp {{ number_format($transferTotalOa, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-12 col-sm-6 summary-metric">
                            <div class="summary-label">Upah Angkut</div>
                            <div class="summary-value" style="color:#15803d;">Rp {{ number_format($transferTotalAngkut, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-12 summary-metric">
                            <div class="summary-label">Total Keseluruhan</div>
                            <div class="summary-value summary-value-main text-dark">Rp {{ number_format($transferTotalOa + $transferTotalAngkut, 0, ',', '.') }}</div>
                            <div class="summary-label">dari {{ $transferHeaders->count() }} transfer
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent py-2 text-end">
                    <a href="{{ route('transfer-pakan.index') }}" class="small text-success">
                        Lihat Detail <i class="fa fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

    </div>

    {{-- Grafik --}}
    <div class="card mb-4">
        <div class="card-header py-2">
            <h6 class="mb-0 fw-bold"><i class="fa fa-bar-chart text-primary"></i> Tren Tagihan per Bulan —
                {{ $tahun }}</h6>
        </div>
        <div class="card-body">
            <canvas id="chartPembayaran" height="80"></canvas>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        new Chart(document.getElementById('chartPembayaran').getContext('2d'), {
            type: 'bar',
            data: {
                labels: @json($chartLabels),
                datasets: [{
                        label: 'OA Payment (PO)',
                        data: @json($chartOa),
                        backgroundColor: 'rgba(37, 99, 235, 0.7)',
                        borderColor: 'rgba(37, 99, 235, 1)',
                        borderWidth: 1,
                    },
                    {
                        label: 'Ongkos Lansir Gudang',
                        data: @json($chartGudang),
                        backgroundColor: 'rgba(234, 88, 12, 0.7)',
                        borderColor: 'rgba(234, 88, 12, 1)',
                        borderWidth: 1,
                    },
                    {
                        label: 'Ongkos Transfer Pakan',
                        data: @json($chartTransfer),
                        backgroundColor: 'rgba(34, 197, 94, 0.7)',
                        borderColor: 'rgba(34, 197, 94, 1)',
                        borderWidth: 1,
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
                            label: ctx => ' ' + ctx.dataset.label + ': Rp ' + Number(ctx.raw).toLocaleString(
                                'id-ID')
                        }
                    }
                },
                scales: {
                    y: {
                        title: {
                            display: true,
                            text: 'Jumlah (Rp)'
                        },
                        ticks: {
                            callback: v => 'Rp ' + Number(v).toLocaleString('id-ID')
                        }
                    }
                }
            }
        });
    </script>
@endsection
