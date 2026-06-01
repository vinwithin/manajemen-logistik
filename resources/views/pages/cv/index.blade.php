@extends('layout.app')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Manajemen Perusahaan (CV)</h5>
        <a href="{{ route('perusahaan.create') }}" class="btn btn-sm btn-primary">
            <i class="fa fa-plus"></i> Tambah CV
        </a>
    </div>

    @php $batas = 4_600_000_000; @endphp

    <div class="row g-3">
        @forelse($cvList as $cv)
            @php
                $persen = $cv->persen_omzet;
                $color = $cv->melebihi_batas
                    ? 'danger'
                    : ($persen >= 80
                        ? 'warning'
                        : ($persen >= 50
                            ? 'info'
                            : 'success'));
            @endphp
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card h-100 {{ $cv->melebihi_batas ? 'border-danger' : '' }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="fw-bold mb-0">{{ $cv->nama_cv }}</h6>
                                @if ($cv->code)
                                    <small class="text-muted">{{ $cv->code }}</small>
                                @endif
                            </div>
                            <div class="d-flex gap-1">
                                <span class="badge bg-{{ $cv->is_aktif ? 'success' : 'secondary' }}">
                                    {{ $cv->is_aktif ? 'Aktif' : 'Nonaktif' }}
                                </span>
                                @if ($cv->melebihi_batas)
                                    <span class="badge bg-danger">⚠️ Batas</span>
                                @endif
                            </div>
                        </div>

                        {{-- Progress omzet --}}
                        <div class="mb-1 d-flex justify-content-between small">
                            <span class="text-muted">Omzet {{ now()->year }}</span>
                            <span class="fw-semibold {{ $cv->melebihi_batas ? 'text-danger' : '' }}">
                                Rp {{ number_format($cv->omzet_tahun, 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="progress mb-1" style="height:8px">
                            <div class="progress-bar bg-{{ $color }}" style="width: {{ $persen }}%"
                                title="{{ $persen }}%"></div>
                        </div>
                        <div class="d-flex justify-content-between small text-muted">
                            <span>{{ $persen }}% dari Rp 48jt</span>
                            <span>Sisa: Rp {{ number_format(max(0, $batas - $cv->omzet_tahun), 0, ',', '.') }}</span>
                        </div>

                        @if ($cv->melebihi_batas)
                            <div class="alert alert-danger py-1 px-2 mt-2 mb-0 small">
                                ⚠️ Omzet melebihi batas Rp 4,6m/tahun. Jangan gunakan CV ini untuk PO baru.
                            </div>
                        @elseif($persen >= 80)
                            <div class="alert alert-warning py-1 px-2 mt-2 mb-0 small">
                                Mendekati batas ({{ $persen }}%). Pertimbangkan CV lain.
                            </div>
                        @endif
                    </div>
                    <div class="card-footer bg-transparent d-flex gap-2 py-2">
                        <a href="{{ route('perusahaan.edit', encrypt($cv->id)) }}" class="btn btn-xs btn-info flex-fill">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                        <a href="#" class="btn btn-xs btn-danger flex-fill"
                            onclick="confirmation('del-cv-{{ $cv->id }}')">
                            <i class="fa fa-trash"></i> Hapus
                        </a>
                        <form action="{{ route('perusahaan.destroy', $cv->id) }}" method="post"
                            id="del-cv-{{ $cv->id }}">
                            @csrf @method('DELETE')
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center text-muted py-4">Belum ada data CV.</div>
                </div>
            </div>
        @endforelse
    </div>

    <script>
        function confirmation(id) {
            alertify.confirm("Konfirmasi!", "Hapus CV ini?", function() {
                $('#' + id).submit();
            }, function() {});
        }
    </script>
@endsection
