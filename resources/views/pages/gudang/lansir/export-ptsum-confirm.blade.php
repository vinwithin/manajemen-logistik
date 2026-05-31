@extends('layout.app')
@section('content')
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fa fa-file-pdf-o text-warning"></i> Export PDF Lansir Gudang — PT Sum</h5>
                    <a href="{{ route('gudang.lansir.index') }}" class="btn btn-sm btn-secondary">
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

                    <form method="GET" action="{{ route('gudang.lansir.export-pdf-ptsum') }}" target="_blank">

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

                                {{-- Filter Gudang & Supplier & Tujuan --}}
                                <div class="row g-3 mt-2">
                                    <div class="col-md-4">
                                        <label class="form-label small">Gudang <span class="text-muted">(opsional)</span></label>
                                        <select name="gudang_id" class="form-select form-select-sm">
                                            <option value="">-- Semua Gudang --</option>
                                            @foreach ($gudangs as $g)
                                                <option value="{{ $g->id }}"
                                                    {{ request('gudang_id') == $g->id ? 'selected' : '' }}>
                                                    {{ $g->nama }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
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
                                    <div class="col-md-4">
                                        <label class="form-label small">Tujuan<span
                                                class="text-danger">*</span></label>
                                        <select name="tujuan_id" id="selectTujuan" class="form-select form-select-sm" required>
                                            <option value="">-- Semua Tujuan --</option>
                                            @foreach ($tujuans as $t)
                                                <option value="{{ $t->id }}"
                                                    {{ request('tujuan_id') == $t->id ? 'selected' : '' }}>
                                                    {{ $t->nama }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
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
                                        value="{{ $dokumen?->no_surat ?? ($noSuratSuggest ?? '') }}" placeholder="Masukkan nomor surat">

                                    <label class="form-label small">Masukkan Tujuan (Dari Gudang ke .......)</label>
                                    <input type="text" name="cpi" class="form-control form-control-sm"
                                        value="{{ $dokumen?->cpi }}" placeholder="Masukkan tujuan">

                                    <div class="text-muted small mt-1">
                                        @if ($dokumen)
                                            <span class="text-success ms-1">
                                                <i class="fa fa-check-circle"></i>
                                                Tersimpan: <strong>{{ $dokumen->no_surat }}</strong> (urutan
                                                #{{ $dokumen->urutan }})
                                            </span>
                                        @elseif ($noSuratSuggest && $cvId && $from && $to)
                                            <span class="text-info ms-1">
                                                <i class="fa fa-info-circle"></i>
                                                Nomor berikutnya: <strong>{{ $noSuratSuggest }}</strong>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">Catatan (opsional)</label>
                                    <input type="text" name="catatan" class="form-control form-control-sm"
                                        value="{{ $dokumen?->catatan }}" placeholder="Catatan tambahan untuk dokumen ini">
                                </div>
                            </div>
                        </div>

                        {{-- Preview info --}}
                        @if ($lansirCount !== null)
                            <div class="alert alert-info py-2 small">
                                <i class="fa fa-info-circle"></i>
                                Ditemukan <strong>{{ $lansirCount }}</strong> lansir
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
                                <i class="fa fa-search"></i> Preview Jumlah Lansir
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Preview jumlah lansir
        document.getElementById('btnPreview').addEventListener('click', function() {
            var cvId = document.getElementById('selectCv').value;
            var from = document.getElementById('inputFrom').value;
            var to = document.getElementById('inputTo').value;
            var gudangId = document.querySelector('[name=gudang_id]').value;
            var supplierId = document.getElementById('selectSupplier').value;
            var tujuanId = document.getElementById('selectTujuan').value;

            if (!from || !to) {
                alert('Isi Dari Tanggal dan Sampai Tanggal terlebih dahulu.');
                return;
            }

            var url = '{{ route('gudang.lansir.export-pdf-ptsum-confirm') }}?cv_id=' + cvId +
                '&from=' + from + '&to=' + to;

            if (gudangId) url += '&gudang_id=' + gudangId;
            if (supplierId) url += '&supplier_id=' + supplierId;
            if (tujuanId) url += '&tujuan_id=' + tujuanId;

            window.location.href = url;
        });
    </script>
@endsection
