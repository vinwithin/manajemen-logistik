@extends('layout.app')

@section('content')
    <div class="row">
        <div class="col-12 ">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Tambah Tujuan Pengiriman</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('tujuan.store') }}">
                        @csrf
                        <div class="form-group mb-3">
                            <label class="form-label">Nama Tujuan <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror"
                                value="{{ old('nama') }}" placeholder="cth: Jambi 1, Bangko, Bungo...">
                            @error('nama')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <div class="d-flex flex-wrap gap-3 mt-1">
                                @foreach ($types as $key => $label)
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="type"
                                            id="type_{{ $key }}" value="{{ $key }}"
                                            {{ old('type') === $key ? 'checked' : '' }}>
                                        <label class="form-check-label" for="type_{{ $key }}">
                                            {{ $label }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            @error('type')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-4">
                            <label class="form-label">Status</label>
                            <div>
                                <label>
                                    <input type="checkbox" name="is_aktif" value="1" checked> Aktif
                                </label>
                            </div>
                        </div>

                        <button class="btn btn-primary btn-sm" type="submit">Simpan</button>
                        <a href="{{ route('tujuan.index') }}" class="btn btn-secondary btn-sm">Batal</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
