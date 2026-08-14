@extends('layout.app')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">Rekap PT Sum Lansir</h5>
                    <div class="d-flex gap-2 flex-wrap align-items-center">
                        <select id="filterStatus" class="form-select form-select-sm" style="width:160px">
                            <option value="">Semua Status</option>
                            <option value="belum_dibayar">Belum Dibayar</option>
                            <option value="sudah_dibayar">Sudah Dibayar</option>
                        </select>
                        <input type="date" id="filterFrom" class="form-control form-control-sm" style="width:140px">
                        <input type="date" id="filterTo" class="form-control form-control-sm" style="width:140px">
                        @can('rekap-pt-sum-lansir.export')
                            <button type="button" id="btnExportExcel" class="btn btn-sm btn-success">
                                <i class="fa fa-file-excel-o"></i> Export Excel
                            </button>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success py-2">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger py-2">{{ session('error') }}</div>
                    @endif
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tipe</th>
                                    <th>No. Referensi</th>
                                    <th>Tanggal</th>
                                    <th>CV</th>
                                    <th>Tujuan</th>
                                    <th>Kendaraan</th>
                                    <th>Total KG</th>
                                    <th>Total PT Sum</th>
                                    <th>Status</th>
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
                serverSide: false,
                bDestroy: true,
                ajax: {
                    url: '{{ route('keuangan.rekap-pt-sum-lansir.index') }}',
                    data: function(d) {
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
                        data: 'tipe'
                    },
                    {
                        data: 'no_referensi'
                    },
                    {
                        data: 'tanggal'
                    },
                    {
                        data: 'cv_name'
                    },
                    {
                        data: 'tujuan'
                    },
                    {
                        data: 'jumlah_kendaraan',
                        searchable: false
                    },
                    {
                        data: 'total_kg',
                        searchable: false,
                        render: d => d ? Number(d).toLocaleString('id-ID') + ' kg' : '-'
                    },
                    {
                        data: 'total_pt_sum',
                        searchable: false,
                        render: d => fmt(d)
                    },
                    {
                        data: 'status_bayar',
                        searchable: false
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                aLengthMenu: [
                    [10, 15, 25, 50],
                    [10, 15, 25, 50]
                ],
                order: [
                    [3, 'desc']
                ],
                responsive: !0,
            });

            $('#filterStatus, #filterFrom, #filterTo').on('change', function() {
                dt.ajax.reload();
            });

            $(document).on('submit', '.form-bayar-ptsum-lansir', function(e) {
                if (!confirm($(this).data('confirm') || 'Tandai tagihan PT Sum Lansir ini sebagai sudah dibayar?')) {
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
                    status: $('#filterStatus').val(),
                });

                window.location.href = '{{ route('keuangan.rekap-pt-sum-lansir.export-excel') }}?' + params.toString();
            });
        });
    </script>
@endsection
