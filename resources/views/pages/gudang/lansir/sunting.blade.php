@extends('layout.app')
@section('content')
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fa fa-edit"></i> Edit Lansir Gudang - {{ $header->no_lansir }}
                    </h5>
                    <a href="{{ route('gudang.lansir.show', encrypt($header->id)) }}" class="btn btn-sm btn-secondary">
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

                    <form method="post" action="{{ route('gudang.lansir.update', encrypt($header->id)) }}" id="formLansir">
                        @csrf
                        @method('PUT')

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
                                                    {{ old('gudang_id', $header->gudang_id) == $gudang->id ? 'selected' : '' }}>
                                                    {{ $gudang->nama }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">CV</label>
                                        <select name="cv_id" class="form-select @error('cv_id') is-invalid @enderror">
                                            <option value="">-- Pilih CV --</option>
                                            @foreach ($cvList as $cv)
                                                <option value="{{ $cv->id }}"
                                                    {{ old('cv_id', $header->cv_id) == $cv->id ? 'selected' : '' }}>
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
                                            value="{{ old('tanggal_lansir', $header->tanggal_lansir?->format('Y-m-d')) }}" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Catatan</label>
                                        <input type="text" name="catatan" class="form-control"
                                            value="{{ old('catatan', $header->catatan) }}" placeholder="Opsional">
                                    </div>
                                </div>
                            </div>
                        </div>

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
                                <i class="fa fa-save"></i> Perbarui Lansir
                            </button>
                            <a href="{{ route('gudang.lansir.show', encrypt($header->id)) }}" class="btn btn-secondary">
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
        var existingData = @json($header);
    </script>

    <script src="{{ asset('js/gudang-lansir-edit.js') }}"></script>
@endsection
