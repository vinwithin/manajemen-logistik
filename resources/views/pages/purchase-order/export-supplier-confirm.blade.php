@extends('layout.app')
@section('content')
    <div class="row justify-content-center">
        <div class="col-12 ">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fa fa-file-pdf-o text-danger"></i> Export PDF Periode — Supplier</h5>
                    <a href="{{ route('purchase-order.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fa fa-arrow-left"></i> Kembali
                    </a>
                </div>
                <div class="card-body">

                    @if ($errors->any())
                        <div class="alert alert-danger py-2 small">
                            @foreach ($errors->all() as $e)
                                <div>{{ $e }}</div>
                            @endforeach
                        </div>
                    @endif

                    <form method="GET" action="{{ route('purchase-order.export-pdf-supplier') }}" target="_blank">

                        <div class="row g-3">

                            {{-- Tanggal (wajib) --}}
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Dari Tanggal <span
                                        class="text-danger">*</span></label>
                                <input type="date" name="from" class="form-control form-control-sm"
                                    value="{{ request('from') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Sampai Tanggal <span
                                        class="text-danger">*</span></label>
                                <input type="date" name="to" class="form-control form-control-sm"
                                    value="{{ request('to') }}" required>
                            </div>

                            {{-- CV (opsional) --}}
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">CV <span
                                        class="text-muted">(opsional)</span></label>
                                <select name="cv_id" class="form-select form-select-sm">
                                    <option value="">-- Semua CV --</option>
                                    @foreach ($userCvs as $cv)
                                        <option value="{{ $cv->id }}"
                                            {{ request('cv_id') == $cv->id ? 'selected' : '' }}>
                                            {{ $cv->nama_cv }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Supplier (opsional) --}}
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Supplier <span
                                        class="text-muted">(opsional)</span></label>
                                <select name="supplier_id" class="form-select form-select-sm">
                                    <option value="">-- Semua Supplier --</option>
                                    @foreach ($supplierList as $s)
                                        <option value="{{ $s->id }}"
                                            {{ request('supplier_id') == $s->id ? 'selected' : '' }}>
                                            {{ $s->initial }} — {{ $s->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Tujuan (opsional) --}}
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Tujuan <span
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

                            {{-- Preview info --}}
                            @if ($poCount !== null)
                                <div class="col-12">
                                    <div class="alert alert-info py-2 small mb-0">
                                        <i class="fa fa-info-circle"></i>
                                        Ditemukan <strong>{{ $poCount }}</strong> PO
                                        @if (request('cv_id'))
                                            untuk CV yang dipilih
                                        @endif
                                        @if (request('supplier_id'))
                                            dari supplier yang dipilih
                                        @endif
                                        @if (request('tujuan_id'))
                                            dengan tujuan yang dipilih
                                        @endif
                                        periode {{ date('d/m/Y', strtotime(request('from'))) }}
                                        &ndash; {{ date('d/m/Y', strtotime(request('to'))) }}
                                    </div>
                                </div>
                            @endif

                            <div class="col-12 d-flex gap-2 mt-2">
                                <button type="submit" class="btn btn-danger">
                                    <i class="fa fa-file-pdf-o"></i> Export PDF
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="btnPreview">
                                    <i class="fa fa-search"></i> Preview Jumlah PO
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('btnPreview').addEventListener('click', function() {
            var form = this.closest('form');
            var from = form.querySelector('[name=from]').value;
            var to = form.querySelector('[name=to]').value;
            if (!from || !to) {
                alertify.warning('Tanggal dari dan sampai wajib diisi.');
                return;
            }

            var params = new URLSearchParams({
                from: from,
                to: to,
                cv_id: form.querySelector('[name=cv_id]').value,
                supplier_id: form.querySelector('[name=supplier_id]').value,
                tujuan_id: form.querySelector('[name=tujuan_id]').value,
            });
            window.location.href = '{{ route('purchase-order.export-supplier-confirm') }}?' + params.toString();
        });
    </script>
@endsection
