@extends('layout.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="mb-0"><i class="fa fa-list text-primary"></i> Rekap Lansir</h5>
                        <small class="text-muted">Hanya PO yang sudah dikunci</small>
                    </div>
                    <div class="d-flex gap-2 flex-wrap align-items-center">
                        <input type="date" id="filterFrom" class="form-control form-control-sm" style="width:140px" title="Dari Tanggal">
                        <input type="date" id="filterTo" class="form-control form-control-sm" style="width:140px" title="Sampai Tanggal">
                        <button type="button" id="resetFilter" class="btn btn-sm btn-secondary" title="Reset Filter">
                            <i class="fa fa-refresh"></i> Reset
                        </button>
                        @can('report.payment.export')
                            <button type="button" class="btn btn-sm btn-success btn-export"
                                data-url="{{ route('rekap-lansir.export-period-excel') }}">
                                <i class="fa fa-file-excel-o"></i> Export Excel
                            </button>
                            <button type="button" class="btn btn-sm btn-danger btn-export"
                                data-url="{{ route('rekap-lansir.export-period-pdf') }}">
                                <i class="fa fa-file-pdf-o"></i> Export PDF
                            </button>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">

                        <table class="table table-striped table-bordered" id="table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tipe</th>
                                    <th>No. Referensi</th>
                                    <th>Tanggal Lansir</th>
                                    <th>Penerima</th>
                                    <th>CV</th>
                                    <th>Kendaraan</th>
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
        $(document).ready(function() {
            const table = $('#table').DataTable({
                bAutoWidth: false,
                iDisplayLength: 15,
                searching: true,
                processing: true,
                serverSide: true,
                bDestroy: true,
                order: [
                    [3, 'desc']
                ],
                ajax: {
                    url: '{{ route('rekap-lansir.index') }}',
                    data: function(data) {
                        data.from = $('#filterFrom').val();
                        data.to = $('#filterTo').val();
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
                        name: 'tipe',
                        searchable: false
                    },
                    {
                        data: 'no_referensi',
                        name: 'no_referensi',
                        searchable: false
                    },
                    {
                        data: 'tanggal_lansir',
                        name: 'tanggal_lansir'
                    },
                    {
                        data: 'nama_tujuan',
                        name: 'nama_tujuan',
                        searchable: false
                    },
                    {
                        data: 'cv_name',
                        name: 'cv_name',
                        searchable: false
                    },
                    {
                        data: 'jumlah_kendaraan',
                        name: 'jumlah_kendaraan',
                        searchable: false,
                        orderable: false
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
                responsive: true
            });

            $('#filterFrom, #filterTo').on('change', function() {
                const from = $('#filterFrom').val();
                const to = $('#filterTo').val();

                if (!from || !to || from > to) {
                    return;
                }

                table.ajax.reload();
            });

            $('#resetFilter').on('click', function() {
                $('#filterFrom, #filterTo').val('');
                table.ajax.reload();
            });

            $('.btn-export').on('click', function() {
                if (!validatePeriod()) {
                    return;
                }

                const params = new URLSearchParams({
                    from: $('#filterFrom').val(),
                    to: $('#filterTo').val()
                });
                window.location.href = `${$(this).data('url')}?${params.toString()}`;
            });

            function validatePeriod() {
                const from = $('#filterFrom').val();
                const to = $('#filterTo').val();

                if (!from || !to) {
                    alert('Dari tanggal dan sampai tanggal wajib diisi.');
                    return false;
                }

                if (from > to) {
                    alert('Dari tanggal tidak boleh melewati sampai tanggal.');
                    return false;
                }

                return true;
            }
        });
    </script>
@endsection
