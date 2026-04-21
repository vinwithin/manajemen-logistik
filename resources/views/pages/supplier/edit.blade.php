@extends('layout.app')

@section('content')
    <div class="row">
        <div class="col-12 col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Edit Supplier</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('supplier.update', $data->id) }}">
                        @csrf
                        @method('put')

                        <div class="form-group mb-3">
                            <label class="form-label">Nama Supplier <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror"
                                value="{{ old('nama', $data->nama) }}" placeholder="cth: PT. Charoen Pokphand">
                            @error('nama')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-4">
                            <label class="form-label">Initial <span class="text-danger">*</span></label>
                            <input type="text" name="initial" class="form-control @error('initial') is-invalid @enderror"
                                value="{{ old('initial', $data->initial) }}" placeholder="cth: CPI"
                                style="text-transform:uppercase" maxlength="20">
                            <small class="text-muted">Singkatan / kode unik supplier</small>
                            @error('initial')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button class="btn btn-primary btn-sm" type="submit">Update</button>
                        <a href="{{ route('supplier.index') }}" class="btn btn-secondary btn-sm">Batal</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
