@extends('layout.app')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">Rekap PT Sum</h5>
                    <div class="d-flex gap-2 flex-wrap align-items-center">
                        <select id="filterSupplier" class="form-select form-select-sm" style="width:180px">
                            <option value="">Semua Supplier</option>
                            @foreach ($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->nama }}</option>
                            @endforeach
                        </select>
                        <select id="filterStatus" class="form-select form-select-sm" style="width:150px">
                            <option value="">Semua Status</option>
                            <option value="belum_dibayar">Belum Dibayar</option>
                            <option value="sudah_dibayar">Sudah Dibayar</option>
                        </select>
                        <input type="date" id="filterFrom" class="form-control form-control-sm" style="width:140px">
                        <input type="date" id="filterTo" class="form-control form-control-sm" style="width:140px">
                        @can('rekap-pt-sum.export')
                            <button type="button" id="btnExportExcel" class="btn btn-sm btn-success">
                                <i class="fa fa-file-excel-o"></i> Export Excel
                            </button>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success py-2">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger py-2">
                            {{ session('error') }}
                        </div>
                    @endif
                    <div class="table-responsive">
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
                                    <th>Harga</th>
                                    <th>Total PT Sum</th>
                                    <th>Status PT Sum</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        var dt;

        function fmt(n) {
            return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
        }

        $(document).ready(function() {
            dt = $('#table').DataTable({
                bAutoWidth: false,
                iDisplayLength: 15,
                processing: true,
                serverSide: true,
                bDestroy: true,
                ajax: {
                    url: '{{ route('keuangan.rekap-pt-sum.index') }}',
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
                        data: 'harga_rata_rata',
                        name: 'harga_rata_rata',
                        searchable: false,
                        render: d => fmt(d)
                    },
                    {
                        data: 'total_pt_sum',
                        name: 'total_pt_sum',
                        searchable: false,
                        render: d => fmt(d)
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

            $(document).on('submit', '.form-bayar-ptsum', function(e) {
                if (!confirm($(this).data('confirm') || 'Tandai tagihan PT Sum ini sebagai sudah dibayar?')) {
                    e.preventDefault();
                }
            });

            $('#btnExportExcel').on('click', function() {
                const from = $('#filterFrom').val();
                const to = $('#filterTo').val();

                if (!from || !to) {
                    alert('Pilih Dari Tanggal dan Sampai Tanggal terlebih dahulu untuk export Excel.');
                    return;
                }

                if (from > to) {
                    alert('Dari Tanggal tidak boleh melewati Sampai Tanggal.');
                    return;
                }

                const params = new URLSearchParams({
                    from: from,
                    to: to,
                    supplier_id: $('#filterSupplier').val(),
                    status: $('#filterStatus').val(),
                });

                window.location.href = '{{ route('keuangan.rekap-pt-sum.export-excel') }}?' + params.toString();
            });
        });
    </script>
@endsection
