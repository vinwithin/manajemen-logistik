@extends('layout.app')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0"><i class="fa fa-truck text-info"></i> Lansir Gudang</h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-success" id="btnExportRekap">
                            <i class="fa fa-file-excel-o"></i> Export Rekap
                        </button>
                        <a href="#" id="btnExportPdfPtSum" class="btn btn-sm btn-warning">
                            <i class="fa fa-file-pdf-o"></i> PDF PT Sum
                        </a>
                        <a href="#" id="btnExportPdfSupplier" class="btn btn-sm btn-danger">
                            <i class="fa fa-file-pdf-o"></i> PDF Supplier
                        </a>
                        @can('gudang-stok.lansir')
                            <a href="{{ route('gudang.lansir.create') }}" class="btn btn-sm btn-primary">
                                <i class="fa fa-plus"></i> Lansir Baru
                            </a>
                        @endcan
                    </div>
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
                    <div class="table-responsive">

                        <table class="table table-striped table-bordered" id="table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>No Lansir</th>
                                    <th>Tanggal</th>
                                    <th>Gudang</th>
                                    <th>Kendaraan</th>
                                    <th>Penerima</th>
                                    <th>Total (kg)</th>
                                    <th>Total (karung)</th>
                                    <th>Pakan</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
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
                        data: 'no_lansir',
                        name: 'no_lansir'
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
                        data: 'jumlah_kendaraan',
                        name: 'jumlah_kendaraan',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'jumlah_penerima',
                        name: 'jumlah_penerima',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'total_kg_fmt',
                        name: 'total_kg',
                        searchable: false
                    },
                    {
                        data: 'total_karung_fmt',
                        name: 'total_karung',
                        searchable: false
                    },
                    {
                        data: 'pakan_list',
                        name: 'pakan_list',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'status_pengiriman',
                        name: 'status_pengiriman',
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

            // Export Rekap — kirim filter aktif
            $('#btnExportRekap').on('click', function() {
                var url = new URL('{{ route('gudang.lansir.export-rekap') }}', window.location.origin);
                var gudang = $('#filterGudang').val();
                var dari = $('#filterDariTanggal').val();
                var sampai = $('#filterSampaiTanggal').val();

                if (gudang) url.searchParams.set('gudang_id', gudang);
                if (dari) url.searchParams.set('dari_tanggal', dari);
                if (sampai) url.searchParams.set('sampai_tanggal', sampai);

                window.location.href = url.toString();
            });

            // PDF PT Sum — ke halaman konfirmasi dengan filter aktif
            $('#btnExportPdfPtSum').on('click', function() {
                var params = new URLSearchParams();
                var gudang = $('#filterGudang').val();
                var dari = $('#filterDariTanggal').val();
                var sampai = $('#filterSampaiTanggal').val();
                if (gudang) params.set('gudang_id', gudang);
                if (dari) params.set('from', dari);
                if (sampai) params.set('to', sampai);
                window.location.href = '{{ route('gudang.lansir.export-pdf-ptsum-confirm') }}?' + params
                    .toString();
            });

            // PDF Supplier — redirect ke halaman konfirmasi dengan filter aktif
            $('#btnExportPdfSupplier').on('click', function() {
                var params = new URLSearchParams();
                var gudang = $('#filterGudang').val();
                var dari = $('#filterDariTanggal').val();
                var sampai = $('#filterSampaiTanggal').val();
                if (gudang) params.set('gudang_id', gudang);
                if (dari) params.set('from', dari);
                if (sampai) params.set('to', sampai);
                window.location.href = '{{ route('gudang.lansir.export-pdf-supplier-confirm') }}?' + params
                    .toString();
            });
        });
    </script>
@endsection
