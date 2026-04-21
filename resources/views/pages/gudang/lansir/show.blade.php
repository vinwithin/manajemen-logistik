@extends('layout.app')
@section('content')
    {{-- Header --}}
    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h5 class="mb-1 fw-bold">Lansir Gudang - {{ $kendaraan->no_polisi }}</h5>
                    <div class="text-muted small mb-1">
                        {{ $kendaraan->tanggal_lansir->format('d M Y') }} &nbsp;·&nbsp;
                        <strong>{{ $kendaraan->gudang?->nama ?? '-' }}</strong>
                        @if ($kendaraan->nama_sopir)
                            &nbsp;·&nbsp; Sopir: {{ $kendaraan->nama_sopir }}
                        @endif
                        @if ($kendaraan->no_surat_jalan)
                            &nbsp;·&nbsp; SJ: {{ $kendaraan->no_surat_jalan }}
                        @endif
                    </div>
                    @if ($kendaraan->catatan)
                        <div class="text-muted small"><i class="fa fa-sticky-note"></i> {{ $kendaraan->catatan }}</div>
                    @endif
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <a href="{{ route('gudang.lansir.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fa fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Detail per Penerima --}}
    @forelse ($kendaraan->penerimas as $pi => $penerima)
        <div class="card mb-3">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <div>
                    <span class="fw-bold"><i class="fa fa-user"></i> {{ $penerima->nama_penerima }}</span>
                    @if ($penerima->tujuan)
                        <span class="text-muted small ms-2">→ {{ $penerima->tujuan->nama }}</span>
                    @endif

                    {{-- Status Badge --}}
                    @if ($penerima->status === 'dalam_perjalanan')
                        <span class="badge bg-warning ms-2">Dalam Perjalanan</span>
                    @elseif ($penerima->status === 'tiba')
                        <span class="badge bg-info ms-2">Tiba</span>
                    @elseif ($penerima->status === 'selesai')
                        <span class="badge bg-success ms-2">Selesai</span>
                    @endif
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary">{{ number_format($penerima->total_kg, 0, ',', '.') }} kg</span>
                    <span class="badge bg-secondary">{{ number_format($penerima->total_karung, 0, ',', '.') }}
                        karung</span>

                    {{-- Action Buttons --}}
                    @if ($penerima->status === 'dalam_perjalanan')
                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal"
                            data-bs-target="#modalTiba{{ $penerima->id }}">
                            <i class="fa fa-check"></i> Tiba
                        </button>
                    @elseif ($penerima->status === 'tiba')
                        <form action="{{ route('gudang.lansir.penerima.update-status', encrypt($penerima->id)) }}"
                            method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="status" value="selesai">
                            <button type="submit" class="btn btn-sm btn-success"
                                onclick="return confirm('Tandai penerima ini sebagai Selesai?')">
                                <i class="fa fa-check-circle"></i> Selesai
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="card-body p-0">
                {{-- Tabel Pakan --}}
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" rowspan="2">#</th>
                                <th rowspan="2">Kode Pakan</th>
                                @foreach ($kodePakanList as $kp)
                                    <th class="text-center">{{ $kp->kode }}</th>
                                @endforeach
                                <th class="text-end" rowspan="2">Total KG</th>
                                <th class="text-end" rowspan="2">Total Karung</th>
                                <th class="text-end" rowspan="2">Total Ongkos OA</th>
                            </tr>
                            <tr>
                                @foreach ($kodePakanList as $kp)
                                    <th class="text-center text-muted small">(kg)</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center">{{ $pi + 1 }}</td>
                                <td>Detail Pakan</td>
                                @php
                                    $pakanMap = $penerima->pakans->keyBy('kode_pakan_id');
                                @endphp
                                @foreach ($kodePakanList as $kp)
                                    @php $pk = $pakanMap[$kp->id] ?? null; @endphp
                                    <td class="text-center">
                                        @if ($pk)
                                            <span
                                                title="Ongkos OA: Rp {{ number_format($pk->ongkos_oa, 0, ',', '.') }}/kg">
                                                {{ number_format($pk->jumlah_kg, 0, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="text-end fw-bold">{{ number_format($penerima->total_kg, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($penerima->total_karung, 0, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format($penerima->total_oa, 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Detail Pakan (List) --}}
                @if ($penerima->pakans->count() > 0)
                    <div class="p-3 border-top">
                        <h6 class="fw-semibold mb-2">Detail Pakan:</h6>
                        <div class="row g-2">
                            @foreach ($penerima->pakans as $pakan)
                                <div class="col-md-6">
                                    <div class="border rounded p-2 small">
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-semibold">{{ $pakan->kodePakan?->kode ?? '-' }}</span>
                                            <span class="text-muted">{{ $pakan->kodePakan?->nama ?? '-' }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mt-1">
                                            <span>{{ number_format($pakan->jumlah_kg, 0, ',', '.') }} kg</span>
                                            <span>{{ number_format($pakan->jumlah_karung, 0, ',', '.') }} karung</span>
                                            <span>Rp {{ number_format($pakan->ongkos_oa, 0, ',', '.') }}/kg</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Tim Bongkar --}}
                @if ($penerima->tims->count() > 0)
                    <div class="p-3 border-top bg-light">
                        <h6 class="fw-semibold mb-2">Tim Bongkar:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama Tim</th>
                                        <th class="text-end">Jumlah (kg)</th>
                                        <th class="text-end">Upah per KG</th>
                                        <th class="text-end">Total Upah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($penerima->tims as $tim)
                                        <tr>
                                            <td>{{ $tim->nama_tim }}</td>
                                            <td class="text-end">{{ number_format($tim->jumlah_kg, 0, ',', '.') }}</td>
                                            <td class="text-end">
                                                @if ($tim->upah_per_kg)
                                                    Rp {{ number_format($tim->upah_per_kg, 0, ',', '.') }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-end fw-bold">
                                                Rp {{ number_format($tim->total_upah, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr class="table-secondary">
                                        <td colspan="3" class="text-end fw-bold">Total Upah Tim:</td>
                                        <td class="text-end fw-bold">
                                            Rp {{ number_format($penerima->tims->sum('total_upah'), 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="alert alert-info">Belum ada penerima dalam lansir ini.</div>
    @endforelse

    {{-- Grand Total --}}
    @if ($kendaraan->penerimas->count() > 0)
        <div class="card mb-3 border-dark">
            <div class="card-body py-2">
                <div class="row g-3 text-center">
                    <div class="col-md-3">
                        <div class="text-muted small">Total Muatan Kendaraan</div>
                        <div class="fw-bold fs-5">{{ number_format($kendaraan->total_kg, 0, ',', '.') }} kg</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Total Karung</div>
                        <div class="fw-bold fs-5">{{ number_format($kendaraan->total_karung, 0, ',', '.') }} karung</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Total Ongkos Angkut</div>
                        <div class="fw-bold fs-5 text-primary">
                            Rp {{ number_format($kendaraan->penerimas->sum('total_oa'), 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Total Upah Tim Bongkar</div>
                        <div class="fw-bold fs-5 text-success">
                            Rp {{ number_format($kendaraan->penerimas->flatMap->tims->sum('total_upah'), 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Info Footer --}}
    <div class="card">
        <div class="card-body py-2">
            <div class="row text-center small text-muted">
                <div class="col-md-6">
                    <i class="fa fa-user"></i> Dibuat oleh: {{ $kendaraan->creator?->name ?? '-' }}
                </div>
                <div class="col-md-6">
                    <i class="fa fa-clock-o"></i> {{ $kendaraan->created_at?->format('d/m/Y H:i') }}
                </div>
            </div>
        </div>
    </div>
@endsection
