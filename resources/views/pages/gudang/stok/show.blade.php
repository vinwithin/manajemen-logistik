@extends('layout.app')
@section('content')
    {{-- Header --}}
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center py-3">
            <div>
                <h5 class="mb-1 fw-bold"><i class="fa fa-archive text-primary"></i> Gudang {{ $gudang->nama }}</h5>
                <span class="text-muted small">Stok per kode pakan</span>
            </div>
            <a href="{{ route('gudang.stok.index') }}" class="btn btn-sm btn-secondary">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    {{-- Tabel Stok --}}
    <div class="card mb-4">
        <div class="card-header py-4">
            <h6 class="mb-0 fw-bold"><i class="fa fa-cubes text-primary"></i> Saldo Stok Saat Ini</h6>
        </div>
        <div class="card-body border-bottom pb-3">
            <div class="row g-2">
                <div class="col-12 col-md-3">
                    <select id="filterPakan" class="form-select form-select-sm">
                        <option value="">Semua Kode Pakan</option>
                        @foreach ($kodePakans as $pakan)
                            <option value="{{ $pakan->id }}"
                                {{ request('kode_pakan_id') == $pakan->id ? 'selected' : '' }}>
                                {{ $pakan->kode }} — {{ $pakan->nama }}
                            </option>
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
                <div class="col-12 col-md-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnResetFilter">
                        <i class="fa fa-times"></i> Reset Filter
                    </button>
                </div>
            </div>
        </div>
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-success" id="btnExportKartuStock">
                    <i class="fa fa-file-excel-o"></i> Export Kartu Stock
                </button>
                <button type="button" class="btn btn-sm btn-warning" id="btnExportStokKeluar">
                    <i class="fa fa-file-excel-o"></i> Export Stok Keluar
                </button>
            </div>
        </div>

        <div class="card-body">
            <table class="table table-striped table-bordered" id="tableStok">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode Pakan</th>
                        <th>Nama Pakan</th>
                        <th>Stok (kg)</th>
                        <th>Stok (karung)</th>
                        <th width="100px">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>



    <script>
        $(document).ready(function() {

            // ── Tabel Stok ────────────────────────────────────────────────
            $('#tableStok').DataTable({
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

            // Pre-select filter dari query string (misal dari link "Mutasi" di tabel stok)
            var params = new URLSearchParams(window.location.search);
            if (params.get('kode_pakan_id')) {
                $('#filterPakan').val(params.get('kode_pakan_id'));
            }

            // Filter hanya dipakai untuk export — tidak ada reload tabel di sini

            // Reset filter
            $('#btnResetFilter').on('click', function() {
                $('#filterPakan, #filterTipe').val('');
                $('#filterDariTanggal, #filterSampaiTanggal').val('');
            });

            // ── Export Kartu Stock ────────────────────────────────────────
            $('#btnExportKartuStock').on('click', function() {
                var url = new URL('{{ route('gudang.mutasi.export') }}', window.location.origin);
                url.searchParams.set('tujuan_id', '{{ $gudang->id }}');
                var pakan = $('#filterPakan').val();
                var tipe = $('#filterTipe').val();
                var dari = $('#filterDariTanggal').val();
                var sampai = $('#filterSampaiTanggal').val();
                if (pakan) url.searchParams.set('kode_pakan_id', pakan);
                if (tipe) url.searchParams.set('tipe', tipe);
                if (dari) url.searchParams.set('dari_tanggal', dari);
                if (sampai) url.searchParams.set('sampai_tanggal', sampai);
                window.location.href = url.toString();
            });

            // ── Export Stok Keluar ────────────────────────────────────────
            $('#btnExportStokKeluar').on('click', function() {
                var url = new URL('{{ route('gudang.mutasi.export-keluar') }}', window.location.origin);
                url.searchParams.set('tujuan_id', '{{ $gudang->id }}');
                var dari = $('#filterDariTanggal').val();
                var sampai = $('#filterSampaiTanggal').val();
                if (dari) url.searchParams.set('dari_tanggal', dari);
                if (sampai) url.searchParams.set('sampai_tanggal', sampai);
                window.location.href = url.toString();
            });
        });
    </script>
@endsection
