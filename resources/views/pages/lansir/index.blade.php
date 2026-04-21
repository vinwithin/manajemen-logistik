@extends('layout.app')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fa fa-truck text-info"></i> Riwayat Lansir
                    </h5>
                    <span class="text-muted small">Daftar penerima yang memiliki riwayat lansir</span>
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
                                <th>Nama Penerima</th>
                                <th>Tujuan</th>
                                <th>Berat (kg)</th>
                                <th>Trip Lansir</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#table').DataTable({
                bAutoWidth: false,
                iDisplayLength: 15,
                searching: true,
                processing: true,
                serverSide: true,
                bDestroy: true,
                bStateSave: true,
                ajax: {
                    url: '/purchase-order/lansir'
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
                        data: 'nama_penerima',
                        name: 'nama_penerima'
                    },
                    {
                        data: 'tujuan_nama',
                        name: 'tujuan_nama',
                        searchable: false
                    },
                    {
                        data: 'berat',
                        name: 'berat',
                        searchable: false,
                        render: d => d ? Number(d).toLocaleString('id-ID') + ' kg' : '-'
                    },
                    {
                        data: 'jumlah_trip',
                        name: 'jumlah_trip',
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
                responsive: !0
            });
        });
    </script>
@endsection
