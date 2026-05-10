@extends('layout.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Master Penerima</h5>
                    <a href="{{ route('penerima.create') }}" class="btn btn-primary btn-sm">
                        <i class="fa fa-plus"></i> Tambah Penerima
                    </a>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="tablePenerima">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Penerima</th>
                                    <th>Tujuan</th>
                                    <th>Ongkos OA (Rp/kg)</th>
                                    <th>Ongkos Bongkar (Rp/kg)</th>
                                    <th>Telepon</th>
                                    <th>Status</th>
                                    <th width="120px">Aksi</th>
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
            $('#tablePenerima').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('penerima.index') }}',
                columns: [{
                        data: 'nama',
                        name: 'nama'
                    },
                    {
                        data: 'tujuan_nama',
                        name: 'tujuan.nama'
                    },
                    {
                        data: 'ongkos_formatted',
                        name: 'ongkos_angkut',
                        orderable: true
                    },
                    {
                        data: 'bongkar_formatted',
                        name: 'ongkos_bongkar',
                        orderable: true
                    },
                    {
                        data: 'telepon',
                        name: 'telepon',
                        defaultContent: '-'
                    },
                    {
                        data: 'status',
                        name: 'is_aktif',
                        orderable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [0, 'asc']
                ]
            });
        });

        function confirmDelete(id) {
            alertify.confirm(
                "Hapus Penerima?",
                "Data penerima akan dihapus permanen. Lanjutkan?",
                function() {
                    $.ajax({
                        url: '/master/penerima/' + id,
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                alertify.success(response.message);
                                $('#tablePenerima').DataTable().ajax.reload();
                            } else {
                                alertify.error(response.message);
                            }
                        },
                        error: function() {
                            alertify.error('Gagal menghapus penerima.');
                        }
                    });
                },
                function() {}
            );
        }
    </script>
@endsection
