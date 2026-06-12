@extends('layout.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Edit Supplier</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('supplier.update', $data->id) }}" id="formSupplier">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Nama Supplier <span class="text-danger">*</span></label>
                                    <input type="text" name="nama"
                                        class="form-control @error('nama') is-invalid @enderror"
                                        value="{{ old('nama', $data->nama) }}" placeholder="cth: PT. Charoen Pokphand">
                                    @error('nama')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Initial <span class="text-danger">*</span></label>
                                    <input type="text" name="initial"
                                        class="form-control @error('initial') is-invalid @enderror"
                                        value="{{ old('initial', $data->initial) }}" placeholder="cth: CPI"
                                        style="text-transform:uppercase" maxlength="20">
                                    <small class="text-muted">Singkatan / kode unik supplier</small>
                                    @error('initial')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Tujuan, Jenis Kendaraan, Ongkos Angkut & Harga PT SUM</h6>
                            <button type="button" class="btn btn-sm btn-success" id="btnAddTujuan">
                                <i class="fa fa-plus"></i> Tambah Tujuan
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th width="30%">Tujuan</th>
                                        <th width="22%">Jenis Kendaraan</th>
                                        <th width="20%">Ongkos Angkut (Rp/kg)</th>
                                        <th width="18%">Harga PT SUM (Rp/kg)</th>
                                        <th width="10%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="tujuanContainer">
                                    @php
                                        $existingTujuans = old(
                                            'tujuans',
                                            $data->tujuans
                                                ->map(function ($t) {
                                                    return [
                                                        'tujuan_id' => $t->id,
                                                        'jenis_kendaraan' => $t->pivot->jenis_kendaraan,
                                                        'ongkos_angkut' => $t->pivot->ongkos_angkut,
                                                        'harga_pt_sum' => $t->pivot->harga_pt_sum ?? 0,
                                                    ];
                                                })
                                                ->toArray(),
                                        );
                                    @endphp

                                    @foreach ($existingTujuans as $index => $tujuan)
                                        <tr class="tujuan-row">
                                            <td>
                                                <select name="tujuans[{{ $index }}][tujuan_id]"
                                                    class="form-control form-control-sm" required>
                                                    <option value="">-- Pilih Tujuan --</option>
                                                    @foreach ($tujuans as $t)
                                                        <option value="{{ $t->id }}"
                                                            {{ $tujuan['tujuan_id'] == $t->id ? 'selected' : '' }}>
                                                            {{ $t->nama }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="tujuans[{{ $index }}][jenis_kendaraan]"
                                                    class="form-control form-control-sm"
                                                    placeholder="Tronton, Colt Diesel, dll"
                                                    value="{{ $tujuan['jenis_kendaraan'] }}">
                                            </td>
                                            <td>
                                                <input type="number" name="tujuans[{{ $index }}][ongkos_angkut]"
                                                    class="form-control form-control-sm" placeholder="0"
                                                    value="{{ $tujuan['ongkos_angkut'] }}" step="0.01" min="0"
                                                    required>
                                            </td>
                                            <td>
                                                <input type="number" name="tujuans[{{ $index }}][harga_pt_sum]"
                                                    class="form-control form-control-sm" placeholder="0"
                                                    value="{{ $tujuan['harga_pt_sum'] ?? 0 }}" step="0.01" min="0">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-danger btnRemoveTujuan">
                                                   Edit
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            <button class="btn btn-primary btn-sm" type="submit">Update</button>
                            <a href="{{ route('supplier.index') }}" class="btn btn-secondary btn-sm">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        let tujuanIndex = {{ count($existingTujuans) }};
        const tujuanOptions = `
            <option value="">-- Pilih Tujuan --</option>
            @foreach ($tujuans as $t)
                <option value="{{ $t->id }}">{{ $t->nama }}</option>
            @endforeach
        `;

        $('#btnAddTujuan').click(function() {
            const html = `
                <tr class="tujuan-row">
                    <td>
                        <select name="tujuans[${tujuanIndex}][tujuan_id]" class="form-control form-control-sm" required>
                            ${tujuanOptions}
                        </select>
                    </td>
                    <td>
                        <input type="text" name="tujuans[${tujuanIndex}][jenis_kendaraan]" 
                            class="form-control form-control-sm" placeholder="Tronton, Colt Diesel, dll">
                    </td>
                    <td>
                        <input type="number" name="tujuans[${tujuanIndex}][ongkos_angkut]" 
                            class="form-control form-control-sm" placeholder="0" 
                            step="0.01" min="0" required>
                    </td>
                    <td>
                        <input type="number" name="tujuans[${tujuanIndex}][harga_pt_sum]" 
                            class="form-control form-control-sm" placeholder="0" 
                            step="0.01" min="0" value="0">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-danger btnRemoveTujuan">
                            Hapus
                        </button>
                    </td>
                </tr>
            `;
            $('#tujuanContainer').append(html);
            tujuanIndex++;
        });

        $(document).on('click', '.btnRemoveTujuan', function() {
            $(this).closest('.tujuan-row').remove();
        });
    </script>
@endsection
