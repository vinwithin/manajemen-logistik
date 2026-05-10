@extends('layout.app')
@section('content')

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0"><i class="fa fa-balance-scale text-primary"></i> Laporan Rugi Laba</h5>
                    @if ($cvId)
                        <a href="{{ route('keuangan.rugi-laba.create', ['cv_id' => $cvId, 'tahun' => $tahun]) }}"
                            class="btn btn-sm btn-primary">
                            <i class="fa fa-plus"></i> Input Bulan Baru
                        </a>
                    @endif
                </div>

                {{-- Filter --}}
                <div class="card-body border-bottom pb-3">
                    <form method="GET" action="{{ route('keuangan.rugi-laba.index') }}" class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small">CV</label>
                            <select name="cv_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">-- Pilih CV --</option>
                                @foreach ($cvList as $c)
                                    <option value="{{ $c->id }}" {{ $cvId == $c->id ? 'selected' : '' }}>
                                        {{ $c->nama_cv }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Tahun</label>
                            <select name="tahun" class="form-select form-select-sm" onchange="this.form.submit()">
                                @foreach (range(now()->year, 2020) as $y)
                                    <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>
                                        {{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>

                <div class="card-body">
                    @if (!$cvId)
                        <div class="alert alert-info">Pilih CV untuk menampilkan daftar laporan.</div>
                    @else
                        @php
                            $bulanAda = $records->pluck('bulan')->toArray();
                            $akumPembelian = 0;
                            $akumPenjualan = 0;
                            $akumBiaya = 0;
                            $akumLabaKotor = 0;
                            $akumLabaBersih = 0;
                            foreach ($summary as $s) {
                                $akumPembelian += $s['totalPembelian'];
                                $akumPenjualan += $s['totalPenjualan'];
                                $akumBiaya += $s['totalBiaya'];
                                $akumLabaKotor += $s['labaKotor'];
                                $akumLabaBersih += $s['labaBersih'];
                            }
                            $fmt = fn($v) => number_format((float) $v, 0, ',', '.');
                        @endphp

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Bulan</th>
                                        <th class="text-end">Total Pembelian</th>
                                        <th class="text-end">Total Penjualan</th>
                                        <th class="text-end">Biaya Operasional</th>
                                        <th class="text-end">Laba Kotor</th>
                                        <th class="text-end">Laba Bersih</th>
                                        <th width="110px" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach (range(1, 12) as $b)
                                        @php
                                            $rl = $records->firstWhere('bulan', $b);
                                            $s = $rl ? $summary[$rl->id] ?? null : null;
                                        @endphp
                                        <tr class="{{ $rl ? '' : 'text-muted' }}">
                                            <td class="fw-semibold">
                                                {{ \App\Models\RugiLaba::namaBulan($b) }}
                                                @if ($b == now()->month && $tahun == now()->year)
                                                    <span class="badge bg-info ms-1">Bulan Ini</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                {{ $s ? $fmt($s['totalPembelian']) : '—' }}
                                            </td>
                                            <td class="text-end">
                                                {{ $s ? $fmt($s['totalPenjualan']) : '—' }}
                                            </td>
                                            <td class="text-end">
                                                {{ $s ? $fmt($s['totalBiaya']) : '—' }}
                                            </td>
                                            <td class="text-end fw-semibold">
                                                @if ($s)
                                                    <span
                                                        class="{{ $s['labaKotor'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                        {{ $fmt($s['labaKotor']) }}
                                                    </span>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="text-end fw-bold">
                                                @if ($s)
                                                    <span
                                                        class="{{ $s['labaBersih'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                        {{ $fmt($s['labaBersih']) }}
                                                    </span>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if ($rl)
                                                    <div class="d-flex gap-1 justify-content-center">
                                                        <a href="{{ route('keuangan.rugi-laba.harian', $rl->id) }}"
                                                            class="btn btn-xs btn-primary" title="Input Harian">
                                                            <i data-feather="calendar"></i>
                                                        </a>
                                                        <a href="{{ route('keuangan.rugi-laba.show', $rl->id) }}"
                                                            class="btn btn-xs btn-info text-white" title="Detail">
                                                            <i data-feather="eye"></i>
                                                        </a>
                                                        <a href="{{ route('keuangan.rugi-laba.edit', $rl->id) }}"
                                                            class="btn btn-xs btn-warning" title="Edit">
                                                            <i data-feather="edit"></i>

                                                        </a>
                                                        <a href="{{ route('keuangan.rugi-laba.export', $rl->id) }}"
                                                            class="btn btn-xs btn-success" title="Export Excel">
                                                            <i data-feather="save"></i>
                                                        </a>
                                                    </div>
                                                @else
                                                    <a href="{{ route('keuangan.rugi-laba.create', ['cv_id' => $cvId, 'bulan' => $b, 'tahun' => $tahun]) }}"
                                                        class="btn btn-xs btn-outline-primary" title="Input">
                                                        <i class="fa fa-plus"></i> Input
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>

                                {{-- Baris Akumulasi Tahunan --}}
                                @if ($records->isNotEmpty())
                                    <tfoot>
                                        <tr class="table-dark fw-bold">
                                            <td>TOTAL {{ $tahun }}</td>
                                            <td class="text-end">{{ $fmt($akumPembelian) }}</td>
                                            <td class="text-end">{{ $fmt($akumPenjualan) }}</td>
                                            <td class="text-end">{{ $fmt($akumBiaya) }}</td>
                                            <td
                                                class="text-end {{ $akumLabaKotor >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ $fmt($akumLabaKotor) }}
                                            </td>
                                            <td
                                                class="text-end {{ $akumLabaBersih >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ $fmt($akumLabaBersih) }}
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>

                        @if ($records->isEmpty())
                            <div class="text-center text-muted py-3">
                                Belum ada data untuk {{ $cv?->nama_cv }} tahun {{ $tahun }}.
                                <a href="{{ route('keuangan.rugi-laba.create', ['cv_id' => $cvId, 'tahun' => $tahun]) }}">
                                    Input bulan pertama →
                                </a>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection
