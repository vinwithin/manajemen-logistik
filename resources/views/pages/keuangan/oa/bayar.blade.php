@extends('layout.app')
@section('content')
    <div class="row justify-content-center">
        <div class="col-12">

            {{-- Info kendaraan --}}
            <div class="card mb-3">
                <div class="card-body py-3">
                    <div class="row g-2 small">
                        <div class="col-6 col-md-3">
                            <div class="text-muted">No. PO</div>
                            <div class="fw-bold">{{ $kendaraan->po->no_po }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted">No. Polisi</div>
                            <div class="fw-bold">{{ $kendaraan->no_polisi }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted">Supplier</div>
                            <div>{{ $kendaraan->supplier?->nama ?? '-' }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted">Sopir</div>
                            <div>{{ $kendaraan->nama_sopir ?? '-' }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted">Total KG</div>
                            <div>{{ number_format($kendaraan->total_kg, 0, ',', '.') }} kg</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted">Total OA</div>
                            <div class="fw-bold text-primary">Rp {{ number_format($tagihan, 0, ',', '.') }}</div>
                        </div>
                        @if ($kendaraan->dp_nominal > 0)
                            <div class="col-6 col-md-3">
                                <div class="text-muted">DP Supplier</div>
                                <div class="fw-bold text-info">
                                    Rp {{ number_format($kendaraan->dp_nominal, 0, ',', '.') }}
                                </div>
                            </div>
                        @endif
                        @if ($kendaraan->oaPayment)
                            <div class="col-6 col-md-3">
                                <div class="text-muted">Sudah Dibayar</div>
                                <div class="fw-bold text-success">
                                    Rp {{ number_format($kendaraan->oaPayment->jumlah_bayar, 0, ',', '.') }}
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Detail penerima dan pakan --}}
                    @if ($kendaraan->penerimas->count())
                        <div class="mt-2 pt-2 border-top">
                            <div class="text-muted small mb-1">Rincian Penerima & Pakan:</div>
                            @foreach ($kendaraan->penerimas as $penerima)
                                <div class="mb-3">
                                    <div class="fw-bold small">{{ $penerima->nama_penerima }} -
                                        {{ $penerima->tujuan?->nama ?? '-' }}</div>
                                    @if ($penerima->pakans->count())
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered mb-0 small">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Kode Pakan</th>
                                                        <th class="text-end">KG</th>
                                                        <th class="text-end">Ongkos OA/kg</th>
                                                        <th class="text-end">Total OA</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($penerima->pakans as $pk)
                                                        <tr>
                                                            <td>{{ $pk->kodePakan?->kode ?? '-' }}</td>
                                                            <td class="text-end">
                                                                {{ number_format($pk->jumlah_kg, 0, ',', '.') }}</td>
                                                            <td class="text-end">Rp
                                                                {{ number_format($pk->ongkos_oa, 0, ',', '.') }}</td>
                                                            <td class="text-end">Rp
                                                                {{ number_format($pk->total_oa, 0, ',', '.') }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($kendaraan->oaPayment && $kendaraan->oaPayment->sisa_tagihan > 0)
                        <div class="alert alert-warning py-2 mt-2 mb-0 small">
                            Sisa tagihan: <strong>Rp
                                {{ number_format($kendaraan->oaPayment->sisa_tagihan, 0, ',', '.') }}</strong>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Form bayar --}}
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Catat Pembayaran OA</h6>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('keuangan.oa.store-bayar', encrypt($kendaraan->id)) }}"
                        enctype="multipart/form-data">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Jumlah Bayar <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="jumlah_bayar"
                                        class="form-control @error('jumlah_bayar') is-invalid @enderror"
                                        value="{{ old('jumlah_bayar', $kendaraan->oaPayment?->sisa_tagihan ?? $tagihan) }}"
                                        step="1" min="1">
                                    @error('jumlah_bayar')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Bayar <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_bayar"
                                    class="form-control @error('tanggal_bayar') is-invalid @enderror"
                                    value="{{ old('tanggal_bayar', date('Y-m-d')) }}">
                                @error('tanggal_bayar')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Metode Bayar <span class="text-danger">*</span></label>
                                <select name="metode_bayar" class="form-select @error('metode_bayar') is-invalid @enderror">
                                    <option value="">-- Pilih --</option>
                                    @foreach (\App\Models\OaPayment::METODE as $key => $label)
                                        <option value="{{ $key }}"
                                            {{ old('metode_bayar') === $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('metode_bayar')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Bukti Bayar</label>
                                <input type="file" name="bukti_bayar"
                                    class="form-control @error('bukti_bayar') is-invalid @enderror"
                                    accept=".jpg,.jpeg,.png,.pdf">
                                <small class="text-muted">JPG, PNG, PDF · Maks 5MB</small>
                                @error('bukti_bayar')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Keterangan</label>
                                <input type="text" name="keterangan" class="form-control"
                                    value="{{ old('keterangan') }}" placeholder="Opsional">
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button class="btn btn-primary" type="submit">
                                <i class="fa fa-save"></i> Simpan Pembayaran
                            </button>
                            <a href="{{ route('keuangan.oa.index') }}" class="btn btn-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection
