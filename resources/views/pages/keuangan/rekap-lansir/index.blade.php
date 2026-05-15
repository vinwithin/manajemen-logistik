@extends('layout.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fa fa-list text-primary"></i> Rekap Lansir</h5>
                    <small class="text-muted">Hanya PO yang sudah dikunci</small>
                </div>
                <div class="card-body">
                    <table class="table table-striped table-bordered" id="table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tipe</th>
                                <th>No. Referensi</th>
                                <th>Tanggal Lansir</th>
                                <th>Tujuan</th>
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

    <script>
        $(document).ready(function() {
            $('#table').DataTable({
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
                    url: '{{ route('rekap-lansir.index') }}'
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
        });
    </script>
@endsection
