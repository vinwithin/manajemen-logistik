@extends('layout.app')
@section('content')
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center py-3">
            <div>
                <h5 class="mb-1 fw-bold"><i class="fa fa-archive text-primary"></i>Gudang {{ $gudang->nama }}</h5>
                <span class="text-muted small">Stok per kode pakan</span>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('gudang.mutasi.index', ['tujuan_id' => $gudang->id]) }}"
                    class="btn btn-sm btn-outline-info">
                    <i class="fa fa-history"></i> Lihat Mutasi
                </a>
                <a href="{{ route('gudang.stok.index') }}" class="btn btn-sm btn-secondary">
                    <i class="fa fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped table-bordered" id="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode Pakan</th>
                        <th>Nama Pakan</th>
                        <th>Stok (kg)</th>
                        <th>Stok (karung)</th>
                    </tr>
                </thead>
            </table>
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
                    url: '/gudang/stok/{{ $gudang->id }}'
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        width: '50px'
                    },
                    {
                        data: 'kode_pakan',
                        name: 'kodePakan.kode'
                    },
                    {
                        data: 'nama_pakan',
                        name: 'kodePakan.nama'
                    },
                    {
                        data: 'stok_kg_fmt',
                        name: 'stok_kg',
                        searchable: false
                    },
                    {
                        data: 'stok_karung_fmt',
                        name: 'stok_karung',
                        searchable: false
                    },
                ],
                aLengthMenu: [
                    [10, 15, 25, 50, -1],
                    [10, 15, 25, 50, 'All']
                ],
                responsive: true
            });
        });
    </script>
@endsection
