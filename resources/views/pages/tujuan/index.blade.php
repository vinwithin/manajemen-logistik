@extends('layout.app')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Tujuan Pengiriman</h5>
                    <div class="d-flex gap-2">
                        {{-- Filter type --}}
                        <select id="filterType" class="form-select form-select-sm" style="width:160px">
                            <option value="">Semua Type</option>
                            @foreach ($types as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <a href="{{ route('tujuan.create') }}" class="btn btn-sm btn-primary">
                            <i class="fa fa-plus"></i> Tambah Tujuan
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-striped table-bordered" id="table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Tujuan</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        var dt;
        $(document).ready(function() {
            dt = $('#table').DataTable({
                bAutoWidth: false,
                iDisplayLength: 15,
                searching: true,
                processing: true,
                serverSide: true,
                bDestroy: true,
                bStateSave: true,
                ajax: {
                    url: '/master/tujuan',
                    data: function(d) {
                        d.type = $('#filterType').val();
                    }
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
                        data: 'type_label',
                        name: 'type',
                        searchable: false
                    },
                    {
                        data: 'is_aktif',
                        name: 'is_aktif',
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
                    [10, 15, 25, 50, "All"]
                ],
                responsive: !0
            });

            $('#filterType').on('change', function() {
                dt.ajax.reload();
            });
        });

        function confirmation(id) {
            alertify.confirm("Konfirmasi!", "Apakah anda yakin menghapus data ini?", function() {
                $('#' + id).submit();
            }, function() {});
        }
    </script>
@endsection
