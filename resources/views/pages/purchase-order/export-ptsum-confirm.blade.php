@extends('layout.app')
@section('content')
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fa fa-file-pdf-o text-danger"></i> Export PDF Periode — PT Sum</h5>
                    <a href="{{ route('purchase-order.index') }}" class="btn btn-sm btn-secondary">
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
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="GET" action="{{ route('purchase-order.export-pdf-ptsum') }}" target="_blank"
                        id="formExportPtSum">

                        {{-- Filter Periode --}}
                        <div class="card border-0 bg-light mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3">Filter Periode</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small">CV <span class="text-danger">*</span></label>
                                        <select name="cv_id" id="selectCv" class="form-select form-select-sm" required>
                                            <option value="">-- Pilih CV --</option>
                                            @foreach ($userCvs as $cv)
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

                                {{-- Filter Supplier & Tujuan --}}
                                <div class="row g-3 mt-2">
                                    <div class="col-md-6">
                                        <label class="form-label small">Supplier <span
                                                class="text-muted">(opsional)</span></label>
                                        <select name="supplier_id" id="selectSupplier" class="form-select form-select-sm">
                                            <option value="">-- Semua Supplier --</option>
                                            @foreach ($suppliers as $s)
                                                <option value="{{ $s->id }}"
                                                    {{ request('supplier_id') == $s->id ? 'selected' : '' }}>
                                                    {{ $s->nama }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Tujuan <span
                                                class="text-muted">(opsional)</span></label>
                                        <select name="tujuan_ids" id="selectTujuan" class="form-select form-select-sm">
                                            <option value="">-- Semua Tujuan --</option>
                                            {{-- Opsi gabungan --}}
                                            @php $currentTujuanIds = request('tujuan_ids', isset($tujuanIds) ? implode(',', $tujuanIds) : ''); @endphp
                                            <option value="6,8" {{ $currentTujuanIds === '6,8' ? 'selected' : '' }}>
                                                ★ Jambi 1 + Jambi 5
                                            </option>
                                            <option value="9,10" {{ $currentTujuanIds === '9,10' ? 'selected' : '' }}>
                                                ★ 34 Dalam + 34 Luar
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
                                                value="{{ empty($selectedKendaraanIds) ? $kendaraanList->pluck('id')->join(',') : implode(',', $selectedKendaraanIds) }}">
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
                                                                    <span class="text-muted">· {{ $k->po->no_po }}</span>
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

                                    <label class="form-label small">Masukkan Tujuan (Dari Pabrik CPI Padang ke
                                        .......)</label>
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
                        @if ($poCount !== null)
                            <div class="alert alert-info py-2 small">
                                <i class="fa fa-info-circle"></i>
                                Ditemukan <strong>{{ $poCount }}</strong> PO
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
                            <button type="submit" class="btn btn-danger">
                                <i class="fa fa-file-pdf-o"></i> Export PDF
                            </button>
                            <button type="button" class="btn btn-success" id="btnExportExcel">
                                <i class="fa fa-file-excel-o"></i> Export Excel
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="btnPreview">
                                <i class="fa fa-search"></i> Preview Jumlah PO
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
            document.querySelectorAll('.kendaraan-check:checked').forEach(function(el) {
                checked.push(el.value);
            });
            var inputKendaraanIds = document.getElementById('inputKendaraanIds');
            if (inputKendaraanIds) inputKendaraanIds.value = checked.join(',');
            var countEl = document.getElementById('countSelected');
            if (countEl) countEl.textContent = checked.length;
        }

        function hasKendaraanSelection() {
            var kendaraanChecks = document.querySelectorAll('.kendaraan-check');
            return kendaraanChecks.length === 0 || document.querySelectorAll('.kendaraan-check:checked').length > 0;
        }

        document.getElementById('formExportPtSum').addEventListener('submit', function(e) {
            syncKendaraanIds();

            if (!hasKendaraanSelection()) {
                e.preventDefault();
                alert('Pilih minimal satu kendaraan terlebih dahulu.');
            }
        });

        document.querySelectorAll('.kendaraan-check').forEach(function(el) {
            el.addEventListener('change', syncKendaraanIds);
        });

        var btnPilihSemua = document.getElementById('btnPilihSemua');
        if (btnPilihSemua) {
            btnPilihSemua.addEventListener('click', function() {
                document.querySelectorAll('.kendaraan-check').forEach(function(el) {
                    el.checked = true;
                });
                syncKendaraanIds();
            });
        }

        var btnHapusSemua = document.getElementById('btnHapusSemua');
        if (btnHapusSemua) {
            btnHapusSemua.addEventListener('click', function() {
                document.querySelectorAll('.kendaraan-check').forEach(function(el) {
                    el.checked = false;
                });
                syncKendaraanIds();
            });
        }

        document.getElementById('btnExportExcel').addEventListener('click', function() {
            var button = this;
            var form = document.getElementById('formExportPtSum');

            if (!form.reportValidity()) {
                return;
            }

            syncKendaraanIds();

            if (!hasKendaraanSelection()) {
                alert('Pilih minimal satu kendaraan terlebih dahulu.');
                return;
            }

            var originalHtml = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Memproses...';

            var params = new URLSearchParams(new FormData(form));
            window.open('{{ route('purchase-order.export-excel-ptsum') }}?' + params.toString(), '_blank');

            setTimeout(function() {
                button.disabled = false;
                button.innerHTML = originalHtml;
            }, 1500);
        });

        // Preview jumlah PO
        document.getElementById('btnPreview').addEventListener('click', function() {
            var cvId = document.getElementById('selectCv').value;
            var from = document.getElementById('inputFrom').value;
            var to = document.getElementById('inputTo').value;
            var supplierId = document.getElementById('selectSupplier').value;
            var tujuanId = document.getElementById('selectTujuan').value;
            var kendaraanIds = document.getElementById('inputKendaraanIds')?.value ?? '';

            if (!from || !to) {
                alert('Isi Dari Tanggal dan Sampai Tanggal terlebih dahulu.');
                return;
            }

            if (!hasKendaraanSelection()) {
                alert('Pilih minimal satu kendaraan terlebih dahulu.');
                return;
            }

            var url = '{{ route('purchase-order.export-ptsum-confirm') }}?cv_id=' + cvId +
                '&from=' + from + '&to=' + to;

            if (supplierId) url += '&supplier_id=' + supplierId;
            if (tujuanId) url += '&tujuan_ids=' + encodeURIComponent(tujuanId);
            if (kendaraanIds) url += '&kendaraan_ids=' + encodeURIComponent(kendaraanIds);

            window.location.href = url;
        });
    </script>
@endsection
