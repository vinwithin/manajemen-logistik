@extends('layout.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Tambah CV / Perusahaan</h5>
                </div>
                <div class="card-body">
                    <form class="form-horizontal" method="post" action="{{ route('perusahaan.store') }}"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama CV / Perusahaan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('nama_cv') is-invalid @enderror"
                                    name="nama_cv" value="{{ old('nama_cv') }}">
                                @error('nama_cv')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Kode</label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror"
                                    name="code" value="{{ old('code') }}">
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <div class="mt-2">
                                    <label><input type="checkbox" name="is_aktif" value="1" checked> Aktif</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Logo Perusahaan</label>
                                <input type="file" class="form-control @error('logo') is-invalid @enderror"
                                    name="logo" accept="image/*" id="inputLogo">
                                <small class="text-muted">Format: JPG, PNG, GIF, SVG, WEBP. Maksimal 2MB</small>
                                @error('logo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="mt-2" id="previewLogo" style="display: none;">
                                    <img src="" alt="Preview Logo"
                                        style="max-width: 200px; max-height: 100px; border: 1px solid #ddd; padding: 5px;">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Alamat</label>
                                <input type="text" class="form-control @error('alamat') is-invalid @enderror"
                                    name="alamat" value="{{ old('alamat') }}" placeholder="Alamat lengkap CV">
                                @error('alamat')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nama Bank</label>
                                <input type="text" class="form-control @error('nama_bank') is-invalid @enderror"
                                    name="nama_bank" value="{{ old('nama_bank') }}" placeholder="Contoh: BCA, BRI, Mandiri">
                                @error('nama_bank')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">No. Rekening</label>
                                <input type="text" class="form-control @error('no_rekening') is-invalid @enderror"
                                    name="no_rekening" value="{{ old('no_rekening') }}" placeholder="Nomor rekening">
                                @error('no_rekening')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Atas Nama Rekening</label>
                                <input type="text" class="form-control @error('atas_nama_rekening') is-invalid @enderror"
                                    name="atas_nama_rekening" value="{{ old('atas_nama_rekening') }}"
                                    placeholder="Nama pemilik rekening">
                                @error('atas_nama_rekening')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Pimpinan</label>
                                <input type="text" class="form-control @error('nama_pimpinan') is-invalid @enderror"
                                    name="nama_pimpinan" value="{{ old('nama_pimpinan') }}"
                                    placeholder="Nama direktur / pimpinan">
                                @error('nama_pimpinan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Prefix Nomor Dokumen</label>
                                <input type="text" class="form-control @error('no_dokumen_prefix') is-invalid @enderror"
                                    name="no_dokumen_prefix" value="{{ old('no_dokumen_prefix') }}"
                                    placeholder="Contoh: 4-TR-JBI/GI">
                                <small class="text-muted">Digunakan sebagai awalan nomor kwitansi/nota PDF</small>
                                @error('no_dokumen_prefix')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 mt-2">
                                <button class="btn btn-primary" type="submit">Simpan</button>
                                <a href="{{ route('perusahaan.index') }}" class="btn btn-secondary">Batal</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('inputLogo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('previewLogo');
                    preview.querySelector('img').src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
@endpush
