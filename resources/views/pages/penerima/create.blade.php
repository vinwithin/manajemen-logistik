@extends('layout.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Tambah Penerima</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('penerima.store') }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Nama Penerima <span class="text-danger">*</span></label>
                                    <input type="text" name="nama"
                                        class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama') }}"
                                        placeholder="Nama peternak/penerima">
                                    @error('nama')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Tujuan <span class="text-danger">*</span></label>
                                    <select name="tujuan_id" class="form-select @error('tujuan_id') is-invalid @enderror">
                                        <option value="">-- Pilih Tujuan --</option>
                                        @foreach ($tujuans as $t)
                                            <option value="{{ $t->id }}"
                                                {{ old('tujuan_id') == $t->id ? 'selected' : '' }}>
                                                {{ $t->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('tujuan_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Ongkos Angkut (Rp/kg) <span
                                            class="text-danger">*</span></label>
                                    <input type="number" name="ongkos_angkut"
                                        class="form-control @error('ongkos_angkut') is-invalid @enderror"
                                        value="{{ old('ongkos_angkut', 0) }}" placeholder="0" step="0.01" min="0">
                                    <small class="text-muted">Ongkos untuk lansir mobil</small>
                                    @error('ongkos_angkut')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Ongkos Bongkar (Rp/kg)</label>
                                    <input type="number" name="ongkos_bongkar"
                                        class="form-control @error('ongkos_bongkar') is-invalid @enderror"
                                        value="{{ old('ongkos_bongkar', 0) }}" placeholder="0" step="0.01"
                                        min="0">
                                    <small class="text-muted">Ongkos untuk tim bongkar (Bangko dan Bungo)</small>
                                    @error('ongkos_bongkar')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Telepon</label>
                                    <input type="text" name="telepon"
                                        class="form-control @error('telepon') is-invalid @enderror"
                                        value="{{ old('telepon') }}" placeholder="08xx-xxxx-xxxx">
                                    @error('telepon')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-group mb-3">
                                    <label class="form-label">Alamat</label>
                                    <textarea name="alamat" class="form-control @error('alamat') is-invalid @enderror" rows="3"
                                        placeholder="Alamat lengkap penerima">{{ old('alamat') }}</textarea>
                                    @error('alamat')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check mb-3">
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
                            <a href="{{ route('penerima.index') }}" class="btn btn-secondary btn-sm">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
