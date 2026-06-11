@extends('layout.app')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">Rekap OA (Ongkos Angkut)</h5>
                    <div class="d-flex gap-2 flex-wrap align-items-center">
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
                        <input type="date" id="filterFrom" class="form-control form-control-sm" style="width:140px">
                        <input type="date" id="filterTo" class="form-control form-control-sm" style="width:140px">
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-striped table-bordered" id="table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>No. PO</th>
                                <th>Tanggal</th>
                                <th>CV</th>
                                <th>No. Polisi</th>
                                <th>Penerima</th>
                                <th>Supplier</th>
                                <th>Total KG</th>
                                <th>Total OA</th>
                                <th>DP</th>
                                <th>Sudah Bayar</th>
                                <th>Sisa</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
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
                    url: '/keuangan/oa',
                    data: function(d) {
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
                        data: 'no_po',
                        name: 'no_po'
                    },
                    {
                        data: 'tanggal_po',
                        name: 'tanggal_po',
                        searchable: false
                    },
                    {
                        data: 'cv_name',
                        name: 'cv_name',
                        searchable: false
                    },
                    {
                        data: 'no_polisi',
                        name: 'no_polisi'
                    },
                    {
                        data: 'penerima_list',
                        name: 'penerima_list',
                        searchable: false
                    },
                    {
                        data: 'supplier_nama',
                        name: 'supplier_nama',
                        searchable: false
                    },
                    {
                        data: 'total_kg',
                        name: 'total_kg',
                        searchable: false,
                        render: d => d ? Number(d).toLocaleString('id-ID') + ' kg' : '-'
                    },
                    {
                        data: 'total_oa',
                        name: 'total_oa',
                        searchable: false,
                        render: d => fmt(d)
                    },
                    {
                        data: 'dp_nominal',
                        name: 'dp_nominal',
                        searchable: false,
                        render: d => d && d !== '0' ? 'Rp ' + d : '-'
                    },
                    {
                        data: 'sudah_bayar',
                        name: 'sudah_bayar',
                        searchable: false,
                        render: d => d && d !== '0' ? 'Rp ' + d : '-'
                    },
                    {
                        data: 'sisa',
                        name: 'sisa',
                        searchable: false,
                        render: d => `<span class="${d>0?'text-danger fw-bold':''}">${fmt(d)}</span>`
                    },
                    {
                        data: 'status_bayar',
                        name: 'status_bayar',
                        searchable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                aLengthMenu: [
                    [10, 15, 25, 50],
                    [10, 15, 25, 50]
                ],
                order: [
                    [1, 'desc']
                ],
                responsive: !0,

            });

            $('#filterSupplier, #filterStatus, #filterFrom, #filterTo').on('change', function() {
                dt.ajax.reload();
            });
        });
    </script>
@endsection
