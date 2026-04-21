@extends('layout.app')
@section('content')
    <div class="page-content">

        {{-- Header context CV --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">
                Dashboard
                <span class="text-muted fw-normal fs-6">
                    · {{ $selectedCv ? $selectedCv->nama_cv : 'Semua Perusahaan' }}
                </span>
            </h4>
        </div>

        {{-- Kartu ringkasan per CV (hanya tampil saat "Semua Perusahaan") --}}
        @if (!$selectedCv && $userCvs->count() > 0)
            <section class="row mb-4">
                @foreach ($userCvs as $cv)
                    <div class="col-12 col-md-6 col-xl-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="avatar bg-primary text-white rounded d-flex align-items-center justify-content-center"
                                    style="width:44px;height:44px;font-size:14px;font-weight:600;flex-shrink:0">
                                    {{ strtoupper(substr($cv->nama_cv, 0, 2)) }}
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 fw-bold">{{ $cv->nama_cv }}</h6>
                                    @if ($cv->code)
                                        <small class="text-muted">{{ $cv->code }}</small>
                                    @endif
                                </div>
                                <span class="badge bg-{{ $cv->is_aktif ? 'success' : 'secondary' }}">
                                    {{ $cv->is_aktif ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                            <div class="card-footer bg-transparent py-2 px-3">
                                <form action="{{ route('switch.cv') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="cv_id" value="{{ $cv->id }}">
                                    <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                                        Lihat Detail
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </section>
        @endif

        {{-- Konten dashboard utama --}}
        <section class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <p class="text-muted mb-0">
                            Menampilkan data untuk:
                            <strong>{{ $selectedCv ? $selectedCv->nama_cv : 'Semua Perusahaan' }}</strong>
                        </p>
                        {{-- Tambahkan statistik / chart di sini --}}
                    </div>
                </div>
            </div>
        </section>

    </div>
@endsection
