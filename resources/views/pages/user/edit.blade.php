@extends('layout.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form class="form-horizontal" method="post" action="{{ route('user.update', $user->id) }}">
                        @csrf
                        @method('put')
                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-2 control-label">Nama</label>
                                <div class="col-sm-7">
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        name="name" value="{{ old('name', $user->name) }}">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2 control-label">Password Baru <small>(kosongkan jika tidak
                                        diubah)</small></label>
                                <div class="col-sm-7">
                                    <input type="password" class="form-control" name="password">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2 control-label">Level Akses</label>
                                <div class="col-sm-7">
                                    <select class="form-control" name="level" id="level_akun">
                                        <option value="1" {{ $user->level == '1' ? 'selected' : '' }}>Pusat (Semua CV)
                                        </option>
                                        <option value="2" {{ $user->level == '2' ? 'selected' : '' }}>Per Cv</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group" id="unit_kerja" style="display:none;">
                                <label class="col-sm-2 control-label">CV</label>
                                <div class="col-sm-7">
                                    <select class="form-control select2" multiple name="id_cv[]" style="width: 100%">
                                        @foreach ($cv as $c)
                                            <option value="{{ $c->id }}"
                                                {{ in_array($c->id, $user_cv) ? 'selected' : '' }}>
                                                {{ $c->nama_cv }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2 control-label">Role</label>
                                <div class="col-sm-7">
                                    @foreach ($roles as $role)
                                        <label class="col-sm-3">
                                            <input type="checkbox" name="roles[]"
                                                {{ in_array($role->id, $user_roles) ? 'checked' : '' }}
                                                value="{{ $role->id }}">
                                            {{ $role->name }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">Status</label>
                                <div class="col-sm-7">
                                    <label><input type="checkbox" name="aktif" value="1"
                                            {{ $user->aktif ? 'checked' : '' }}> Aktif</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-offset-2 col-sm-7">
                                    <button class="btn btn-sm btn-primary" type="submit">Update</button>
                                    <a href="{{ route('user.index') }}" class="btn btn-sm btn-secondary">Batal</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @include('plugins.select2')
    <script>
        function confirmation(id) {
            alertify.confirm("Confirmation!", "Are sure to delete this data?", function() {
                $('#' + id).submit();
            }, function() {

            });
        }
        $(".select2").select2();



        $("#level_akun").on("change", function() {
            if ($(this).val() == '0') $("#unit_kerja").css('display', 'none');

            else $("#unit_kerja").css('display', '');
        });

        $(document).ready(function() {
            @if ($user->level == '2')
                $("#unit_kerja").css('display', '');
            @else
                $("#unit_kerja").css('display', 'none');
            @endif
        });
    </script>
@endsection
