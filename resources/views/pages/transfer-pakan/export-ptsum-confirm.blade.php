@extends('layout.app')
@section('content')
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fa fa-file-pdf-o text-warning"></i> Export PDF Transfer Pakan — PT Sum</h5>
                    <a href="{{ route('transfer-pakan.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fa fa-arrow-left"></i> Kembali
                    </a>
                </div>
                <div class="card-body">

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show py-2">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger py-2">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger py-2 small">
                            <ul class="mb-0 mt-1 ps-3">
                                @foreach ($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="GET" action="{{ route('transfer-pakan.export-ptsum') }}" target="_blank">

                        {{-- Filter Periode --}}
                        <div class="card border-0 bg-light mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3">Filter Periode</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small">CV <span class="text-danger">*</span></label>
                                        <select name="cv_id" id="selectCv" class="form-select form-select-sm" required>
                                            <option value="">-- Pilih CV --</option>
                                            @foreach (\App\Models\Cv::withOmzet() as $cv)
                                                <option value="{{ $cv->id }}"
                                                    data-prefix="{{ $cv->no_dokumen_prefix }}"
                                                    {{ (request('cv_id') ?? session('active_cv')) == $cv->id ? 'selected' : '' }}>
                                                    {{ $cv->nama_cv }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Dari Tanggal <span
                                                class="text-danger">*</span></label>
                                        <input type="date" name="from" id="inputFrom"
                                            class="form-control form-control-sm" value="{{ old('from', request('from')) }}"
                                            required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Sampai Tanggal <span
                                                class="text-danger">*</span></label>
                                        <input type="date" name="to" id="inputTo"
                                            class="form-control form-control-sm" value="{{ old('to', request('to')) }}"
                                            required>
                                    </div>
                                </div>

                                {{-- Filter Tujuan --}}
                                <div class="row g-3 mt-2">
                                    <div class="col-md-12">
                                        <label class="form-label small">Tujuan <span class="text-danger">*</span></label>
                                        <select name="tujuan_ids" id="selectTujuan" class="form-select form-select-sm"
                                            required>
                                            <option value="">-- Semua Tujuan --</option>
                                            {{-- Opsi gabungan --}}
                                            @php $currentTujuanIds = request('tujuan_ids', isset($tujuanIds) ? implode(',', $tujuanIds) : ''); @endphp
                                            <option value="6,8" {{ $currentTujuanIds === '6,8' ? 'selected' : '' }}>
                                                ★ Jambi 1 & Jambi 5
                                            </option>
                                            <option value="9,10" {{ $currentTujuanIds === '9,10' ? 'selected' : '' }}>
                                                ★ 34 Dalam & 34 Luar
                                            </option>
                                            <option disabled>──────────────</option>
                                            @foreach ($tujuans as $t)
                                                <option value="{{ $t->id }}"
                                                    {{ $currentTujuanIds == $t->id ? 'selected' : '' }}>
                                                    {{ $t->nama }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                {{-- Filter Kendaraan (Plat Mobil) --}}
                                @if ($kendaraanList->count() > 0)
                                    <div class="row g-3 mt-2">
                                        <div class="col-12">
                                            <label class="form-label small fw-bold">
                                                <i class="fa fa-truck"></i> Pilih Kendaraan (Plat Mobil)
                                                <span class="text-muted fw-normal">(opsional — kosongkan untuk semua
                                                    kendaraan)</span>
                                            </label>
                                            <input type="hidden" name="kendaraan_ids" id="inputKendaraanIds"
                                                value="{{ implode(',', $selectedKendaraanIds) }}">
                                            <div class="border rounded p-2 bg-white"
                                                style="max-height: 200px; overflow-y: auto;">
                                                <div class="mb-1">
                                                    <button type="button" class="btn btn-xs btn-outline-primary me-1"
                                                        id="btnPilihSemua">
                                                        <i class="fa fa-check-square-o"></i> Pilih Semua
                                                    </button>
                                                    <button type="button" class="btn btn-xs btn-outline-secondary"
                                                        id="btnHapusSemua">
                                                        <i class="fa fa-square-o"></i> Hapus Semua
                                                    </button>
                                                </div>
                                                <div class="row g-1 mt-1">
                                                    @foreach ($kendaraanList as $k)
                                                        <div class="col-md-4 col-6">
                                                            <div class="form-check form-check-sm">
                                                                <input class="form-check-input kendaraan-check"
                                                                    type="checkbox" value="{{ $k->id }}"
                                                                    id="k_{{ $k->id }}"
                                                                    {{ empty($selectedKendaraanIds) || in_array($k->id, $selectedKendaraanIds) ? 'checked' : '' }}>
                                                                <label class="form-check-label"
                                                                    for="k_{{ $k->id }}">
                                                                    <strong>{{ $k->no_polisi }}</strong>
                                                                    <span class="text-muted">· {{ $k->header->no_transfer }}</span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                <span
                                                    id="countSelected">{{ empty($selectedKendaraanIds) ? $kendaraanList->count() : count($selectedKendaraanIds) }}</span>
                                                dari {{ $kendaraanList->count() }} kendaraan dipilih
                                            </small>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Nomor Surat --}}
                        <div class="card border-warning mb-4">
                            <div class="card-header bg-warning bg-opacity-10 py-2">
                                <h6 class="mb-0 small fw-bold">
                                    <i class="fa fa-file-text-o text-warning"></i>
                                    Nomor Surat Kwitansi
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <label class="form-label small">Nomor Surat</label>
                                    <input type="text" name="no_surat" class="form-control form-control-sm"
                                        value="" placeholder="Masukkan nomor surat">

                                    <label class="form-label small">Masukkan Tujuan (Dari penerima ke .......)</label>
                                    <input type="text" name="cpi" class="form-control form-control-sm"
                                        value="{{ $dokumen?->cpi }}" placeholder="Masukkan tujuan">

                                   
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">Tanggal Surat</label>
                                    <input type="text" name="tanggal_surat" class="form-control form-control-sm"
                                         placeholder="Jambi, 17 Agustus 2026">
                                </div>
                            </div>
                        </div>

                        {{-- Preview info --}}
                        @if ($transferCount !== null)
                            <div class="alert alert-info py-2 small">
                                <i class="fa fa-info-circle"></i>
                                Ditemukan <strong>{{ $transferCount }}</strong> transfer pakan
                                @if ($cvNama)
                                    untuk <strong>{{ $cvNama }}</strong>
                                @endif
                                @if (request('from') && request('to'))
                                    periode {{ date('d/m/Y', strtotime(request('from'))) }} &ndash;
                                    {{ date('d/m/Y', strtotime(request('to'))) }}
                                @endif
                            </div>
                        @endif

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-warning">
                                <i class="fa fa-file-pdf-o"></i> Export PDF
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="btnPreview">
                                <i class="fa fa-search"></i> Preview Jumlah
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sync checkbox ke hidden input
        function syncKendaraanIds() {
            var checked = [];
            document.querySelectorAll('.kendaraan-check:checked').forEach(function (el) {
                checked.push(el.value);
            });
            document.getElementById('inputKendaraanIds').value = checked.join(',');
            var countEl = document.getElementById('countSelected');
            if (countEl) countEl.textContent = checked.length;
        }

        document.querySelectorAll('.kendaraan-check').forEach(function (el) {
            el.addEventListener('change', syncKendaraanIds);
        });

        var btnPilihSemua = document.getElementById('btnPilihSemua');
        if (btnPilihSemua) {
            btnPilihSemua.addEventListener('click', function () {
                document.querySelectorAll('.kendaraan-check').forEach(function (el) {
                    el.checked = true;
                });
                syncKendaraanIds();
            });
        }

        var btnHapusSemua = document.getElementById('btnHapusSemua');
        if (btnHapusSemua) {
            btnHapusSemua.addEventListener('click', function () {
                document.querySelectorAll('.kendaraan-check').forEach(function (el) {
                    el.checked = false;
                });
                syncKendaraanIds();
            });
        }

        // Preview jumlah
        document.getElementById('btnPreview').addEventListener('click', function () {
            var cvId = document.getElementById('selectCv').value;
            var from = document.getElementById('inputFrom').value;
            var to = document.getElementById('inputTo').value;
            var tujuanId = document.getElementById('selectTujuan').value;
            var kendaraanIds = document.getElementById('inputKendaraanIds')?.value ?? '';

            if (!from || !to) {
                alert('Isi Dari Tanggal dan Sampai Tanggal terlebih dahulu.');
                return;
            }

            var url = '{{ route('transfer-pakan.export-ptsum-confirm') }}?cv_id=' + cvId +
                '&from=' + from + '&to=' + to;

            if (tujuanId) url += '&tujuan_ids=' + encodeURIComponent(tujuanId);
            if (kendaraanIds) url += '&kendaraan_ids=' + encodeURIComponent(kendaraanIds);

            window.location.href = url;
        });
    </script>
@endsection
