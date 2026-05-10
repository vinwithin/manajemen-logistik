<header class="mb-3">
    <a href="#" class="burger-btn d-block d-xl-none">
        <i class="bi bi-justify fs-3"></i>
    </a>
</header>

<div class="page-heading">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

        {{-- Kiri: Judul halaman + breadcrumb --}}
        <div>
            <h3 class="mb-0 fw-bold" style="font-size:1.1rem;">
                @if ($activeCv)
                    <span class="text-primary">{{ $activeCv->nama_cv }}</span>
                @else
                    <span class="text-secondary">Semua Perusahaan</span>
                @endif
            </h3>
            
        </div>

        {{-- Kanan: Info user + logout --}}
        <div class="d-flex align-items-center gap-3">

            {{-- Tanggal --}}
            <div class="d-none d-md-block text-muted small text-end">
                <i data-feather="calendar" style="width:13px;height:13px;"></i>
                {{ now()->translatedFormat('d F Y') }}
            </div>

            {{-- Divider --}}
            <div class="vr d-none d-md-block" style="height:28px;"></div>

            {{-- User dropdown --}}
            <div class="dropdown">
                <button class="btn btn-sm btn-light d-flex align-items-center gap-2 border" type="button"
                    data-bs-toggle="dropdown" aria-expanded="false" style="border-radius:8px; padding:5px 10px;">
                    <span
                        class="avatar rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                        style="width:28px;height:28px;font-size:11px;font-weight:700;flex-shrink:0;">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                    </span>
                    <span class="d-none d-sm-inline text-dark"
                        style="font-size:0.82rem;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        {{ auth()->user()->name ?? 'User' }}
                    </span>
                    <i data-feather="chevron-down" style="width:13px;height:13px;color:#888;"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width:180px;">
                    <li>
                        <div class="px-3 py-2 border-bottom">
                            <div class="fw-semibold small">{{ auth()->user()->name ?? '-' }}</div>
                            <div class="text-muted" style="font-size:11px;">{{ auth()->user()->email ?? '-' }}</div>
                        </div>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2"
                            href="{{ route('profile.edit') }}">
                            <i data-feather="user" style="width:14px;height:14px;"></i>
                            Profil Saya
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider my-1">
                    </li>
                    <li>
                        <a href="{{ route('logout') }}"
                            class="dropdown-item d-flex align-items-center gap-2 py-2 text-danger">
                            <i data-feather="log-out" style="width:14px;height:14px;"></i>
                            Keluar
                        </a>
                    </li>
                </ul>
            </div>

        </div>
    </div>
</div>
