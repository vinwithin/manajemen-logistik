@extends('layout.app')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0"><i class="fa fa-exchange text-primary"></i> Transfer Pakan</h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-success" id="btnExportRekap">
                            <i class="fa fa-file-excel-o"></i> Export Excel
                        </button>
                        <button type="button" class="btn btn-sm btn-warning" id="btnExportPdfPtSum">
                            <i class="fa fa-file-pdf-o"></i> PDF PT Sum
                        </button>
                        @can('transfer-pakan.create')
                            <a href="{{ route('transfer-pakan.create') }}" class="btn btn-sm btn-primary">
                                <i class="fa fa-plus"></i> Input Baru
                            </a>
                        @endcan
                    </div>
                </div>

                {{-- Filter bar --}}
                <div class="card-body border-bottom pb-3">
                    <div class="row g-2">
                        <div class="col-12 col-md-3">
                            <input type="date" id="filterFrom" class="form-control form-control-sm">
                        </div>
                        <div class="col-12 col-md-3">
                            <input type="date" id="filterTo" class="form-control form-control-sm">
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">

                        <table class="table table-striped table-bordered" id="tableTransferPakan">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>No. Transfer</th>
                                    <th>Tanggal</th>
                                    <th>CV</th>
                                    <th>Pengirim</th>
                                    <th>Penerima</th>
                                    <th>Tujuan</th>
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
        $(document).ready(function() {
            dt = $('#tableTransferPakan').DataTable({
                bAutoWidth: false,
                iDisplayLength: 15,
                searching: true,
                processing: true,
                serverSide: true,
                bDestroy: true,
                bStateSave: true,
                order: [
                    [2, 'desc']
                ],
                ajax: {
                    url: '{{ route('transfer-pakan.index') }}',
                    data: function(d) {
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
                        data: 'no_transfer',
                        name: 'no_transfer'
                    },
                    {
                        data: 'tanggal',
                        name: 'tanggal_transfer',
                        searchable: false
                    },
                    {
                        data: 'cv_name',
                        name: 'cv_name',
                        searchable: false
                    },
                    {
                        data: 'pengirim',
                        name: 'pengirim',
                        searchable: false
                    },
                    {
                        data: 'penerima',
                        name: 'penerima',
                        searchable: false
                    },
                    {
                        data: 'tujuan_nama',
                        name: 'tujuan_nama',
                        searchable: false
                    },
                    {
                        data: 'status',
                        name: 'status',
                        orderable: false,
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
                    [10, 15, 25, 50, -1],
                    [10, 15, 25, 50, 'All']
                ],
                responsive: true
            });

            $('#filterFrom, #filterTo').on('change', function() {
                dt.ajax.reload();
            });

            // Export Excel — dengan filter aktif
            $('#btnExportRekap').on('click', function() {
                var params = new URLSearchParams();
                var dari = $('#filterFrom').val();
                var sampai = $('#filterTo').val();
                if (dari) params.set('from', dari);
                if (sampai) params.set('to', sampai);
                window.location.href = '{{ route('transfer-pakan.export-rekap') }}?' + params.toString();
            });

            // PDF PT Sum — ke halaman konfirmasi dengan filter aktif
            $('#btnExportPdfPtSum').on('click', function() {
                var params = new URLSearchParams();
                var dari = $('#filterFrom').val();
                var sampai = $('#filterTo').val();
                if (dari) params.set('from', dari);
                if (sampai) params.set('to', sampai);
                window.location.href = '{{ route('transfer-pakan.export-ptsum-confirm') }}?' + params
                    .toString();
            });
        });
    </script>
@endsection
