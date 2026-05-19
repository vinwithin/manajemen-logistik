@extends('layout.app')
@section('content')
    @php
        $po = $penerima->kendaraan->po;
    @endphp

    {{-- Header --}}
    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h5 class="mb-1 fw-bold">Lansir — {{ $penerima->nama_penerima }}</h5>
                    <div class="text-muted small">
                        PO: <strong>{{ $po->no_po }}</strong>
                        &nbsp;·&nbsp; Kendaraan: <strong>{{ $penerima->kendaraan->no_polisi }}</strong>
                        @if ($penerima->kendaraan->supplier)
                            &nbsp;·&nbsp; Supplier: <strong>{{ $penerima->kendaraan->supplier->initial }}</strong>
                        @endif
                        &nbsp;·&nbsp; Tujuan: <strong>{{ $penerima->tujuan?->nama ?? '-' }}</strong>
                    </div>
                    <div class="mt-1">
                        @if ($penerima->validasi_oleh)
                            <span class="text-muted small ms-2">
                                Divalidasi: {{ $penerima->validasi_oleh }}
                                ({{ $penerima->tiba_at?->format('d/m/Y H:i') }})
                            </span>
                        @endif
                    </div>
                </div>
                <a href="{{ route('purchase-order.show', encrypt($po->id)) }}" class="btn btn-sm btn-secondary">
                    <i class="fa fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    {{-- Ringkasan pakan --}}
    <div class="card mb-3">
        <div class="card-header py-2">
            <h6 class="mb-0">Pakan Penerima Ini</h6>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Kode Pakan</th>
                        <th class="text-end">Jumlah (kg)</th>
                        <th class="text-end">Karung</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($penerima->pakans as $pakan)
                        <tr>
                            <td>{{ $pakan->kodePakan?->kode ?? '-' }}</td>
                            <td class="text-end">{{ number_format($pakan->jumlah_kg, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($pakan->jumlah_karung, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-semibold">
                    <tr>
                        <td>Total</td>
                        <td class="text-end">{{ number_format($penerima->total_kg, 0, ',', '.') }} kg</td>
                        <td class="text-end">{{ number_format($penerima->pakans->sum('jumlah_karung'), 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Riwayat lansir --}}
    @if ($penerima->lansirs->count())
        <div class="card mb-3">
            <div class="card-header py-2">
                <h6 class="mb-0">Riwayat Lansir ({{ $penerima->lansirs->count() }} trip)</h6>
            </div>
            <div class="card-body">
                @foreach ($penerima->lansirs as $i => $lansir)
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold">Trip #{{ $i + 1 }}</span>
                            <span class="text-muted small">
                                @if ($lansir->no_do)
                                    <i class="fa fa-file-text-o"></i> {{ $lansir->no_do }}
                                    &nbsp;·&nbsp;
                                @endif
                                @if ($lansir->tanggal_lansir)
                                    <i class="fa fa-calendar"></i> {{ $lansir->tanggal_lansir->format('d/m/Y') }}
                                    &nbsp;·&nbsp;
                                @endif
                                {{ $lansir->selesai_at?->format('d/m/Y H:i') }}
                                &nbsp;·&nbsp; {{ $lansir->validasi_oleh }}
                            </span>
                        </div>

                        {{-- Mobil --}}
                        @if ($lansir->mobils->count())
                            <div class="mb-2">
                                <div class="text-muted small mb-1">Kendaraan Lansir:</div>
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>No. Polisi</th>
                                            <th>Sopir</th>
                                            <th class="text-end">Berat (kg)</th>
                                            <th class="text-end">Karung</th>
                                            <th class="text-end">Ongkos/kg</th>
                                            <th class="text-end">Total Ongkos</th>
                                            <th>Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($lansir->mobils as $mobil)
                                            <tr>
                                                <td>{{ $mobil->no_polisi }}</td>
                                                <td>{{ $mobil->nama_sopir ?? '-' }}</td>
                                                <td class="text-end">{{ number_format($mobil->berat ?? 0, 0, ',', '.') }}
                                                </td>
                                                <td class="text-end">
                                                    {{ number_format($mobil->jumlah_karung ?? 0, 0, ',', '.') }}</td>
                                                <td class="text-end">Rp
                                                    {{ number_format($mobil->ongkos ?? 0, 0, ',', '.') }}</td>
                                                <td class="text-end">Rp
                                                    {{ number_format(($mobil->berat ?? 0) * ($mobil->ongkos ?? 0), 0, ',', '.') }}
                                                </td>
                                                <td>
                                                    @if ($mobil->keterangan)
                                                        <span class="text-muted small">{{ $mobil->keterangan }}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        {{-- Tim --}}
                        @if ($lansir->tims->count())
                            <div>
                                <div class="text-muted small mb-1">Tim Bongkar:</div>
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nama Tim</th>
                                            <th class="text-end">Berat (kg)</th>
                                            <th class="text-end">Karung</th>
                                            <th class="text-end">Upah/kg</th>
                                            <th>Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($lansir->tims as $tim)
                                            <tr>
                                                <td>{{ $tim->nama_tim }}</td>
                                                <td class="text-end">{{ number_format($tim->berat ?? 0, 0, ',', '.') }}
                                                </td>
                                                <td class="text-end">
                                                    {{ number_format($tim->jumlah_karung ?? 0, 0, ',', '.') }}</td>
                                                <td class="text-end">Rp {{ number_format($tim->upah ?? 0, 0, ',', '.') }}
                                                </td>
                                                <td>
                                                    @if ($tim->keterangan)
                                                        <span class="text-muted small">{{ $tim->keterangan }}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Form tambah lansir baru (hanya tampil jika status tiba) --}}
    @if ($penerima->status === 'tiba')
        <div class="card">
            <div class="card-header py-2">
                <h6 class="mb-0"><i class="fa fa-plus"></i> Tambah Trip Lansir</h6>
            </div>
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show py-2">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show py-2">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                        <strong><i class="fa fa-exclamation-triangle"></i> Terdapat kesalahan:</strong>
                        <ul class="mb-0 mt-1 ps-3">
                            @foreach ($errors->all() as $error)
                                <li class="small">{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form method="post" action="{{ route('po-penerima.lansir-store', $penerima->id) }}">
                    @csrf

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Validator <span class="text-danger">*</span></label>
                            <input type="text" name="validasi_oleh"
                                class="form-control @error('validasi_oleh') is-invalid @enderror"
                                value="{{ old('validasi_oleh') }}" placeholder="Nama admin / petugas">
                            @error('validasi_oleh')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Lansir <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_lansir"
                                class="form-control @error('tanggal_lansir') is-invalid @enderror"
                                value="{{ old('tanggal_lansir', date('Y-m-d')) }}">
                            @error('tanggal_lansir')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">No. Surat Jalan</label>
                            <input type="text" name="no_do"
                                class="form-control @error('no_do') is-invalid @enderror"
                                value="{{ old('no_do', $penerima->no_do ?? '') }}" placeholder="Opsional">
                            @error('no_do')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>


                    @php
                        $ongkosAngkut = $penerima->penerima?->ongkos_angkut ?? 0;
                        $ongkosBongkar = $penerima->penerima?->ongkos_bongkar ?? 0;
                        $gabungOngkos = $ongkosAngkut > 0 && $ongkosBongkar > 0;
                        $defaultOngkosMobil = $gabungOngkos ? $ongkosAngkut + $ongkosBongkar : $ongkosAngkut;
                    @endphp

                    {{-- Kendaraan Lansir --}}
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <h6 class="fw-semibold mb-0">Kendaraan Lansir</h6>
                                @if ($gabungOngkos)
                                    <span class="badge bg-success text-white">
                                        OA+Angkut: Rp {{ number_format($defaultOngkosMobil, 0, ',', '.') }}/kg
                                    </span>
                                    <span class="badge bg-secondary text-white small">
                                        (OA {{ number_format($ongkosAngkut, 0, ',', '.') }} + Angkut
                                        {{ number_format($ongkosBongkar, 0, ',', '.') }})
                                    </span>
                                @elseif ($ongkosAngkut > 0)
                                    <span class="badge bg-info text-white">
                                        OA Lansir: Rp {{ number_format($ongkosAngkut, 0, ',', '.') }}/kg
                                    </span>
                                @endif
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnTambahMobil">
                                <i class="fa fa-plus"></i> Tambah Kendaraan
                            </button>
                        </div>
                        <div id="listMobil">
                            <div class="row g-2 align-items-end mb-2 item-mobil">
                                <div class="col-md-2">
                                    <label class="form-label small">No. Polisi <span class="text-danger">*</span></label>
                                    <input type="text" name="mobils[0][no_polisi]"
                                        class="form-control form-control-sm text-uppercase" placeholder="B 1234 XY">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">Nama Sopir</label>
                                    <input type="text" name="mobils[0][nama_sopir]"
                                        class="form-control form-control-sm" placeholder="Opsional">
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label small">Berat (kg)</label>
                                    <input type="number" name="mobils[0][berat]"
                                        class="form-control form-control-sm input-berat" placeholder="0" step="0.01"
                                        min="0">
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label small">Karung</label>
                                    <input type="number" name="mobils[0][jumlah_karung]"
                                        class="form-control form-control-sm input-karung" placeholder="0" min="0"
                                        readonly>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label small">Ongkos (Rp/kg)</label>
                                    <input type="number" name="mobils[0][ongkos]"
                                        class="form-control form-control-sm input-ongkos-mobil" placeholder="0"
                                        step="0.01" min="0" value="{{ $defaultOngkosMobil }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Keterangan</label>
                                    <input type="text" name="mobils[0][keterangan]"
                                        class="form-control form-control-sm" placeholder="Keluhan/catatan (opsional)">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-mobil w-100"
                                        disabled><i class="fa fa-times"></i> Hapus</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tim Bongkar — disembunyikan jika ongkos sudah digabung ke mobil --}}
                    @if (!$gabungOngkos)
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <h6 class="fw-semibold mb-0">Tim Bongkar <span
                                            class="text-muted small">(Opsional)</span>
                                    </h6>
                                    @if ($penerima->penerima?->ongkos_bongkar > 0)
                                        <span class="badge bg-secondary">
                                            Upah: Rp
                                            {{ number_format($penerima->penerima->ongkos_bongkar, 0, ',', '.') }}/kg
                                        </span>
                                    @endif
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnTambahTim">
                                    <i class="fa fa-plus"></i> Tambah Tim
                                </button>
                            </div>
                            <div id="listTim"></div>
                        </div>
                    @else
                        <div class="alert alert-info py-2 mb-4 small">
                            <i class="fa fa-info-circle"></i>
                            Tim bongkar tidak diperlukan — ongkos OA (Rp
                            {{ number_format($ongkosAngkut, 0, ',', '.') }}/kg)
                            dan ongkos angkut (Rp {{ number_format($ongkosBongkar, 0, ',', '.') }}/kg) sudah digabung
                            ke kolom ongkos kendaraan di atas.
                        </div>
                    @endif

                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> Simpan Lansir
                    </button>
                </form>
            </div>
        </div>
    @else
        <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> Penerima sudah selesai. Anda hanya dapat melihat riwayat lansir.
        </div>
    @endif

    @if ($penerima->status === 'tiba')
        <script>
            var mobilCount = 1;
            var timCount = 0;

            // Ongkos dari master penerima
            var defaultOngkosMobil = {{ $defaultOngkosMobil }};
            var defaultUpahTim = {{ $gabungOngkos ? 0 : $penerima->penerima?->ongkos_bongkar ?? 0 }};

            // Auto-calc karung dari berat (untuk mobil)
            $(document).on('input', '.input-berat', function() {
                var berat = parseFloat($(this).val()) || 0;
                $(this).closest('.item-mobil').find('.input-karung').val(berat > 0 ? Math.ceil(berat / 50) : '');
            });

            // Auto-calc karung dari berat (untuk tim)
            $(document).on('input', '.input-berat-tim', function() {
                var berat = parseFloat($(this).val()) || 0;
                $(this).closest('.item-tim').find('.input-karung-tim').val(berat > 0 ? Math.ceil(berat / 50) : '');
            });

            // Tambah kendaraan lansir
            $('#btnTambahMobil').on('click', function() {
                var i = mobilCount++;
                var row = `<div class="row g-2 align-items-end mb-2 item-mobil">
                <div class="col-md-2">
                    <input type="text" name="mobils[${i}][no_polisi]" class="form-control form-control-sm text-uppercase" placeholder="B 1234 XY">
                </div>
                <div class="col-md-2">
                    <input type="text" name="mobils[${i}][nama_sopir]" class="form-control form-control-sm" placeholder="Opsional">
                </div>
                <div class="col-md-1">
                    <input type="number" name="mobils[${i}][berat]" class="form-control form-control-sm input-berat" placeholder="0" step="0.01" min="0">
                </div>
                <div class="col-md-1">
                    <input type="number" name="mobils[${i}][jumlah_karung]" class="form-control form-control-sm input-karung" placeholder="0" min="0" readonly>
                </div>
                <div class="col-md-1">
                    <input type="number" name="mobils[${i}][ongkos]" class="form-control form-control-sm input-ongkos-mobil" placeholder="0" step="0.01" min="0" value="${defaultOngkosMobil}">
                </div>
                <div class="col-md-3">
                    <input type="text" name="mobils[${i}][keterangan]" class="form-control form-control-sm" placeholder="Keluhan/catatan (opsional)">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-mobil w-100"><i class="fa fa-times"></i> Hapus</button>
                </div>
            </div>`;
                $('#listMobil').append(row);
                updateMobilHapus();
            });

            $(document).on('click', '.btn-hapus-mobil', function() {
                if ($('.item-mobil').length > 1) {
                    $(this).closest('.item-mobil').remove();
                    updateMobilHapus();
                }
            });

            function updateMobilHapus() {
                var $rows = $('.item-mobil');
                $rows.find('.btn-hapus-mobil').prop('disabled', $rows.length === 1);
            }

            // Tambah tim bongkar
            $('#btnTambahTim').on('click', function() {
                var i = timCount++;
                var row = `<div class="row g-2 align-items-end mb-2 item-tim">
                <div class="col-md-2">
                    <label class="form-label small">Nama Tim <span class="text-danger">*</span></label>
                    <input type="text" name="tims[${i}][nama_tim]" class="form-control form-control-sm" placeholder="Nama tim bongkar">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Berat (kg)</label>
                    <input type="number" name="tims[${i}][berat]" class="form-control form-control-sm input-berat-tim" placeholder="0" step="0.01" min="0">
                </div>
                <div class="col-md-1">
                    <label class="form-label small">Karung</label>
                    <input type="number" name="tims[${i}][jumlah_karung]" class="form-control form-control-sm input-karung-tim" placeholder="0" min="0" readonly>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Upah (Rp/kg)</label>
                    <input type="number" name="tims[${i}][upah]" class="form-control form-control-sm" placeholder="0" step="0.01" min="0" value="${defaultUpahTim}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Keterangan</label>
                    <input type="text" name="tims[${i}][keterangan]" class="form-control form-control-sm" placeholder="Keluhan/catatan (opsional)">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-tim w-100"><i class="fa fa-times"></i> Hapus Tim</button>
                </div>
            </div>`;
                $('#listTim').append(row);
            });

            $(document).on('click', '.btn-hapus-tim', function() {
                $(this).closest('.item-tim').remove();
            });
        </script>
    @endif
@endsection
