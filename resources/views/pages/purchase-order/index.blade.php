@extends('layout.app')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Daftar Purchase Order</h5>
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        {{-- Filter tanggal untuk export --}}
                        <input type="date" id="exportFrom" class="form-control form-control-sm" style="width:140px"
                            placeholder="Dari">
                        <input type="date" id="exportTo" class="form-control form-control-sm" style="width:140px"
                            placeholder="Sampai">
                        <a href="#" id="btnExport" class="btn btn-sm btn-success">
                            <i class="fa fa-file-excel-o"></i> Export Semua Data
                        </a>
                        <a href="{{ route('purchase-order.create') }}" class="btn btn-sm btn-primary">
                            <i class="fa fa-plus"></i> Input PO
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-striped table-bordered" id="table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>No. PO</th>
                                <th>Tanggal</th>
                                <th>CV</th>
                                <th>Kendaraan</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#table').DataTable({
                bAutoWidth: false,
                iDisplayLength: 15,
                searching: true,
                processing: true,
                serverSide: true,
                bDestroy: true,
                order: [
                    [2, 'desc']
                ],
                ajax: {
                    url: '/purchase-order'
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        width: '50px'
                    },
                    {
                        data: 'no_po',
                        name: 'no_po'
                    },
                    {
                        data: 'tanggal_po',
                        name: 'tanggal_po'
                    },
                    {
                        data: 'cv_name',
                        name: 'cv_name',
                        searchable: false
                    },
                    {
                        data: 'jumlah_mobil',
                        name: 'jumlah_mobil',
                        searchable: false
                    },
                    {
                        data: 'status_badge',
                        name: 'status',
                        searchable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                aLengthMenu: [
                    [10, 15, 25, 50],
                    [10, 15, 25, 50]
                ],
                responsive: !0
            });
        });

        // Build export URL dengan filter
        function updateExportUrl() {
            var from = $('#exportFrom').val();
            var to = $('#exportTo').val();
            var url = '{{ route('purchase-order.export') }}';
            var params = [];
            if (from) params.push('from=' + from);
            if (to) params.push('to=' + to);
            if (params.length) url += '?' + params.join('&');
            $('#btnExport').attr('href', url);
        }

        $('#exportFrom, #exportTo').on('change', updateExportUrl);
        updateExportUrl();

        function confirmDelete(id) {
            alertify.confirm("Konfirmasi!", "Hapus PO ini?", function() {
                $('#del-po-' + id).submit();
            }, function() {});
        }

        function confirmLock(id) {
            alertify.confirm(
                "Kunci PO?",
                "PO yang dikunci tidak dapat diedit lagi. Lanjutkan?",
                function() {
                    $.post('/purchase-order/' + id + '/lock', {
                            _token: '{{ csrf_token() }}'
                        })
                        .done(function(res) {
                            if (res.success) {
                                alertify.success(res.message);
                                $('#table').DataTable().ajax.reload();
                            } else {
                                alertify.error(res.message);
                            }
                        });
                },
                function() {}
            );
        }
    </script>
@endsection
