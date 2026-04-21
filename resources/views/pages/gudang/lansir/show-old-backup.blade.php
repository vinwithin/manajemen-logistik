@extends('layout.app')
@section('content')

    @if (session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger py-2">{{ session('error') }}</div>
    @endif

    {{-- Header --}}
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center py-3">
            <div>
                <h5 class="mb-1 fw-bold">
                    <i class="fa fa-truck text-info"></i> Detail Lansir Gudang
                </h5>
                <div class="text-muted small">
                    {{ $lansir->tujuan?->nama ?? '-' }} &nbsp;·&nbsp;
                    {{ $lansir->kodePakan?->kode ?? '-' }} — {{ $lansir->kodePakan?->nama ?? '-' }} &nbsp;·&nbsp;
                    {{ $lansir->created_at->format('d M Y, H:i') }}
                </div>
            </div>
            <a href="{{ route('gudang.lansir.index') }}" class="btn btn-sm btn-secondary">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row g-3">

        {{-- ── Info Utama ── --}}
        <div class="col-12 col-md-5">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">Informasi Lansir</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted" style="width:45%">Tanggal</td>
                                <td class="fw-semibold">{{ $lansir->created_at->format('d M Y, H:i') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Gudang</td>
                                <td class="fw-semibold">{{ $lansir->tujuan?->nama ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Kode Pakan</td>
                                <td class="fw-semibold">{{ $lansir->kodePakan?->kode ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Nama Pakan</td>
                                <td>{{ $lansir->kodePakan?->nama ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Jumlah (kg)</td>
                                <td class="fw-bold text-primary fs-6">
                                    {{ number_format($lansir->jumlah_kg, 2, ',', '.') }} kg
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Jumlah (karung)</td>
                                <td>{{ number_format($lansir->jumlah_karung, 0, ',', '.') }} karung</td>
                            </tr>

                            <tr>
                                <td class="text-muted">Catatan</td>
                                <td>{{ $lansir->catatan ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Dibuat oleh</td>
                                <td>{{ $lansir->createdBy?->name ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── Mobil & Tim ── --}}
        <div class="col-12 col-md-7">

            {{-- Tabel Mobil --}}
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">🚛 Mobil Lansir</h6>
                    <span class="badge bg-info">{{ $lansir->mobils->count() }} mobil</span>
                </div>
                <div class="card-body p-0">
                    @if ($lansir->mobils->isEmpty())
                        <div class="text-center text-muted py-3 small">Tidak ada data mobil.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>No. Polisi</th>
                                        <th>Nama Sopir</th>
                                        <th>Berat (kg)</th>
                                        <th>Karung</th>
                                        <th>Ongkos (Rp)</th>
                                        <th>Total Ongkos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($lansir->mobils as $i => $mobil)
                                        @php $totalOngkos = ($mobil->berat ?? 0) * ($mobil->ongkos ?? 0); @endphp
                                        <tr>
                                            <td>{{ $i + 1 }}</td>
                                            <td><strong>{{ $mobil->no_polisi }}</strong></td>
                                            <td>{{ $mobil->nama_sopir ?? '-' }}</td>
                                            <td>
                                                {{ $mobil->berat ? number_format($mobil->berat, 2, ',', '.') . ' kg' : '-' }}
                                            </td>
                                            <td>
                                                {{ $mobil->jumlah_karung ? number_format($mobil->jumlah_karung, 0, ',', '.') : '-' }}
                                            </td>
                                            <td>
                                                {{ $mobil->ongkos ? 'Rp ' . number_format($mobil->ongkos, 0, ',', '.') : '-' }}
                                            </td>
                                            <td>
                                                {{ $totalOngkos ? 'Rp ' . number_format($totalOngkos, 0, ',', '.') : '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                @if ($lansir->mobils->count() > 1)
                                    @php
                                        $grandBerat = $lansir->mobils->sum('berat');
                                        $grandKarungMobil = $lansir->mobils->sum('jumlah_karung');
                                        $grandOngkos = $lansir->mobils->sum(
                                            fn($m) => ($m->berat ?? 0) * ($m->ongkos ?? 0),
                                        );
                                    @endphp
                                    <tfoot class="table-light fw-bold">
                                        <tr>
                                            <td colspan="3">Total</td>
                                            <td>{{ number_format($grandBerat, 2, ',', '.') }} kg</td>
                                            <td>{{ number_format($grandKarungMobil, 0, ',', '.') }}</td>
                                            <td>-</td>
                                            <td>Rp {{ number_format($grandOngkos, 0, ',', '.') }}</td>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Tabel Tim Bongkar --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">👷 Tim Bongkar</h6>
                    <span class="badge bg-secondary">{{ $lansir->tims->count() }} tim</span>
                </div>
                <div class="card-body p-0">
                    @if ($lansir->tims->isEmpty())
                        <div class="text-center text-muted py-3 small">Tidak ada data tim bongkar.</div>
                    @else
                        @php $totalBeratMobil = $lansir->mobils->sum('berat'); @endphp
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Tim / Orang</th>
                                        <th>Berat (kg)</th>
                                        <th>Karung</th>
                                        <th>Upah (Rp/kg)</th>
                                        <th>Total Upah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($lansir->tims as $i => $tim)
                                        @php
                                            $beratTim = $tim->berat ?? $totalBeratMobil;
                                            $totalUpah = $beratTim * ($tim->upah ?? 0);
                                        @endphp
                                        <tr>
                                            <td>{{ $i + 1 }}</td>
                                            <td><strong>{{ $tim->nama_tim }}</strong></td>
                                            <td>
                                                {{ $tim->berat ? number_format($tim->berat, 2, ',', '.') . ' kg' : '-' }}
                                            </td>
                                            <td>
                                                {{ $tim->jumlah_karung ? number_format($tim->jumlah_karung, 0, ',', '.') : '-' }}
                                            </td>
                                            <td>
                                                {{ $tim->upah ? 'Rp ' . number_format($tim->upah, 0, ',', '.') : '-' }}
                                            </td>
                                            <td>
                                                {{ $totalUpah ? 'Rp ' . number_format($totalUpah, 0, ',', '.') : '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                @if ($lansir->tims->count() > 1)
                                    @php
                                        $grandUpah = $lansir->tims->sum(function ($t) use ($totalBeratMobil) {
                                            return ($t->berat ?? $totalBeratMobil) * ($t->upah ?? 0);
                                        });
                                    @endphp
                                    <tfoot class="table-light fw-bold">
                                        <tr>
                                            <td colspan="5">Total Upah</td>
                                            <td>Rp {{ number_format($grandUpah, 0, ',', '.') }}</td>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
@endsection
