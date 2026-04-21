<header class="mb-3">
    <a href="#" class="burger-btn d-block d-xl-none">
        <i class="bi bi-justify fs-3"></i>
    </a>
</header>
<div class="page-heading d-flex justify-content-between">
    @if ($activeCv)
        <h3>
            {{ $activeCv->nama_cv  }}

        </h3>
    @else
        <h3>Semua Perusahaan</h3>
    @endif
    <a href="/logout" class="btn btn-outline-warning">Keluar</a>
</div>
