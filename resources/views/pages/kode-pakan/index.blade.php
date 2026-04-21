@extends('layout.app')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Kode Pakan</h5>
                    <a href="{{ route('pakan.create') }}" class="btn btn-sm btn-primary">
                        <i class="fa fa-plus"></i> Tambah Kode Pakan
                    </a>
                </div>
                <div class="card-body">
                    <table class="table table-striped table-bordered" id="table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Pakan</th>
                                <th>Kode</th>
                                <th>Action</th>
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
                    url: '/master/pakan'
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        width: '50px'
                    },
                    {
                        data: 'nama',
                        name: 'nama'
                    },
                    {
                        data: 'kode',
                        name: 'kode',
                        width: '150px'
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
                    [10, 15, 25, 50, "All"]
                ],
                responsive: !0
            });
        });

        function confirmation(id) {
            alertify.confirm("Konfirmasi!", "Hapus kode pakan ini?", function() {
                $('#' + id).submit();
            }, function() {});
        }
    </script>
@endsection
