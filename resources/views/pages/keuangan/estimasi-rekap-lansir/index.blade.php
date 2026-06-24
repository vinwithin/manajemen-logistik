@extends('layout.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Estimasi Lansir & Bongkar</h5>
                        <small class="text-muted">Penerima berdasarkan estimasi tiba dan status proses lansir.</small>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-12 col-md-3">
                            <label for="filterFrom" class="form-label">Dari Tanggal PO</label>
                            <input type="date" id="filterFrom" class="form-control" value="{{ $filters['from'] }}">
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="filterTo" class="form-label">Sampai Tanggal PO</label>
                            <input type="date" id="filterTo" class="form-control" value="{{ $filters['to'] }}">
                        </div>
                        <div class="col-12 col-md-auto">
                            <button type="button" id="resetFilter" class="btn btn-sm btn-secondary">
                                Reset
                            </button>
                        </div>
                        <div class="col-12 col-md-auto ms-md-auto">
                            <button type="button" class="btn btn-sm btn-success btn-export"
                                data-url="{{ route('estimasi-rekap-lansir.export') }}">
                                <i class="fa fa-file-excel"></i> Export Excel
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Estimasi Tiba</th>
                                    <th>No PO</th>
                                    <th>No DO</th>
                                    <th>Kendaraan</th>
                                    <th>Penerima</th>
                                    <th>Tujuan</th>
                                    <th>Pakan</th>
                                    <th>Kg</th>
                                    <th>Karung</th>
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
        $(document).ready(function() {
            const table = $('#table').DataTable({
                bAutoWidth: false,
                iDisplayLength: 15,
                searching: true,
                processing: true,
                serverSide: true,
                bDestroy: true,
                order: [
                    [1, 'asc']
                ],
                ajax: {
                    url: '{{ route('estimasi-rekap-lansir.index') }}',
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
                        data: 'estimasi_tiba_display',
                        name: 'estimasi_tiba'
                    },
                    {
                        data: 'no_po',
                        name: 'no_po',
                        orderable: false
                    },
                    {
                        data: 'no_do',
                        name: 'no_do'
                    },
                    {
                        data: 'kendaraan_display',
                        name: 'kendaraan.no_polisi',
                        orderable: false
                    },
                    {
                        data: 'nama_penerima',
                        name: 'nama_penerima'
                    },
                    {
                        data: 'tujuan_display',
                        name: 'tujuan_display',
                        orderable: false
                    },
                    {
                        data: 'pakan_display',
                        name: 'pakan_display',
                        orderable: false
                    },
                    {
                        data: 'total_kg',
                        name: 'total_kg',
                        orderable: false,
                        className: 'text-end',
                        render: data => Number(data || 0).toLocaleString('id-ID', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        })
                    },
                    {
                        data: 'total_karung',
                        name: 'total_karung',
                        orderable: false,
                        className: 'text-end',
                        render: data => Number(data || 0).toLocaleString('id-ID')
                    },
                    {
                        data: 'status_lansir',
                        name: 'status_lansir',
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
