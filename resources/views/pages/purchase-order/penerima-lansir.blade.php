@extends('layout.app')
@section('content')
    @php
        $po = $penerima->kendaraan->po;
        $jenisLansirDefault = old('jenis_lansir', $jenisLansirDefault ?? 'mobil_tim');
        $isTimBongkarPage = $isTimBongkarPage ?? false;
    @endphp

    {{-- Header --}}
    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h5 class="mb-1 fw-bold">
                        {{ $isTimBongkarPage ? 'Tim Bongkar' : 'Lansir' }} — {{ $penerima->nama_penerima }}
                    </h5>
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
                            <div class="d-flex gap-2 align-items-center">
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
                                <button type="button" class="btn btn-sm btn-warning text-white btn-edit-lansir"
                                    data-target="#edit-lansir-{{ $lansir->id }}">
                                    Edit
                                </button>
                                <form action="{{ route('po-penerima.lansir-destroy', encrypt($lansir->id)) }}"
                                    method="POST"
                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus riwayat lansir ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger text-white">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div id="edit-lansir-{{ $lansir->id }}" class="border rounded bg-white p-3 mb-3 edit-lansir-form"
                            style="display: none;">
                            <div class="alert alert-warning">
                                <p class="text-dark">Dalam Mode Edit</p>
                            </div>
                            <form method="POST" action="{{ route('po-penerima.lansir-update', encrypt($lansir->id)) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="jenis_lansir"
                                    value="{{ $lansir->mobils->count() ? 'mobil_tim' : 'tim_bongkar' }}">

                                <div class="row g-2 mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label small">Nama Validator <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="validasi_oleh" class="form-control form-control-sm"
                                            value="{{ old('validasi_oleh', $lansir->validasi_oleh) }}" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Tanggal Lansir <span
                                                class="text-danger">*</span></label>
                                        <input type="date" name="tanggal_lansir" class="form-control form-control-sm"
                                            value="{{ old('tanggal_lansir', $lansir->tanggal_lansir?->format('Y-m-d')) }}"
                                            required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">No DO</label>
                                        <input type="text" name="no_do" class="form-control form-control-sm"
                                            value="{{ old('no_do', $lansir->no_do) }}" placeholder="Opsional">
                                    </div>
                                </div>

                                <div class="mb-3 edit-mobil-section">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="fw-semibold small">Edit Kendaraan Lansir</div>
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-tambah-edit-mobil">
                                            <i class="fa fa-plus"></i> Tambah Kendaraan
                                        </button>
                                    </div>
                                    <div class="edit-list-mobil" data-next-index="{{ $lansir->mobils->count() }}">
                                        @foreach ($lansir->mobils as $mobilIndex => $mobil)
                                            <div class="row g-2 align-items-end mb-2 edit-item-mobil">
                                                <div class="col-md-2">
                                                    <label class="form-label small">No. Polisi</label>
                                                    <input type="text" name="mobils[{{ $mobilIndex }}][no_polisi]"
                                                        class="form-control form-control-sm text-uppercase"
                                                        value="{{ old("mobils.$mobilIndex.no_polisi", $mobil->no_polisi) }}"
                                                        required>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small">Nama Sopir</label>
                                                    <input type="text" name="mobils[{{ $mobilIndex }}][nama_sopir]"
                                                        class="form-control form-control-sm"
                                                        value="{{ old("mobils.$mobilIndex.nama_sopir", $mobil->nama_sopir) }}">
                                                </div>
                                                <div class="col-md-1">
                                                    <label class="form-label small">Berat</label>
                                                    <input type="number" name="mobils[{{ $mobilIndex }}][berat]"
                                                        class="form-control form-control-sm edit-input-berat"
                                                        value="{{ old("mobils.$mobilIndex.berat", $mobil->berat) }}"
                                                        step="0.01" min="0">
                                                </div>
                                                <div class="col-md-1">
                                                    <label class="form-label small">Karung</label>
                                                    <input type="number" name="mobils[{{ $mobilIndex }}][jumlah_karung]"
                                                        class="form-control form-control-sm edit-input-karung"
                                                        value="{{ old("mobils.$mobilIndex.jumlah_karung", $mobil->jumlah_karung) }}"
                                                        min="0" readonly>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small">Ongkos/kg</label>
                                                    <input type="number" name="mobils[{{ $mobilIndex }}][ongkos]"
                                                        class="form-control form-control-sm"
                                                        value="{{ old("mobils.$mobilIndex.ongkos", $mobil->ongkos) }}"
                                                        step="0.01" min="0">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small">Keterangan</label>
                                                    <input type="text" name="mobils[{{ $mobilIndex }}][keterangan]"
                                                        class="form-control form-control-sm"
                                                        value="{{ old("mobils.$mobilIndex.keterangan", $mobil->keterangan) }}">
                                                </div>
                                                <div class="col-md-12">
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-danger btn-hapus-edit-mobil">
                                                        <i class="fa fa-times"></i> Hapus Kendaraan
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="fw-semibold small">Edit Tim Bongkar</div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-tambah-edit-tim">
                                            <i class="fa fa-plus"></i> Tambah Tim
                                        </button>
                                    </div>
                                    <div class="edit-list-tim" data-next-index="{{ max($lansir->tims->count(), 1) }}">
                                    @forelse ($lansir->tims as $timIndex => $tim)
                                        <div class="row g-2 align-items-end mb-2 edit-item-tim">
                                            <div class="col-md-2">
                                                <label class="form-label small">Nama Tim</label>
                                                <input type="text" name="tims[{{ $timIndex }}][nama_tim]"
                                                    class="form-control form-control-sm"
                                                    value="{{ old("tims.$timIndex.nama_tim", $tim->nama_tim) }}">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Berat</label>
                                                <input type="number" name="tims[{{ $timIndex }}][berat]"
                                                    class="form-control form-control-sm edit-input-berat-tim"
                                                    value="{{ old("tims.$timIndex.berat", $tim->berat) }}" step="0.01"
                                                    min="0">
                                            </div>
                                            <div class="col-md-1">
                                                <label class="form-label small">Karung</label>
                                                <input type="number" name="tims[{{ $timIndex }}][jumlah_karung]"
                                                    class="form-control form-control-sm edit-input-karung-tim"
                                                    value="{{ old("tims.$timIndex.jumlah_karung", $tim->jumlah_karung) }}"
                                                    min="0" readonly>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Upah/kg</label>
                                                <input type="number" name="tims[{{ $timIndex }}][upah]"
                                                    class="form-control form-control-sm"
                                                    value="{{ old("tims.$timIndex.upah", $tim->upah) }}" step="0.01"
                                                    min="0">
                                            </div>
                                            <div class="col-md-5">
                                                <label class="form-label small">Keterangan</label>
                                                <input type="text" name="tims[{{ $timIndex }}][keterangan]"
                                                    class="form-control form-control-sm"
                                                    value="{{ old("tims.$timIndex.keterangan", $tim->keterangan) }}">
                                            </div>
                                            <div class="col-md-12">
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-danger btn-hapus-edit-tim">
                                                    <i class="fa fa-times"></i> Hapus Tim
                                                </button>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="row g-2 align-items-end mb-2 edit-item-tim">
                                            <div class="col-md-2">
                                                <label class="form-label small">Nama Tim</label>
                                                <input type="text" name="tims[0][nama_tim]"
                                                    class="form-control form-control-sm">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Berat</label>
                                                <input type="number" name="tims[0][berat]"
                                                    class="form-control form-control-sm edit-input-berat-tim"
                                                    step="0.01" min="0">
                                            </div>
                                            <div class="col-md-1">
                                                <label class="form-label small">Karung</label>
                                                <input type="number" name="tims[0][jumlah_karung]"
                                                    class="form-control form-control-sm edit-input-karung-tim"
                                                    min="0" readonly>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Upah/kg</label>
                                                <input type="number" name="tims[0][upah]"
                                                    class="form-control form-control-sm" step="0.01" min="0">
                                            </div>
                                            <div class="col-md-5">
                                                <label class="form-label small">Keterangan</label>
                                                <input type="text" name="tims[0][keterangan]"
                                                    class="form-control form-control-sm">
                                            </div>
                                            <div class="col-md-12">
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-danger btn-hapus-edit-tim">
                                                    <i class="fa fa-times"></i> Hapus Tim
                                                </button>
                                            </div>
                                        </div>
                                    @endforelse
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="fa fa-save"></i> Simpan Perubahan
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-edit-lansir"
                                        data-target="#edit-lansir-{{ $lansir->id }}">
                                        Batal
                                    </button>
                                </div>
                            </form>
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

                    </div>
                    <input type="hidden" name="jenis_lansir" id="jenisLansir" value="{{ $jenisLansirDefault }}">
                    @error('jenis_lansir')
                        <div class="text-danger small mb-3">{{ $message }}</div>
                    @enderror


                    @php
                        $ongkosAngkut = $penerima->penerima?->ongkos_angkut ?? 0;
                        $ongkosBongkar = $penerima->penerima?->ongkos_bongkar ?? 0;
                        $defaultOngkosMobil = $ongkosAngkut;
                    @endphp

                    {{-- Kendaraan Lansir --}}
                    <div class="mb-4" id="sectionMobil" @if ($isTimBongkarPage) style="display:none" @endif>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <h6 class="fw-semibold mb-0">Kendaraan Lansir <span class="text-danger">*</span></h6>
                                @if ($ongkosAngkut > 0)
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

                    {{-- Tim Bongkar --}}
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <h6 class="fw-semibold mb-0">Tim Bongkar <span class="text-danger">*</span></h6>
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
            var defaultUpahTim = {{ $penerima->penerima?->ongkos_bongkar ?? 0 }};

            function syncJenisLansir() {
                var isTimOnly = $('#jenisLansir').val() === 'tim_bongkar';
                $('#sectionMobil').toggle(!isTimOnly);
                $('#sectionMobil').find('input, button').prop('disabled', isTimOnly);
            }

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

            function tambahTimRow() {
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
            }

            // Tambah tim bongkar
            $('#btnTambahTim').on('click', function() {
                tambahTimRow();
            });

            $(document).on('click', '.btn-hapus-tim', function() {
                $(this).closest('.item-tim').remove();
                if ($('.item-tim').length === 0) {
                    tambahTimRow();
                }
            });

            $('#jenisLansir').on('change', syncJenisLansir);
            tambahTimRow();
            syncJenisLansir();
        </script>
    @endif

    <script>
        $(document).on('click', '.btn-edit-lansir', function() {
            var target = $(this).data('target');
            $(target).slideToggle(150);
        });

        $(document).on('input', '.edit-input-berat', function() {
            var berat = parseFloat($(this).val()) || 0;
            $(this).closest('.row').find('.edit-input-karung').val(berat > 0 ? Math.ceil(berat / 50) : '');
        });

        $(document).on('input', '.edit-input-berat-tim', function() {
            var berat = parseFloat($(this).val()) || 0;
            $(this).closest('.row').find('.edit-input-karung-tim').val(berat > 0 ? Math.ceil(berat / 50) : '');
        });

        function editMobilRow(index) {
            return `<div class="row g-2 align-items-end mb-2 edit-item-mobil">
                <div class="col-md-2">
                    <label class="form-label small">No. Polisi</label>
                    <input type="text" name="mobils[${index}][no_polisi]" class="form-control form-control-sm text-uppercase" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Nama Sopir</label>
                    <input type="text" name="mobils[${index}][nama_sopir]" class="form-control form-control-sm">
                </div>
                <div class="col-md-1">
                    <label class="form-label small">Berat</label>
                    <input type="number" name="mobils[${index}][berat]" class="form-control form-control-sm edit-input-berat" step="0.01" min="0">
                </div>
                <div class="col-md-1">
                    <label class="form-label small">Karung</label>
                    <input type="number" name="mobils[${index}][jumlah_karung]" class="form-control form-control-sm edit-input-karung" min="0" readonly>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Ongkos/kg</label>
                    <input type="number" name="mobils[${index}][ongkos]" class="form-control form-control-sm" step="0.01" min="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Keterangan</label>
                    <input type="text" name="mobils[${index}][keterangan]" class="form-control form-control-sm">
                </div>
                <div class="col-md-12">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-edit-mobil">
                        <i class="fa fa-times"></i> Hapus Kendaraan
                    </button>
                </div>
            </div>`;
        }

        function editTimRow(index) {
            return `<div class="row g-2 align-items-end mb-2 edit-item-tim">
                <div class="col-md-2">
                    <label class="form-label small">Nama Tim</label>
                    <input type="text" name="tims[${index}][nama_tim]" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Berat</label>
                    <input type="number" name="tims[${index}][berat]" class="form-control form-control-sm edit-input-berat-tim" step="0.01" min="0">
                </div>
                <div class="col-md-1">
                    <label class="form-label small">Karung</label>
                    <input type="number" name="tims[${index}][jumlah_karung]" class="form-control form-control-sm edit-input-karung-tim" min="0" readonly>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Upah/kg</label>
                    <input type="number" name="tims[${index}][upah]" class="form-control form-control-sm" step="0.01" min="0">
                </div>
                <div class="col-md-5">
                    <label class="form-label small">Keterangan</label>
                    <input type="text" name="tims[${index}][keterangan]" class="form-control form-control-sm">
                </div>
                <div class="col-md-12">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-edit-tim">
                        <i class="fa fa-times"></i> Hapus Tim
                    </button>
                </div>
            </div>`;
        }

        $(document).on('click', '.btn-tambah-edit-mobil', function() {
            var $form = $(this).closest('.edit-lansir-form');
            var $list = $form.find('.edit-list-mobil');
            var index = parseInt($list.data('next-index'), 10) || 0;

            $list.append(editMobilRow(index));
            $list.data('next-index', index + 1);
            $form.find('input[name="jenis_lansir"]').val('mobil_tim');
        });

        $(document).on('click', '.btn-hapus-edit-mobil', function() {
            var $form = $(this).closest('.edit-lansir-form');
            $(this).closest('.edit-item-mobil').remove();

            if ($form.find('.edit-item-mobil').length === 0) {
                $form.find('input[name="jenis_lansir"]').val('tim_bongkar');
            }
        });

        $(document).on('click', '.btn-tambah-edit-tim', function() {
            var $list = $(this).closest('.mb-3').find('.edit-list-tim');
            var index = parseInt($list.data('next-index'), 10) || 0;

            $list.append(editTimRow(index));
            $list.data('next-index', index + 1);
        });

        $(document).on('click', '.btn-hapus-edit-tim', function() {
            var $list = $(this).closest('.edit-list-tim');
            $(this).closest('.edit-item-tim').remove();

            if ($list.find('.edit-item-tim').length === 0) {
                var index = parseInt($list.data('next-index'), 10) || 0;
                $list.append(editTimRow(index));
                $list.data('next-index', index + 1);
            }
        });
    </script>
@endsection
