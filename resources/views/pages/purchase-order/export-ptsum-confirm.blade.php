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

                    <form method="GET" action="{{ route('purchase-order.export-pdf-ptsum') }}" target="_blank">

                        {{-- Filter Periode --}}
                        <div class="card border-0 bg-light mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3">Filter Periode</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small">CV <span class="text-danger">*</span></label>
                                        <select name="cv_id" id="selectCv" class="form-select form-select-sm" required>
                                            <option value="">-- Pilih CV --</option>
                                            @foreach ($cvList as $cv)
                                                <option value="{{ $cv->id }}"
                                                    data-prefix="{{ $cv->no_dokumen_prefix }}"
                                                    {{ (request('cv_id') ?? session('active_cv')) == $cv->id ? 'selected' : '' }}>
                                                    {{ $cv->nama_cv }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Dari Tanggal</label>
                                        <input type="date" name="from" id="inputFrom"
                                            class="form-control form-control-sm" value="{{ request('from') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Sampai Tanggal</label>
                                        <input type="date" name="to" id="inputTo"
                                            class="form-control form-control-sm" value="{{ request('to') }}">
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
                                        <select name="tujuan_id" id="selectTujuan" class="form-select form-select-sm">
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
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="buat_no_surat"
                                        id="checkBuatNoSurat" value="1" {{ $dokumen ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="checkBuatNoSurat">
                                        Buat Nomor Surat
                                    </label>
                                    <div class="text-muted small mt-1">
                                        Jika dicentang, nomor surat akan di-generate otomatis dan muncul di kwitansi PDF.
                                        Nomor increment per CV per tahun.
                                        @if ($dokumen)
                                            <span class="text-success ms-1">
                                                <i class="fa fa-check-circle"></i>
                                                Tersimpan: <strong>{{ $dokumen->no_surat }}</strong> (urutan
                                                #{{ $dokumen->urutan }})
                                            </span>
                                        @elseif ($noSuratSuggest && $cvId && $from)
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
        // Preview jumlah PO
        document.getElementById('btnPreview').addEventListener('click', function() {
            var cvId = document.getElementById('selectCv').value;
            var from = document.getElementById('inputFrom').value;
            var to = document.getElementById('inputTo').value;
            var supplierId = document.getElementById('selectSupplier').value;
            var tujuanId = document.getElementById('selectTujuan').value;

            var url = '{{ route('purchase-order.export-ptsum-confirm') }}?cv_id=' + cvId +
                '&from=' + from + '&to=' + to;

            if (supplierId) url += '&supplier_id=' + supplierId;
            if (tujuanId) url += '&tujuan_id=' + tujuanId;

            window.location.href = url;
        });
    </script>
@endsection
