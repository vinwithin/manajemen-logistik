@extends('layout.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Tambah Mobil</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('mobil.store') }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Nopol <span class="text-danger">*</span></label>
                                    <input type="text" name="nopol"
                                        class="form-control @error('nopol') is-invalid @enderror"
                                        value="{{ old('nopol') }}" placeholder="BH 1234 XX">
                                    @error('nopol')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Nama Sopir</label>
                                    <input type="text" name="nama_sopir"
                                        class="form-control @error('nama_sopir') is-invalid @enderror"
                                        value="{{ old('nama_sopir') }}" placeholder="Nama sopir">
                                    @error('nama_sopir')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">No HP</label>
                                    <input type="text" name="no_hp"
                                        class="form-control @error('no_hp') is-invalid @enderror"
                                        value="{{ old('no_hp') }}" placeholder="08xx-xxxx-xxxx">
                                    @error('no_hp')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check mb-3 mt-md-4">
                                    <input type="checkbox" name="is_aktif" class="form-check-input" id="is_aktif"
                                        value="1" {{ old('is_aktif', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_aktif">
                                        Aktif
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button class="btn btn-primary btn-sm" type="submit">Simpan</button>
                            <a href="{{ route('mobil.index') }}" class="btn btn-secondary btn-sm">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
