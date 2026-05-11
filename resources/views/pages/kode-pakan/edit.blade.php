@extends('layout.app')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Edit Kode Pakan</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('pakan.update', $data->id) }}">
                        @csrf
                        @method('put')
                        <div class="form-group mb-3">
                            <label class="form-label">Nama Pakan <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror"
                                value="{{ old('nama', $data->nama) }}" placeholder="cth: Broiler Starter">
                            @error('nama')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label class="form-label">Kode <span class="text-danger">*</span></label>
                            <input type="text" name="kode" class="form-control @error('kode') is-invalid @enderror"
                                value="{{ old('kode', $data->kode) }}" placeholder="cth: BR-S01"
                                style="text-transform:uppercase" maxlength="50">
                            <small class="text-muted">Kode unik untuk pakan ini</small>
                            @error('kode')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button class="btn btn-primary btn-sm" type="submit">Update</button>
                        <a href="{{ route('pakan.index') }}" class="btn btn-secondary btn-sm">Batal</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
