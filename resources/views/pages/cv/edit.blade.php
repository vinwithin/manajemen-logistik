@extends('layout.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form class="form-horizontal" method="post" action="{{ route('perusahaan.update', $data->id) }}">
                        @csrf
                        @method('put')
                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-2 control-label">Nama CV / Perusahaan</label>
                                <input type="text" class="form-control @error('nama_cv') is-invalid @enderror"
                                    name="nama_cv" value="{{ old('nama_cv', $data->nama_cv) }}">
                                @error('nama_cv')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">Kode</label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror"
                                    name="code" value="{{ old('code', $data->code) }}">
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">Status</label>
                                <div>
                                    <label><input type="checkbox" name="is_aktif" value="1"
                                            {{ $data->is_aktif ? 'checked' : '' }}> Aktif</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <button class="btn btn-sm btn-primary" type="submit">Update</button>
                                <a href="{{ route('perusahaan.index') }}" class="btn btn-sm btn-secondary">Batal</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
