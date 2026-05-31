@extends('layout.app')
@section('content')

    {{-- Header --}}
    <div class="card mb-3 border-success">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h5 class="mb-1 fw-bold">
                        <i class="fa fa-truck text-success"></i> Lansir: {{ $header->no_lansir }}
                    </h5>
                    <div class="text-muted small mb-2">
                        <i class="fa fa-calendar"></i> {{ $header->tanggal_lansir->format('d M Y') }}
                        &nbsp;·&nbsp;
                        <i class="fa fa-warehouse"></i> <strong>{{ $header->gudang?->nama ?? '-' }}</strong>
                        @if ($header->cv)
                            &nbsp;·&nbsp;
                            <i class="fa fa-building"></i> <strong class="text-primary">{{ $header->cv->nama_cv }}</strong>
                        @endif
                        @if ($header->catatan)
                            &nbsp;·&nbsp; <i class="fa fa-sticky-note"></i> {{ $header->catatan }}
                        @endif
                    </div>
                    <div class="d-flex flex-wrap gap-1">
                        <span class="badge bg-success">{{ $header->jumlah_kendaraan }} Kendaraan</span>
                        <span class="badge bg-primary">{{ $header->jumlah_penerima }} Penerima</span>
                        <span class="badge bg-info">{{ number_format($header->total_kg, 0, ',', '.') }} kg</span>
                        <span class="badge bg-secondary">{{ number_format($header->total_karung, 0, ',', '.') }}
                            karung</span>
                    </div>
                </div>
                <a href="{{ route('gudang.lansir.index') }}" class="btn btn-sm btn-secondary">
                    <i class="fa fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    @forelse ($header->kendaraans as $ki => $kendaraan)
        <div class="card mb-4 border-info">
            <div class="card-header text-dark py-2">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="mb-0">
                        <i class="fa fa-truck"></i>
                        Kendaraan {{ $ki + 1 }}: <strong>{{ $kendaraan->no_polisi }}</strong>
                        @if ($kendaraan->nama_sopir)
                            <span class="fw-normal opacity-75">({{ $kendaraan->nama_sopir }})</span>
                        @endif
                    </h6>
                    <div class="d-flex gap-1 flex-wrap">
                        @if ($kendaraan->no_surat_jalan)
                            <span class="badge bg-light text-dark">SJ: {{ $kendaraan->no_surat_jalan }}</span>
                        @endif
                        <span class="badge bg-warning text-dark">{{ number_format($kendaraan->total_kg, 0, ',', '.') }}
                            kg</span>
                        <span class="badge bg-light text-dark">{{ number_format($kendaraan->total_karung, 0, ',', '.') }}
                            karung</span>
                        @php
                            $ongkosOaKendaraan =
                                $kendaraan->penerimas->flatMap->pakans->where('ongkos_oa', '>', 0)->first()
                                    ?->ongkos_oa ?? 0;
                            $ongkosAngkutKendaraan =
                                $kendaraan->penerimas->flatMap->tims->where('upah_per_kg', '>', 0)->first()
                                    ?->upah_per_kg ?? 0;
                        @endphp
                        @if ($ongkosOaKendaraan > 0)
                            <span class="badge bg-primary">OA: Rp
                                {{ number_format($ongkosOaKendaraan, 0, ',', '.') }}/kg</span>
                        @endif
                        @if ($ongkosAngkutKendaraan > 0)
                            <span class="badge bg-success">Angkut: Rp
                                {{ number_format($ongkosAngkutKendaraan, 0, ',', '.') }}/kg</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card-body p-3">
                @forelse ($kendaraan->penerimas as $pi => $penerima)
                    @php
                        $totalOaPenerima = $penerima->total_oa;
                        $totalUpahPenerima = $penerima->tims->sum('total_upah');
                    @endphp
                    <div class="card mb-3 {{ $loop->last ? 'mb-0' : '' }}">
                        <div
                            class="card-header py-2 d-flex justify-content-between align-items-center bg-light flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="fw-bold">
                                    <i class="fa fa-user text-primary"></i> {{ $penerima->nama_penerima }}
                                </span>
                                @if ($penerima->tujuan)
                                    <span class="text-muted small">→ {{ $penerima->tujuan->nama }}</span>
                                @endif
                                @if ($penerima->no_surat_jalan)
                                    <span class="badge bg-light text-dark">SJ: {{ $penerima->no_surat_jalan }}</span>
                                @endif
                                @if ($penerima->status === 'dalam_perjalanan')
                                    <span class="badge bg-warning text-dark">Dalam Perjalanan</span>
                                @elseif ($penerima->status === 'tiba')
                                    <span class="badge bg-info">Tiba</span>
                                @elseif ($penerima->status === 'selesai')
                                    <span class="badge bg-success">Selesai</span>
                                @endif
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="badge bg-primary">{{ number_format($penerima->total_kg, 0, ',', '.') }}
                                    kg</span>
                                <span class="badge bg-secondary">{{ number_format($penerima->total_karung, 0, ',', '.') }}
                                    karung</span>

                                @if ($penerima->status === 'dalam_perjalanan')
                                    <button type="button" class="btn btn-info" data-bs-toggle="modal"
                                        data-bs-target="#modalTiba{{ $penerima->id }}">
                                        <i class="fa fa-check"></i> Tiba
                                    </button>
                                @elseif ($penerima->status === 'tiba')
                                    <form
                                        action="{{ route('gudang.lansir.penerima.update-status', encrypt($penerima->id)) }}"
                                        method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="status" value="selesai">
                                        <button type="submit" class="btn btn-success"
                                            onclick="return confirm('Tandai penerima ini sebagai Selesai?')">
                                            <i class="fa fa-check-circle"></i> Selesai
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        <div class="card-body p-0">

                            @if (in_array($penerima->status, ['tiba', 'selesai']))
                                <div class="px-3 py-2 bg-light border-bottom">
                                    <div class="row g-2 small">
                                        <div class="col-md-4">
                                            <span class="text-muted">Waktu Tiba:</span>
                                            <strong
                                                class="ms-1">{{ $penerima->tiba_at?->format('d/m/Y H:i') ?? '-' }}</strong>
                                        </div>
                                        <div class="col-md-4">
                                            <span class="text-muted">Validasi Oleh:</span>
                                            <strong class="ms-1">{{ $penerima->validator?->name ?? '-' }}</strong>
                                        </div>
                                        <div class="col-md-4">
                                            @if ($penerima->bukti_tiba)
                                                <a href="{{ asset('storage/' . $penerima->bukti_tiba) }}" target="_blank"
                                                    class="btn btn-xs btn-outline-primary">
                                                    <i class="fa fa-image"></i> Lihat Bukti Tiba
                                                </a>
                                            @else
                                                <span class="text-muted small">Tidak ada bukti tiba</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if ($penerima->pakans->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="40px">#</th>
                                                <th>Kode Pakan</th>
                                                <th>Nama Pakan</th>
                                                <th class="text-end">Jumlah (kg)</th>
                                                <th class="text-end">Karung</th>
                                                <th class="text-end">Ongkos OA (Rp/kg)</th>
                                                <th class="text-end">Total OA</th>
                                                <th class="text-end">Harga PT Sum (Rp/kg)</th>
                                                <th class="text-end">Total PT Sum</th>
                                                <th>Keterangan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($penerima->pakans as $idx => $pakan)
                                                <tr>
                                                    <td class="text-center text-muted">{{ $idx + 1 }}</td>
                                                    <td><strong>{{ $pakan->kodePakan?->kode ?? '-' }}</strong></td>
                                                    <td class="text-muted">{{ $pakan->kodePakan?->nama ?? '-' }}</td>
                                                    <td class="text-end">
                                                        {{ number_format($pakan->jumlah_kg, 0, ',', '.') }}</td>
                                                    <td class="text-end">
                                                        {{ number_format($pakan->jumlah_karung, 0, ',', '.') }}</td>
                                                    <td class="text-end">
                                                        @if ($pakan->ongkos_oa > 0)
                                                            Rp {{ number_format($pakan->ongkos_oa, 0, ',', '.') }}
                                                        @else
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-end">
                                                        @php $totalOaPakan = $pakan->jumlah_kg * ($pakan->ongkos_oa ?? 0); @endphp
                                                        @if ($totalOaPakan > 0)
                                                            Rp {{ number_format($totalOaPakan, 0, ',', '.') }}
                                                        @else
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-end">
                                                        @if (($pakan->harga_pt_sum ?? 0) > 0)
                                                            Rp {{ number_format($pakan->harga_pt_sum, 0, ',', '.') }}
                                                        @else
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-end">
                                                        @php $totalPtSumPakan = $pakan->jumlah_kg * ($pakan->harga_pt_sum ?? 0); @endphp
                                                        @if ($totalPtSumPakan > 0)
                                                            Rp {{ number_format($totalPtSumPakan, 0, ',', '.') }}
                                                        @else
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-muted small">{{ $pakan->keterangan ?? '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="table-light fw-bold">
                                            <tr>
                                                <td colspan="3" class="text-end">Total</td>
                                                <td class="text-end">{{ number_format($penerima->total_kg, 0, ',', '.') }}
                                                </td>
                                                <td class="text-end">
                                                    {{ number_format($penerima->total_karung, 0, ',', '.') }}</td>
                                                <td></td>
                                                <td class="text-end text-primary">
                                                    Rp {{ number_format($totalOaPenerima, 0, ',', '.') }}
                                                </td>
                                                <td></td>
                                                <td class="text-end text-success">
                                                    Rp
                                                    {{ number_format($penerima->pakans->sum(fn($p) => $p->jumlah_kg * ($p->harga_pt_sum ?? 0)), 0, ',', '.') }}
                                                </td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @else
                                <div class="text-center text-muted py-3 small">Tidak ada data pakan.</div>
                            @endif

                            {{-- Tim Bongkar --}}
                            @if ($penerima->tims->count() > 0)
                                <div class="border-top">
                                    <div class="px-3 pt-2 pb-1">
                                        <span class="small fw-semibold text-muted">
                                            <i class="fa fa-users"></i> Tim Bongkar
                                        </span>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered mb-0 align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Nama Tim</th>
                                                    <th class="text-end">Jumlah (kg)</th>
                                                    <th class="text-end">Upah (Rp/kg)</th>
                                                    <th class="text-end">Total Upah</th>
                                                    <th>Keterangan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($penerima->tims as $tim)
                                                    <tr>
                                                        <td>{{ $tim->nama_tim }}</td>
                                                        <td class="text-end">
                                                            {{ number_format($tim->jumlah_kg, 0, ',', '.') }}</td>
                                                        <td class="text-end">
                                                            @if ($tim->upah_per_kg > 0)
                                                                Rp {{ number_format($tim->upah_per_kg, 0, ',', '.') }}
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-end fw-bold">
                                                            @if ($tim->total_upah > 0)
                                                                Rp {{ number_format($tim->total_upah, 0, ',', '.') }}
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-muted small">{{ $tim->keterangan ?? '—' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            @if ($penerima->tims->count() > 1)
                                                <tfoot class="table-light fw-bold">
                                                    <tr>
                                                        <td colspan="3" class="text-end">Total Upah Tim</td>
                                                        <td class="text-end text-success">
                                                            Rp {{ number_format($totalUpahPenerima, 0, ',', '.') }}
                                                        </td>
                                                        <td></td>
                                                    </tr>
                                                </tfoot>
                                            @endif
                                        </table>
                                    </div>
                                </div>
                            @endif

                            {{-- Subtotal Penerima --}}
                            <div
                                class="d-flex justify-content-between align-items-center px-3 py-2 bg-light border-top small fw-semibold text-muted">
                                <span>{{ number_format($penerima->total_kg, 0, ',', '.') }} kg &nbsp;·&nbsp;
                                    {{ number_format($penerima->total_karung, 0, ',', '.') }} karung</span>
                                <span>
                                    OA: <span class="text-primary">Rp
                                        {{ number_format($totalOaPenerima, 0, ',', '.') }}</span>
                                    @if ($totalUpahPenerima > 0)
                                        &nbsp;·&nbsp; Angkut: <span class="text-success">Rp
                                            {{ number_format($totalUpahPenerima, 0, ',', '.') }}</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Modal Tiba --}}
                    <div class="modal fade" id="modalTiba{{ $penerima->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="{{ route('gudang.lansir.penerima.update-status', encrypt($penerima->id)) }}"
                                    method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="status" value="tiba">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Tandai Tiba — {{ $penerima->nama_penerima }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="alert alert-info small mb-3">
                                            <i class="fa fa-info-circle"></i>
                                            Total: <strong>{{ number_format($penerima->total_kg, 0, ',', '.') }}
                                                kg</strong>
                                            ({{ number_format($penerima->total_karung, 0, ',', '.') }} karung)
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Bukti Tiba <span
                                                    class="text-muted small">(Opsional)</span></label>
                                            <input type="file" name="bukti_tiba" class="form-control"
                                                accept="image/*">
                                            <small class="text-muted">Format: JPG, PNG. Max: 2MB</small>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-info">
                                            <i class="fa fa-check"></i> Tandai Tiba
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="alert alert-info m-0">Belum ada penerima dalam kendaraan ini.</div>
                @endforelse

                {{-- Total per Kendaraan --}}
                @if ($kendaraan->penerimas->count() > 0)
                    @php
                        $oaKendaraan = $kendaraan->penerimas->sum('total_oa');
                        $upahKendaraan = $kendaraan->penerimas->flatMap->tims->sum('total_upah');
                    @endphp
                    <div
                        class="mt-3 p-2 bg-light rounded border d-flex justify-content-between align-items-center small fw-semibold">
                        <span class="text-muted">Total Kendaraan {{ $ki + 1 }}</span>
                        <span>
                            <span class="text-dark">{{ number_format($kendaraan->total_kg, 0, ',', '.') }} kg</span>
                            &nbsp;·&nbsp;
                            OA: <span class="text-primary">Rp {{ number_format($oaKendaraan, 0, ',', '.') }}</span>
                            @if ($upahKendaraan > 0)
                                &nbsp;·&nbsp; Angkut: <span class="text-success">Rp
                                    {{ number_format($upahKendaraan, 0, ',', '.') }}</span>
                            @endif
                        </span>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="alert alert-warning">Belum ada kendaraan dalam lansir ini.</div>
    @endforelse

    @if ($header->kendaraans->count() > 0)
        @php
            $grandOA = $header->kendaraans->flatMap->penerimas->sum('total_oa');
            $grandUpah = $header->kendaraans->flatMap->penerimas->flatMap->tims->sum('total_upah');
            $grandTotal = $grandOA + $grandUpah;
        @endphp
        <div class="card mb-3 border-dark">
            <div class="card-header text-dark py-2">
                <h6 class="mb-0"><i class="fa fa-calculator"></i> Grand Total — {{ $header->no_lansir }}</h6>
            </div>
            <div class="card-body py-3">
                <div class="row g-3 text-center">
                    <div class="col-6 col-md-3">
                        <div class="text-muted small">Kendaraan</div>
                        <div class="fw-bold fs-5">{{ $header->jumlah_kendaraan }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted small">Total Muatan</div>
                        <div class="fw-bold fs-5">{{ number_format($header->total_kg, 0, ',', '.') }} kg</div>
                        <div class="text-muted small">{{ number_format($header->total_karung, 0, ',', '.') }} karung</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted small">Total OA Lansir</div>
                        <div class="fw-bold fs-5 text-primary">Rp {{ number_format($grandOA, 0, ',', '.') }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted small">Total Upah Angkut</div>
                        <div class="fw-bold fs-5 text-success">Rp {{ number_format($grandUpah, 0, ',', '.') }}</div>
                    </div>
                </div>
                @if ($grandTotal > 0)
                    <hr class="my-2">
                    <div class="text-center">
                        <span class="text-muted small">Total Biaya Keseluruhan: </span>
                        <span class="fw-bold text-danger fs-5">Rp {{ number_format($grandTotal, 0, ',', '.') }}</span>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Footer Info --}}
    <div class="card">
        <div class="card-body py-2">
            <div class="row text-center small text-muted">
                <div class="col-md-6">
                    <i class="fa fa-user"></i> Dibuat oleh: {{ $header->creator?->name ?? '-' }}
                </div>
                <div class="col-md-6">
                    <i class="fa fa-clock-o"></i> {{ $header->created_at?->format('d/m/Y H:i') }}
                </div>
            </div>
        </div>
    </div>

@endsection
