@extends('layout.app')
@section('content')
    <div class="page-content">

        {{-- Header context CV --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
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
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center"
                                    style="width:50px;height:50px;font-size:16px;font-weight:700;flex-shrink:0">
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
                            <div class="card-footer bg-transparent py-2 px-3 border-0">
                                <form action="{{ route('switch.cv') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="cv_id" value="{{ $cv->id }}">
                                    <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                                        <i class="bi bi-eye me-1"></i> Lihat Detail
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </section>
        @endif

        {{-- Statistik Cards --}}
        <section class="row mb-4">
            <div class="col-12 col-sm-6 col-xl-3 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-muted mb-1">Total Purchase Order</h6>
                                <h3 class="fw-bold mb-0">{{ number_format($totalPO, 0, ',', '.') }}</h3>
                            </div>
                            <div class="avatar bg-light-primary text-primary rounded d-flex align-items-center justify-content-center"
                                style="width:50px;height:50px;font-size:22px">
                                <i class="bi bi-file-text"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-muted mb-1">Total Supplier</h6>
                                <h3 class="fw-bold mb-0">{{ number_format($totalSupplier, 0, ',', '.') }}</h3>
                            </div>
                            <div class="avatar bg-light-success text-success rounded d-flex align-items-center justify-content-center"
                                style="width:50px;height:50px;font-size:22px">
                                <i class="bi bi-truck"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-muted mb-1">Total Tujuan</h6>
                                <h3 class="fw-bold mb-0">{{ number_format($totalTujuan, 0, ',', '.') }}</h3>
                            </div>
                            <div class="avatar bg-light-info text-info rounded d-flex align-items-center justify-content-center"
                                style="width:50px;height:50px;font-size:22px">
                                <i class="bi bi-geo-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-muted mb-1">Total Penerima</h6>
                                <h3 class="fw-bold mb-0">{{ number_format($totalPenerima, 0, ',', '.') }}</h3>
                            </div>
                            <div class="avatar bg-light-warning text-warning rounded d-flex align-items-center justify-content-center"
                                style="width:50px;height:50px;font-size:22px">
                                <i class="bi bi-people"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Konten dashboard lainnya --}}
        <section class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0 fw-bold">Selamat Datang!</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-0">
                            Menampilkan data untuk:
                            <strong class="text-primary">{{ $selectedCv ? $selectedCv->nama_cv : 'Semua Perusahaan' }}</strong>
                        </p>
                        {{-- Anda bisa menambahkan chart atau konten lain di sini --}}
                    </div>
                </div>
            </div>
        </section>

    </div>
@endsection
