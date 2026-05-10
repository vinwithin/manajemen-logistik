@extends('layout.app')
@section('content')
    @php
        $typeLabels = [
            'gudang' => 'Gudang',
            'direct' => 'Direct PIR',
            'co_farm' => 'Co Farm',
            'rent_farm' => 'Rent Farm',
        ];
        $fmt = fn($v) => number_format((float) $v, 0, ',', '.');
    @endphp

    <div class="row">
        <div class="col-12">

            {{-- Header --}}
            <div class="card mb-3">
                <div class="card-body d-flex justify-content-between align-items-center py-3">
                    <div>
                        <h5 class="mb-1 fw-bold">Laporan Rugi Laba</h5>
                        <div class="text-muted small">
                            {{ $rl->cv->nama_cv }} &nbsp;·&nbsp;
                            {{ \App\Models\RugiLaba::namaBulan($rl->bulan) }} {{ $rl->tahun }}
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('keuangan.rugi-laba.harian', $rl->id) }}" class="btn btn-sm btn-primary">
                            <i class="fa fa-calendar-plus-o"></i> Input Harian
                        </a>
                        <a href="{{ route('keuangan.rugi-laba.export', $rl->id) }}" class="btn btn-sm btn-success">
                            <i class="fa fa-file-excel-o"></i> Export Excel
                        </a>
                        <a href="{{ route('keuangan.rugi-laba.edit', $rl->id) }}" class="btn btn-sm btn-warning">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('keuangan.rugi-laba.index', ['cv_id' => $rl->cv_id]) }}"
                            class="btn btn-sm btn-secondary">
                            <i class="fa fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Tabel Laporan --}}
            <div class="card">
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0">

                        {{-- A. PEMBELIAN --}}
                        <tr class="table-secondary">
                            <td width="40px" class="fw-bold">A.</td>
                            <td class="fw-bold">Pembelian</td>
                            <td width="160px"></td>
                        </tr>
                        @foreach ($data['types'] as $type)
                            <tr>
                                <td></td>
                                <td class="ps-4 text-muted">- {{ $typeLabels[$type] ?? $type }}</td>
                                <td class="text-end">{{ $fmt($data['pembelian'][$type]) }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td></td>
                            <td class="ps-4 text-muted">- Cab. Bungo</td>
                            <td class="text-end">0</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="ps-4 text-muted">- Transper Pakan</td>
                            <td class="text-end">0</td>
                        </tr>
                        <tr class="table-light">
                            <td></td>
                            <td class="text-center fw-bold">TOTAL</td>
                            <td class="text-end fw-bold text-danger">{{ $fmt($data['totalPembelian']) }}</td>
                        </tr>

                        <tr>
                            <td colspan="3" class="py-1 bg-white border-0"></td>
                        </tr>

                        {{-- B. PENJUALAN --}}
                        <tr class="table-secondary">
                            <td class="fw-bold">B.</td>
                            <td class="fw-bold">Penjualan</td>
                            <td></td>
                        </tr>
                        @foreach ($data['types'] as $type)
                            <tr>
                                <td></td>
                                <td class="ps-4 text-muted">- {{ $typeLabels[$type] ?? $type }}</td>
                                <td class="text-end">{{ $fmt($data['penjualan'][$type]) }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td></td>
                            <td class="ps-4 text-muted">- Cab. Bungo</td>
                            <td class="text-end">0</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="ps-4 text-muted">- Transper Pakan</td>
                            <td class="text-end">0</td>
                        </tr>
                        <tr class="table-light">
                            <td></td>
                            <td class="text-center fw-bold">TOTAL</td>
                            <td class="text-end fw-bold text-success">{{ $fmt($data['totalPenjualan']) }}</td>
                        </tr>

                        <tr>
                            <td colspan="3" class="py-1 bg-white border-0"></td>
                        </tr>

                        {{-- C. BIAYA OPERASIONAL --}}
                        <tr class="table-secondary">
                            <td class="fw-bold">C.</td>
                            <td class="fw-bold">Biaya Operasional</td>
                            <td></td>
                        </tr>
                        @php
                            $biayaRows = [
                                ['GAJI', $rl->gaji],
                                ['ATK', $rl->atk],
                                ['PEMBAYARAN SUPLIER LINTAS', $rl->pembayaran_supplier_lintas],
                                [
                                    'PEMBAYARAN MOBIL LOKAL',
                                    (float) $rl->pembayaran_mobil_lokal + $data['mobilLokalOtomatis'],
                                ],
                                ['SHARING FEE', $rl->sharing_fee],
                                ['SHARING PROFIT', $rl->sharing_profit],
                                ['PERJALANAN DINAS', $rl->perjalanan_dinas],
                                ['ENTERTAIN', $rl->entertain],
                                ['ADM BANK', $rl->adm_bank],
                                [
                                    'UPAH BONGKAR UPAH MUAT',
                                    (float) $rl->upah_bongkar + (float) $rl->upah_muat + $data['upahBongkarOtomatis'],
                                ],
                                ['BIAYA LAIN LAIN', $rl->biaya_lain_lain],
                                ['BBM', $rl->bbm],
                                ['LISTRIK', $rl->listrik],
                                ['PDAM', $rl->pdam],
                            ];
                        @endphp
                        @foreach ($biayaRows as [$label, $val])
                            <tr>
                                <td></td>
                                <td class="ps-3">{{ $label }}</td>
                                <td class="text-end">{{ $fmt($val) }}</td>
                            </tr>
                        @endforeach
                        <tr class="table-light">
                            <td></td>
                            <td class="text-center fw-bold">TOTAL</td>
                            <td class="text-end fw-bold text-danger">{{ $fmt($data['totalBiayaOperasional']) }}</td>
                        </tr>

                        <tr>
                            <td colspan="3" class="py-1 bg-white border-0"></td>
                        </tr>

                        {{-- D. LABA KOTOR --}}
                        <tr class="table-warning">
                            <td class="fw-bold">D.</td>
                            <td class="fw-bold">LABA KOTOR (B - A)</td>
                            <td class="text-end fw-bold {{ $data['labaKotor'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $fmt($data['labaKotor']) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-bold">E.</td>
                            <td>Pph 21 (LABA KOTOR X 0.5%)</td>
                            <td class="text-end">{{ $fmt($data['pph21']) }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">F.</td>
                            <td>Potongan Voucher</td>
                            <td class="text-end">{{ $fmt($data['voucher']) }}</td>
                        </tr>

                        <tr>
                            <td colspan="3" class="py-1 bg-white border-0"></td>
                        </tr>

                        {{-- G. LABA BERSIH --}}
                        <tr class="table-dark">
                            <td class="fw-bold text-dark">G.</td>
                            <td class="fw-bold text-dark">LABA BERSIH (D - C - E - F)</td>
                            <td class="text-end fw-bold {{ $data['labaBersih'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $fmt($data['labaBersih']) }}
                            </td>
                        </tr>

                    </table>
                </div>
            </div>

            @if ($rl->catatan)
                <div class="alert alert-light mt-3 small">
                    <strong>Catatan:</strong> {{ $rl->catatan }}
                </div>
            @endif

        </div>
    </div>
@endsection
