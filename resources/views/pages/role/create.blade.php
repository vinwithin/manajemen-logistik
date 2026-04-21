@extends('layout.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form class="form-horizontal" method="post" action="{{ route('role.store') }}">
                        @csrf
                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-2 control-label">Nama Role</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                    name="name" value="{{ old('name') }}">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            @include('pages.role._permission_checkboxes', [
                                'permissions' => $permissions,
                                'selected' => old('permissions', []),
                            ])

                            <div class="form-group mt-3">
                                <button class="btn btn-sm btn-primary" type="submit">Simpan</button>
                                <a href="{{ route('role.index') }}" class="btn btn-sm btn-secondary">Batal</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
