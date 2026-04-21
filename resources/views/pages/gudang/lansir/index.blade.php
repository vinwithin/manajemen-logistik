@extends('layout.app')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0"><i class="fa fa-truck text-info"></i> Lansir Gudang</h5>
                    @can('gudang-stok.lansir')
                        <a href="{{ route('gudang.lansir.create') }}" class="btn btn-sm btn-primary">
                            <i class="fa fa-plus"></i> Lansir Baru
                        </a>
                    @endcan
                </div>

                {{-- Filter bar --}}
                <div class="card-body border-bottom pb-3">
                    <div class="row g-2">
                        <div class="col-12 col-md-4">
                            <select id="filterGudang" class="form-select form-select-sm">
                                <option value="">Semua Gudang</option>
                                @foreach ($gudangs as $gudang)
                                    <option value="{{ $gudang->id }}">{{ $gudang->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <input type="date" id="filterDariTanggal" class="form-control form-control-sm"
                                placeholder="Dari tanggal">
                        </div>
                        <div class="col-12 col-md-3">
                            <input type="date" id="filterSampaiTanggal" class="form-control form-control-sm"
                                placeholder="Sampai tanggal">
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <table class="table table-striped table-bordered" id="table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Gudang</th>
                                <th>No. Polisi</th>
                                <th>Sopir</th>
                                <th>Penerima</th>
                                <th>Total (kg)</th>
                                <th>Total (karung)</th>
                                <th>Pakan</th>
                                <th>Aksi</th>
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
                order: [
                    [1, 'desc']
                ],
                ajax: {
                    url: '/gudang/lansir',
                    data: function(d) {
                        d.gudang_id = $('#filterGudang').val();
                        d.dari_tanggal = $('#filterDariTanggal').val();
                        d.sampai_tanggal = $('#filterSampaiTanggal').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        width: '50px'
                    },
                    {
                        data: 'tanggal',
                        name: 'tanggal_lansir',
                        searchable: false
                    },
                    {
                        data: 'nama_gudang',
                        name: 'gudang.nama'
                    },
                    {
                        data: 'no_polisi',
                        name: 'no_polisi'
                    },
                    {
                        data: 'nama_sopir',
                        name: 'nama_sopir'
                    },
                    {
                        data: 'jumlah_penerima',
                        name: 'jumlah_penerima',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'total_kg',
                        name: 'total_kg',
                        searchable: false,
                        render: function(d) {
                            return '<strong>' + Number(d).toLocaleString('id-ID') + ' kg</strong>';
                        }
                    },
                    {
                        data: 'total_karung',
                        name: 'total_karung',
                        searchable: false,
                        render: function(d) {
                            return Number(d).toLocaleString('id-ID') + ' karung';
                        }
                    },
                    {
                        data: 'pakan_list',
                        name: 'pakan_list',
                        orderable: false,
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
                    [10, 15, 25, 50, 'All']
                ],
                responsive: true
            });

            $('#filterGudang, #filterDariTanggal, #filterSampaiTanggal').on('change', function() {
                dt.ajax.reload();
            });
        });
    </script>
@endsection
