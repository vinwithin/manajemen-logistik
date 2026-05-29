@extends('layout.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form class="form-horizontal" method="post" action="{{ route('user.store') }}">
                        @csrf
                        <div class="box-body">
                            <div class="form-group">
                                <label for="name" class="col-sm-2 control-label">Nama User</label>
                                <input id="name" type="text"
                                    class="form-control @error('name') is-invalid @enderror" name="name"
                                    value="{{ old('name') }}">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="email" class="col-sm-2 control-label">Email</label>
                                <input id="email" type="email"
                                    class="form-control @error('email') is-invalid @enderror" name="email"
                                    value="{{ old('email') }}">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="password" class="col-sm-2 control-label">Password</label>
                                <input id="password" type="password"
                                    class="form-control @error('password') is-invalid @enderror" name="password">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="level" class="col-sm-2 control-label">Level Akses</label>
                                <div class="col-sm-7">
                                    <select class="form-control" name="level" id="level_akun">
                                        <option value="1" {{ old('level') == '1' ? 'selected' : '' }}>Pusat (Semua Cv)
                                        </option>
                                        <option value="2" {{ old('level', '2') == '2' ? 'selected' : '' }}>Per Cv
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="level_tujuan" class="col-sm-2 control-label">Level Akses Tujuan</label>
                                <div class="col-sm-7">
                                    <select class="form-control" name="level_tujuan" id="level_tujuan">
                                        <option value="1" {{ old('level_tujuan') == '1' ? 'selected' : '' }}>Pusat (Semua Tujuan)
                                        </option>
                                        <option value="2" {{ old('level_tujuan', '2') == '2' ? 'selected' : '' }}>Per Tujuan
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group" id="unit_kerja" style="display:none;">
                                <label for="inputEmail3" class="col-sm-2 control-label">Cv</label>
                                <div class="col-sm-7">
                                    <select class="form-control select2" multiple name="id_cv[]" id="id_cv" style="width: 100%">
                                        @foreach ($data as $cv)
                                            <option value="{{ $cv->id }}">{{ $cv->nama_cv }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group" id="unit_tujuan" style="display:none;">
                                <label for="inputEmail3" class="col-sm-2 control-label">Tujuan</label>
                                <div class="col-sm-7">
                                    <select class="form-control select2" multiple name="id_tujuan[]" id="id_tujuan" style="width: 100%">
                                        @foreach ($tujuan as $t)
                                            <option value="{{ $t->id }}">{{ $t->nama }} ({{ $t->type }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2 control-label">Role</label>
                                <div class="col-sm-7">
                                    @foreach ($roles as $role)
                                        <label class="col-sm-3">
                                            <input type="checkbox" name="roles[]" value="{{ $role->id }}">
                                            {{ $role->name }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">Status</label>
                                <div class="col-sm-7">
                                    <label><input type="checkbox" name="aktif" value="1" checked> Aktif</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-offset-2 col-sm-7">
                                    <button class="btn btn-sm btn-primary" type="submit">Simpan</button>
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
        $("#id_pegawai").select2({
            placeholder: "Tentukan dosen atau pegawai..",
            ajax: {
                url: "",
                dataTyper: "json",
                data: function(param) {
                    var value = {
                        search: param.term,
                    }
                    return value;
                },
                processResults: function(hasil) {

                    return {
                        results: hasil,
                    }
                }
            }
        });

        $("#level_akun").on("change", function() {
            if ($(this).val() == '1') {
                $("#unit_kerja").css('display', 'none');
            } else {
                $("#unit_kerja").css('display', '');
            }
        });

        $("#level_tujuan").on("change", function() {
            if ($(this).val() == '1') {
                $("#unit_tujuan").css('display', 'none');
            } else {
                $("#unit_tujuan").css('display', '');
            }
        });

        // Set initial state on page load
        $(document).ready(function() {
            if ($("#level_akun").val() == '1') {
                $("#unit_kerja").css('display', 'none');
            } else {
                $("#unit_kerja").css('display', '');
            }

            if ($("#level_tujuan").val() == '1') {
                $("#unit_tujuan").css('display', 'none');
            } else {
                $("#unit_tujuan").css('display', '');
            }
        });
    </script>
@endsection
