@extends('layout.app')
@section('content')
    {{-- Summary cards --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-2">
            <div class="card text-center py-3">
                <div class="fw-bold fs-5 text-primary">Rp {{ number_format($summary['total_tagihan'], 0, ',', '.') }}</div>
                <div class="text-muted small">Total Tagihan</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card text-center py-3">
                <div class="fw-bold fs-5 text-success">Rp {{ number_format($summary['total_bayar'], 0, ',', '.') }}</div>
                <div class="text-muted small">Total Dibayar</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card text-center py-3">
                <div class="fw-bold fs-5 text-danger">Rp {{ number_format($summary['total_sisa'], 0, ',', '.') }}</div>
                <div class="text-muted small">Sisa Tagihan</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card text-center py-3">
                <div class="fw-bold fs-5">
                    <span class="text-secondary">{{ $summary['count_pending'] }}</span> /
                    <span class="text-warning">{{ $summary['count_partial'] }}</span> /
                    <span class="text-success">{{ $summary['count_lunas'] }}</span>
                </div>
                <div class="text-muted small">Pending / Sebagian / Lunas</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card text-center py-3">
                <div class="fw-bold fs-5 text-warning">Rp {{ number_format($summary['total_dp'], 0, ',', '.') }}</div>
                <div class="text-muted small">Total DP Supplier</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card text-center py-3">
                <div class="fw-bold fs-5">
                    <span class="text-info">{{ $summary['count_oa'] }}</span> /
                    <span class="text-warning">{{ $summary['count_dp'] }}</span>
                </div>
                <div class="text-muted small">OA / DP</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">Pembayaran Supplier</h5>
            <div class="d-flex gap-2 flex-wrap">
                <select id="filterTipe" class="form-select form-select-sm" style="width:150px">
                    <option value="">Semua Tipe</option>
                    <option value="oa">Pembayaran OA</option>
                    <option value="dp_supplier">DP Supplier</option>
                </select>
                <select id="filterSupplier" class="form-select form-select-sm" style="width:180px">
                    <option value="">Semua Supplier</option>
                    @foreach ($suppliers as $s)
                        <option value="{{ $s->id }}">{{ $s->nama }}</option>
                    @endforeach
                </select>
                <select id="filterStatus" class="form-select form-select-sm" style="width:150px">
                    <option value="">Semua Status</option>
                    <option value="pending">Belum Bayar</option>
                    <option value="partial">Bayar Sebagian</option>
                    <option value="lunas">Lunas</option>
                </select>
                <input type="date" id="filterFrom" class="form-control form-control-sm" style="width:140px"
                    placeholder="Dari">
                <input type="date" id="filterTo" class="form-control form-control-sm" style="width:140px"
                    placeholder="Sampai">
            </div>
        </div>
        <div class="card-body">
            <table class="table table-striped table-bordered" id="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tipe</th>
                        <th>No. PO</th>
                        <th>CV</th>
                        <th>No. Polisi</th>
                        <th>Tujuan</th>
                        <th>Supplier</th>
                        <th>Tagihan</th>
                        <th>Dibayar</th>
                        <th>Sisa</th>
                        <th>Tgl Bayar</th>
                        <th>Metode</th>
                        <th>Status</th>
                        <th>Bukti</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <script>
        var dt;

        function fmt(n) {
            return 'Rp ' + (n || 0).toLocaleString('id-ID');
        }

        $(document).ready(function() {
            dt = $('#table').DataTable({
                bAutoWidth: false,
                iDisplayLength: 15,
                processing: true,
                serverSide: true,
                bDestroy: true,
                ajax: {
                    url: '/keuangan/pembayaran',
                    data: function(d) {
                        d.tipe_pembayaran = $('#filterTipe').val();
                        d.supplier_id = $('#filterSupplier').val();
                        d.status = $('#filterStatus').val();
                        d.from = $('#filterFrom').val();
                        d.to = $('#filterTo').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        width: '50px'
                    },
                    {
                        data: 'tipe',
                        name: 'tipe_pembayaran',
                        searchable: false
                    },
                    {
                        data: 'no_po',
                        name: 'no_po'
                    },
                    {
                        data: 'cv_name',
                        name: 'cv_name',
                        searchable: false
                    },
                    {
                        data: 'no_polisi',
                        name: 'no_polisi',
                        searchable: false
                    },
                    {
                        data: 'tujuan',
                        name: 'tujuan',
                        searchable: false
                    },
                    {
                        data: 'supplier_nama',
                        name: 'supplier_nama',
                        searchable: false
                    },
                    {
                        data: 'jumlah_tagihan',
                        searchable: false,
                        render: d => fmt(d)
                    },
                    {
                        data: 'jumlah_bayar',
                        searchable: false,
                        render: d => fmt(d)
                    },
                    {
                        data: 'sisa',
                        searchable: false,
                        render: d => `<span class="${d>0?'text-danger fw-bold':''}">${fmt(d)}</span>`
                    },
                    {
                        data: 'tanggal_bayar',
                        name: 'tanggal_bayar',
                        searchable: false
                    },
                    {
                        data: 'metode_bayar',
                        name: 'metode_bayar',
                        searchable: false,
                        render: d => d ? d.charAt(0).toUpperCase() + d.slice(1) : '-'
                    },
                    {
                        data: 'status_badge',
                        name: 'status',
                        searchable: false
                    },
                    {
                        data: 'bukti',
                        name: 'bukti',
                        orderable: false,
                        searchable: false
                    },
                ],
                aLengthMenu: [
                    [10, 15, 25, 50],
                    [10, 15, 25, 50]
                ],
                order: [
                    [10, 'desc']
                ], // Order by tanggal_bayar desc
                responsive: !0
            });

            $('#filterTipe, #filterSupplier, #filterStatus, #filterFrom, #filterTo').on('change', function() {
                dt.ajax.reload();
            });
        });
    </script>
@endsection
