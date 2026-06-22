@extends('layout.app')
@section('content')
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fa fa-print text-danger"></i> Cetak Pembayaran Supplier</h5>
                    <a href="{{ route('keuangan.pembayaran.index') }}" class="btn btn-sm btn-secondary">
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

                    <form method="GET" target="_blank">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Dari Tanggal Bayar <span class="text-danger">*</span></label>
                                <input type="date" name="from" class="form-control form-control-sm"
                                    value="{{ request('from') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Sampai Tanggal Bayar <span class="text-danger">*</span></label>
                                <input type="date" name="to" class="form-control form-control-sm"
                                    value="{{ request('to') }}" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Tipe Pembayaran <span class="text-muted">(opsional)</span></label>
                                <select name="tipe_pembayaran" class="form-select form-select-sm">
                                    <option value="">-- Semua Tipe --</option>
                                    <option value="oa" {{ request('tipe_pembayaran') === 'oa' ? 'selected' : '' }}>Pembayaran OA</option>
                                    <option value="dp_supplier" {{ request('tipe_pembayaran') === 'dp_supplier' ? 'selected' : '' }}>DP Supplier</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Status Pembayaran</label>
                                <select name="status_pembayaran" class="form-select form-select-sm">
                                    <option value="lunas" {{ request('status_pembayaran', 'lunas') === 'lunas' ? 'selected' : '' }}>Lunas</option>
                                    <option value="belum_lunas" {{ request('status_pembayaran') === 'belum_lunas' ? 'selected' : '' }}>Belum Lunas</option>
                                </select>
                                <div class="form-text">Belum lunas mencakup yang belum membayar sama sekali dan yang baru membayar DP/sebagian. Periodenya memakai tanggal PO.</div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Tujuan <span class="text-muted">(opsional)</span></label>
                                <select name="tujuan_id" class="form-select form-select-sm">
                                    <option value="">-- Semua Tujuan --</option>
                                    @foreach ($tujuans as $t)
                                        <option value="{{ $t->id }}" {{ request('tujuan_id') == $t->id ? 'selected' : '' }}>
                                            {{ $t->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            @if ($paymentCount !== null)
                                <div class="col-12">
                                    <div class="alert alert-info py-2 small mb-0">
                                        <i class="fa fa-info-circle"></i>
                                        Ditemukan <strong>{{ $paymentCount }}</strong> kendaraan sesuai filter untuk
                                        periode {{ date('d/m/Y', strtotime(request('from'))) }}
                                        &ndash; {{ date('d/m/Y', strtotime(request('to'))) }}.
                                    </div>
                                </div>
                            @endif

                            <div class="col-12">
                                <label class="form-label small fw-semibold d-block">
                                    Supplier <span class="text-muted">(bisa pilih lebih dari satu)</span>
                                </label>
                                <div class="border rounded p-3">
                                    <div class="form-check mb-3 pb-2 border-bottom">
                                        <input class="form-check-input" type="checkbox" id="checkAllSuppliers">
                                        <label class="form-check-label small fw-semibold" for="checkAllSuppliers">
                                            Pilih Semua Supplier
                                        </label>
                                    </div>
                                    <div class="row g-2">
                                        @foreach ($suppliers as $s)
                                            <div class="col-md-4 col-sm-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox"
                                                        name="supplier_ids[]" value="{{ $s->id }}"
                                                        id="supplier-{{ $s->id }}"
                                                        {{ in_array($s->id, (array) request('supplier_ids', [])) ? 'checked' : '' }}>
                                                    <label class="form-check-label small" for="supplier-{{ $s->id }}">
                                                        {{ $s->initial }} — {{ $s->nama }}
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 d-flex gap-2 mt-2">
                                <button type="submit" formaction="{{ route('keuangan.pembayaran.export-pdf') }}" class="btn btn-danger">
                                    <i class="fa fa-file-pdf-o"></i> Export PDF
                                </button>
                                <button type="submit" formaction="{{ route('keuangan.pembayaran.export-excel') }}" class="btn btn-success">
                                    <i class="fa fa-file-excel-o"></i> Export Excel
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="btnPreview">
                                    <i class="fa fa-search"></i> Preview Jumlah
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        var checkAllSuppliers = document.getElementById('checkAllSuppliers');
        var supplierCheckboxes = Array.from(document.querySelectorAll('[name="supplier_ids[]"]'));

        function syncCheckAllSuppliers() {
            var checkedCount = supplierCheckboxes.filter(function(checkbox) {
                return checkbox.checked;
            }).length;

            checkAllSuppliers.checked = supplierCheckboxes.length > 0 && checkedCount === supplierCheckboxes.length;
            checkAllSuppliers.indeterminate = checkedCount > 0 && checkedCount < supplierCheckboxes.length;
        }

        checkAllSuppliers.addEventListener('change', function() {
            supplierCheckboxes.forEach(function(checkbox) {
                checkbox.checked = checkAllSuppliers.checked;
            });
            syncCheckAllSuppliers();
        });

        supplierCheckboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', syncCheckAllSuppliers);
        });

        syncCheckAllSuppliers();

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
                tipe_pembayaran: form.querySelector('[name=tipe_pembayaran]').value,
                tujuan_id: form.querySelector('[name=tujuan_id]').value,
                status_pembayaran: form.querySelector('[name=status_pembayaran]').value,
            });
            form.querySelectorAll('[name="supplier_ids[]"]:checked').forEach(function(checkbox) {
                params.append('supplier_ids[]', checkbox.value);
            });
            window.location.href = '{{ route('keuangan.pembayaran.export-pdf-confirm') }}?' + params.toString();
        });
    </script>
@endsection
