@extends('layout.app')
@section('content')
    @php
        $isEdit = isset($rl);
        $title = $isEdit ? 'Edit Rugi Laba' : 'Input Rugi Laba';
        $action = $isEdit ? route('keuangan.rugi-laba.store') : route('keuangan.rugi-laba.store');
        $typeLabels = [
            'gudang' => 'Gudang',
            'direct' => 'Direct PIR',
            'co_farm' => 'Co Farm',
            'rent_farm' => 'Rent Farm',
        ];
    @endphp

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ $title }}</h5>
                    <a href="{{ route('keuangan.rugi-laba.index', ['cv_id' => $cvId]) }}" class="btn btn-sm btn-secondary">
                        <i class="fa fa-arrow-left"></i> Kembali
                    </a>
                </div>
                <div class="card-body">

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ $action }}">
                        @csrf
                        <input type="hidden" name="cv_id" value="{{ $cvId }}">
                        <input type="hidden" name="bulan" value="{{ $bulan }}">
                        <input type="hidden" name="tahun" value="{{ $tahun }}">

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">CV</label>
                                <input type="text" class="form-control bg-light" value="{{ $cv?->nama_cv ?? '-' }}"
                                    readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Periode</label>
                                <input type="text" class="form-control bg-light"
                                    value="{{ \App\Models\RugiLaba::namaBulan($bulan) }} {{ $tahun }}" readonly>
                            </div>
                        </div>

                        <div class="row g-4">

                            {{-- Kiri: Info Otomatis --}}
                            <div class="col-lg-5">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white py-2">
                                        <h6 class="mb-0"><i class="fa fa-magic"></i> Data Otomatis dari Sistem</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <table class="table table-sm mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Komponen</th>
                                                    <th class="text-end">Pembelian</th>
                                                    <th class="text-end">Penjualan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($autoData['types'] as $type)
                                                    <tr>
                                                        <td class="small">{{ $typeLabels[$type] ?? $type }}</td>
                                                        <td class="text-end small">
                                                            {{ number_format($autoData['pembelian'][$type], 0, ',', '.') }}
                                                        </td>
                                                        <td class="text-end small">
                                                            {{ number_format($autoData['penjualan'][$type], 0, ',', '.') }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                <tr class="table-secondary fw-bold">
                                                    <td>TOTAL</td>
                                                    <td class="text-end text-danger">
                                                        {{ number_format($autoData['totalPembelian'], 0, ',', '.') }}</td>
                                                    <td class="text-end text-success">
                                                        {{ number_format($autoData['totalPenjualan'], 0, ',', '.') }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <div class="p-2 border-top small text-muted">
                                            <div>Upah Bongkar/Muat (auto):
                                                <strong>{{ number_format($autoData['upahBongkarOtomatis'], 0, ',', '.') }}</strong>
                                            </div>
                                            <div>Mobil Lokal (auto):
                                                <strong>{{ number_format($autoData['mobilLokalOtomatis'], 0, ',', '.') }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Kanan: Form Input Manual --}}
                            <div class="col-lg-7">
                                <div class="card">
                                    <div class="card-header bg-info text-white py-2">
                                        <h6 class="mb-0">Input Biaya Operasional Manual</h6>
                                    </div>
                                    <div class="card-body">
                                        @php
                                            $formFields = [
                                                'gaji' => 'GAJI',
                                                'atk' => 'ATK',
                                                'pembayaran_supplier_lintas' => 'PEMBAYARAN SUPLIER LINTAS',
                                                'pembayaran_mobil_lokal' => 'PEMBAYARAN MOBIL LOKAL (tambahan)',
                                                'sharing_fee' => 'SHARING FEE',
                                                'sharing_profit' => 'SHARING PROFIT',
                                                'perjalanan_dinas' => 'PERJALANAN DINAS',
                                                'entertain' => 'ENTERTAIN',
                                                'adm_bank' => 'ADM BANK',
                                                'upah_bongkar' => 'UPAH BONGKAR (tambahan)',
                                                'upah_muat' => 'UPAH MUAT (tambahan)',
                                                'biaya_lain_lain' => 'BIAYA LAIN LAIN',
                                                'bbm' => 'BBM',
                                                'listrik' => 'LISTRIK',
                                                'pdam' => 'PDAM',
                                                'lingkungan' => 'LINGKUNGAN',
                                            ];
                                        @endphp

                                        <div class="row g-2">
                                            @foreach ($formFields as $field => $label)
                                                <div class="col-12">
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text"
                                                            style="width:240px; font-size:11px; text-align:left;">
                                                            {{ $label }}
                                                        </span>
                                                        <input type="number" name="{{ $field }}"
                                                            class="form-control form-control-sm text-end"
                                                            value="{{ old($field, $isEdit ? (float) $rl->$field : 0) }}"
                                                            placeholder="0" step="1" min="0">
                                                    </div>
                                                </div>
                                            @endforeach

                                            <div class="col-12 mt-2">
                                                <label class="form-label small">Catatan</label>
                                                <textarea name="catatan" class="form-control form-control-sm" rows="2" placeholder="Opsional">{{ old('catatan', $isEdit ? $rl->catatan : '') }}</textarea>
                                            </div>

                                            <div class="col-12 mt-3">
                                                <button type="submit" class="btn btn-primary w-100">
                                                    <i class="fa fa-save"></i> Simpan & Lihat Laporan
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
