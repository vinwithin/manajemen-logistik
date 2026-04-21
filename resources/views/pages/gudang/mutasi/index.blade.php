@extends('layout.app')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0"><i class="fa fa-history text-info"></i> Riwayat Mutasi Stok</h5>
                    <a href="{{ route('gudang.stok.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fa fa-arrow-left"></i> Kembali ke Stok
                    </a>
                </div>

                {{-- Filter bar --}}
                <div class="card-body border-bottom pb-3">
                    <div class="row g-2">
                        <div class="col-12 col-md-3">
                            <select id="filterGudang" class="form-select form-select-sm">
                                <option value="">Semua Gudang</option>
                                @foreach ($gudangs as $gudang)
                                    <option value="{{ $gudang->id }}"
                                        {{ request('tujuan_id') == $gudang->id ? 'selected' : '' }}>
                                        {{ $gudang->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <select id="filterPakan" class="form-select form-select-sm">
                                <option value="">Semua Kode Pakan</option>
                                @foreach ($kodePakans as $pakan)
                                    <option value="{{ $pakan->id }}">{{ $pakan->kode }} — {{ $pakan->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <select id="filterTipe" class="form-select form-select-sm">
                                <option value="">Semua Tipe</option>
                                <option value="masuk">Masuk</option>
                                <option value="keluar">Keluar</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <input type="date" id="filterDariTanggal" class="form-control form-control-sm"
                                placeholder="Dari tanggal">
                        </div>
                        <div class="col-12 col-md-2">
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
                                <th>Kode Pakan</th>
                                <th>Tipe</th>
                                <th>Jumlah (kg)</th>
                                <th>Jumlah (karung)</th>
                                <th>Saldo Setelah (kg)</th>
                                <th>Penerima / Referensi</th>
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
                    url: '/gudang/mutasi',
                    data: function(d) {
                        d.tujuan_id = $('#filterGudang').val();
                        d.kode_pakan_id = $('#filterPakan').val();
                        d.tipe = $('#filterTipe').val();
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
                        name: 'created_at',
                        searchable: false
                    },
                    {
                        data: 'nama_gudang',
                        name: 'tujuan.nama'
                    },
                    {
                        data: 'kode_pakan',
                        name: 'kodePakan.kode',
                        width: '120px'
                    },
                    {
                        data: 'tipe',
                        name: 'tipe',
                        searchable: false,
                        render: function(d) {
                            return d === 'masuk' ?
                                '<span class="badge bg-success">Masuk</span>' :
                                '<span class="badge bg-danger">Keluar</span>';
                        }
                    },
                    {
                        data: 'jumlah_kg',
                        name: 'jumlah_kg',
                        searchable: false,
                        render: function(d) {
                            return '<strong>' + Number(d).toLocaleString('id-ID') + ' kg</strong>';
                        }
                    },
                    {
                        data: 'jumlah_karung',
                        name: 'jumlah_karung',
                        searchable: false,
                        render: function(d) {
                            return Number(d).toLocaleString('id-ID') + ' karung';
                        }
                    },
                    {
                        data: 'saldo_kg_after',
                        name: 'saldo_kg_after',
                        searchable: false,
                        render: function(d) {
                            return '<strong>' + Number(d).toLocaleString('id-ID') + ' kg</strong>';
                        }
                    },
                    {
                        data: 'referensi',
                        name: 'referensi',
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

            $('#filterGudang, #filterPakan, #filterTipe, #filterDariTanggal, #filterSampaiTanggal').on('change',
                function() {
                    dt.ajax.reload();
                });

            // Pre-select filter from query string
            var params = new URLSearchParams(window.location.search);
            if (params.get('tujuan_id')) {
                $('#filterGudang').val(params.get('tujuan_id')).trigger('change');
            }
        });
    </script>
@endsection
