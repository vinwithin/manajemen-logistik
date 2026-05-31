@extends('layout.app')
@section('content')
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Input Lansir Gudang Baru</h5>
                    <a href="{{ route('gudang.lansir.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fa fa-arrow-left"></i> Kembali
                    </a>
                </div>
                <div class="card-body">
                    {{-- Error summary --}}
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show mb-4">
                            <strong><i class="fa fa-exclamation-triangle"></i> Terdapat kesalahan:</strong>
                            <ul class="mb-0 mt-1 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li class="small">{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form method="post" action="{{ route('gudang.lansir.store') }}" id="formLansir">
                        @csrf

                        {{-- Header Lansir --}}
                        <div class="card mb-4">
                            <div class="card-header bg-light py-2">
                                <h6 class="fw-bold mb-0">Informasi Lansir</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Gudang Asal <span class="text-danger">*</span></label>
                                        <select name="gudang_id" id="selectGudang" class="form-select" required>
                                            <option value="">-- Pilih Gudang --</option>
                                            @foreach ($gudangs as $gudang)
                                                <option value="{{ $gudang->id }}"
                                                    {{ old('gudang_id', $gudangId) == $gudang->id ? 'selected' : '' }}>
                                                    {{ $gudang->nama }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">CV <span class="text-danger">*</span></label>
                                        <select name="cv_id" class="form-select @error('cv_id') is-invalid @enderror"
                                            required>
                                            <option value="">-- Pilih CV --</option>
                                            @foreach ($cvList as $cv)
                                                <option value="{{ $cv->id }}"
                                                    {{ old('cv_id', session('active_cv')) == $cv->id ? 'selected' : '' }}>
                                                    {{ $cv->nama_cv }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('cv_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Tanggal Lansir <span class="text-danger">*</span></label>
                                        <input type="date" name="tanggal_lansir" class="form-control"
                                            value="{{ old('tanggal_lansir', date('Y-m-d')) }}" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Catatan</label>
                                        <input type="text" name="catatan" class="form-control"
                                            value="{{ old('catatan') }}" placeholder="Opsional">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Source dari PO (Optional) --}}
                        {{-- @if ($poPenerimaList->count() > 0)
                            <div class="card mb-4 border-info">
                                <div class="card-header bg-info bg-opacity-10 py-2">
                                    <h6 class="fw-bold mb-0 text-info">
                                        <i class="fa fa-link"></i> Source dari PO (Opsional)
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info alert-sm mb-3">
                                        <i class="fa fa-info-circle"></i>
                                        <small>
                                            Pilih PO Penerima jika lansir ini berasal dari barang yang sudah tiba di gudang
                                            dari supplier.
                                            Data penerima dan pakan akan otomatis terisi.
                                        </small>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <label class="form-label">Pilih PO Penerima</label>
                                            <select id="selectPoPenerima" class="form-select">
                                                <option value="">-- Pilih PO Penerima (Opsional) --</option>
                                                @foreach ($poPenerimaList as $pp)
                                                    <option value="{{ $pp->id }}"
                                                        data-po-no="{{ $pp->kendaraan->po->no_po }}"
                                                        data-tiba="{{ $pp->tiba_at?->format('d/m/Y H:i') }}">
                                                        PO: {{ $pp->kendaraan->po->no_po }} |
                                                        {{ $pp->nama_penerima }} |
                                                        {{ number_format($pp->total_kg, 0, ',', '.') }} kg |
                                                        Tiba: {{ $pp->tiba_at?->format('d/m/Y') }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="text-muted">
                                                Menampilkan PO yang sudah tiba di gudang dan belum dilansir
                                            </small>
                                        </div>
                                    </div>
                                    <div id="poInfoDisplay" class="mt-3" style="display: none;">
                                        <div class="card bg-light">
                                            <div class="card-body py-2">
                                                <div class="row small">
                                                    <div class="col-md-3">
                                                        <strong>No. PO:</strong> <span id="poNoDisplay"></span>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <strong>Penerima:</strong> <span id="poPenerimaDisplay"></span>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <strong>Tiba:</strong> <span id="poTibaDisplay"></span>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <button type="button" class="btn btn-sm btn-success"
                                                            id="btnAutoFill">
                                                            <i class="fa fa-magic"></i> Auto-Fill Data
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif --}}

                        {{-- Daftar Kendaraan --}}
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0">Daftar Kendaraan</h6>
                                <button type="button" class="btn btn-sm btn-outline-success" id="btnTambahKendaraan">
                                    <i class="fa fa-plus"></i> Tambah Kendaraan
                                </button>
                            </div>
                            <div id="listKendaraan"></div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> Simpan Lansir
                            </button>
                            <a href="{{ route('gudang.lansir.index') }}" class="btn btn-secondary">
                                <i class="fa fa-times"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        var stokData = @json($stokList);
        var kodePakanList = @json($kodePakans);
        var tujuanList = @json($tujuans);
        var penerimaList = @json($penerimaList);
    </script>

    <script src="{{ asset('js/gudang-lansir-create.js') }}"></script>
@endsection
