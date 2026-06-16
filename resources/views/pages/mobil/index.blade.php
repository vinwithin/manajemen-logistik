@extends('layout.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Master Mobil</h5>
                    <a href="{{ route('mobil.create') }}" class="btn btn-primary btn-sm">
                        <i class="fa fa-plus"></i> Tambah Mobil
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
                        <table class="table table-bordered table-hover" id="tableMobil">
                            <thead class="table-light">
                                <tr>
                                    <th>Nopol</th>
                                    <th>Nama Sopir</th>
                                    <th>No HP</th>
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
            $('#tableMobil').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('mobil.index') }}',
                columns: [{
                        data: 'nopol',
                        name: 'nopol'
                    },
                    {
                        data: 'nama_sopir',
                        name: 'nama_sopir',
                        defaultContent: '-'
                    },
                    {
                        data: 'no_hp',
                        name: 'no_hp',
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
                "Hapus Mobil?",
                "Data mobil akan dihapus permanen. Lanjutkan?",
                function() {
                    $.ajax({
                        url: '/master/mobil/' + id,
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                alertify.success(response.message);
                                $('#tableMobil').DataTable().ajax.reload();
                            } else {
                                alertify.error(response.message);
                            }
                        },
                        error: function() {
                            alertify.error('Gagal menghapus mobil.');
                        }
                    });
                },
                function() {}
            );
        }
    </script>
@endsection
