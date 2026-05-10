@extends('layout.app')
@section('content')
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fa fa-file-pdf-o text-info"></i> Export PDF Lansir Gudang — Supplier</h5>
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

                    <form method="GET" action="{{ route('gudang.lansir.export-pdf-supplier') }}" target="_blank">

                        <div class="card border-0 bg-light mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3">Filter Periode</h6>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label small">Gudang <span
                                                class="text-muted">(opsional)</span></label>
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
                                    <div class="col-md-3">
                                        <label class="form-label small">CV <span
                                                class="text-muted">(opsional)</span></label>
                                        <select name="cv_id" class="form-select form-select-sm">
                                            <option value="">-- Semua CV --</option>
                                            @foreach ($cvList as $cv)
                                                <option value="{{ $cv->id }}"
                                                    {{ request('cv_id') == $cv->id ? 'selected' : '' }}>
                                                    {{ $cv->nama_cv }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Dari Tanggal <span
                                                class="text-danger">*</span></label>
                                        <input type="date" name="from" id="inputFrom"
                                            class="form-control form-control-sm" value="{{ request('from') }}" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Sampai Tanggal <span
                                                class="text-danger">*</span></label>
                                        <input type="date" name="to" id="inputTo"
                                            class="form-control form-control-sm" value="{{ request('to') }}" required>
                                    </div>
                                </div>
                                <div class="row g-3 mt-2">
                                    <div class="col-md-6">
                                        <label class="form-label small">Supplier <span
                                                class="text-muted">(opsional)</span></label>
                                        <select name="supplier_id" class="form-select form-select-sm">
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
                                        <select name="tujuan_id" class="form-select form-select-sm">
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

                        {{-- Preview info --}}
                        @if ($lansirCount !== null)
                            <div class="alert alert-info py-2 small">
                                <i class="fa fa-info-circle"></i>
                                Ditemukan <strong>{{ $lansirCount }}</strong> lansir
                                @if (request('from') && request('to'))
                                    periode {{ date('d/m/Y', strtotime(request('from'))) }} &ndash;
                                    {{ date('d/m/Y', strtotime(request('to'))) }}
                                @endif
                            </div>
                        @endif

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-info">
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
        document.getElementById('btnPreview').addEventListener('click', function() {
            var from = document.getElementById('inputFrom').value;
            var to = document.getElementById('inputTo').value;
            if (!from || !to) {
                alertify.warning('Isi tanggal dari dan sampai.');
                return;
            }
            var params = new URLSearchParams({
                from: from,
                to: to,
                gudang_id: document.querySelector('[name=gudang_id]').value,
                cv_id: document.querySelector('[name=cv_id]').value,
                supplier_id: document.querySelector('[name=supplier_id]').value,
                tujuan_id: document.querySelector('[name=tujuan_id]').value,
            });
            window.location.href = '{{ route('gudang.lansir.export-pdf-supplier-confirm') }}?' + params.toString();
        });
    </script>
@endsection
