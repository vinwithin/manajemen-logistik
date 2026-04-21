@extends('layout.app')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Daftar Supplier</h5>
                    <a href="{{ route('supplier.create') }}" class="btn btn-sm btn-primary">
                        <i class="fa fa-plus"></i> Tambah Supplier
                    </a>
                </div>
                <div class="card-body">
                    <table class="table table-striped table-bordered" id="table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Supplier</th>
                                <th>Initial</th>
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
                    url: '/master/supplier'
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
                        data: 'initial',
                        name: 'initial',
                        width: '120px'
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
            alertify.confirm("Konfirmasi!", "Apakah anda yakin menghapus supplier ini?", function() {
                $('#' + id).submit();
            }, function() {});
        }
    </script>
@endsection
