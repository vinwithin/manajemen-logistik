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

                        {{-- Header Kendaraan --}}
                        <div class="card mb-4">
                            <div class="card-header bg-light py-2">
                                <h6 class="fw-bold mb-0">Informasi Kendaraan</h6>
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
                                        <label class="form-label">No. Polisi <span class="text-danger">*</span></label>
                                        <input type="text" name="no_polisi" class="form-control text-uppercase"
                                            value="{{ old('no_polisi') }}" placeholder="B 1234 XY" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Nama Sopir</label>
                                        <input type="text" name="nama_sopir" class="form-control"
                                            value="{{ old('nama_sopir') }}" placeholder="Opsional">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">No. Surat Jalan</label>
                                        <input type="text" name="no_surat_jalan" class="form-control"
                                            value="{{ old('no_surat_jalan') }}" placeholder="Opsional">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tanggal Lansir <span class="text-danger">*</span></label>
                                        <input type="date" name="tanggal_lansir" class="form-control"
                                            value="{{ old('tanggal_lansir', date('Y-m-d')) }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Catatan</label>
                                        <input type="text" name="catatan" class="form-control"
                                            value="{{ old('catatan') }}" placeholder="Opsional">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Daftar Penerima --}}
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0">Daftar Penerima</h6>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnTambahPenerima">
                                    <i class="fa fa-plus"></i> Tambah Penerima
                                </button>
                            </div>
                            <div id="listPenerima"></div>
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

    {{-- Data stok untuk JavaScript --}}
    <script>
        var stokData = @json($stokList);
        var kodePakanList = @json($kodePakans);
        var tujuanList = @json($tujuans);
    </script>

    <script src="{{ asset('js/gudang-lansir-create.js') }}"></script>
@endsection
