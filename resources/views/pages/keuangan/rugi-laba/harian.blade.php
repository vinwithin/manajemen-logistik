@extends('layout.app')
@section('content')
    @php $fmt = fn($v) => number_format((float) $v, 0, ',', '.'); @endphp

    <div class="row g-3">

        {{-- Header --}}
        <div class="col-12">
            <div class="card">
                <div class="card-body d-flex justify-content-between align-items-center py-3">
                    <div>
                        <h5 class="mb-1 fw-bold">Input Biaya Harian</h5>
                        <div class="text-muted small">
                            {{ $rl->cv->nama_cv }} &nbsp;·&nbsp;
                            {{ \App\Models\RugiLaba::namaBulan($rl->bulan) }} {{ $rl->tahun }}
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('keuangan.rugi-laba.show', $rl->id) }}" class="btn btn-sm btn-info text-white">
                            <i class="fa fa-eye"></i> Lihat Laporan
                        </a>
                        <a href="{{ route('keuangan.rugi-laba.export', $rl->id) }}" class="btn btn-sm btn-success">
                            <i class="fa fa-file-excel-o"></i> Export Excel
                        </a>
                        <a href="{{ route('keuangan.rugi-laba.index', ['cv_id' => $rl->cv_id]) }}"
                            class="btn btn-sm btn-secondary">
                            <i class="fa fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Form Input --}}
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-header bg-info text-white p-3">
                    <h6 class="mb-0"><i class="fa fa-plus-circle"></i> Tambah Entri Harian</h6>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show py-2 small">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('keuangan.rugi-laba.harian.store', $rl->id) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal"
                                class="form-control form-control-sm @error('tanggal') is-invalid @enderror"
                                value="{{ old('tanggal', date('Y-m-d')) }}"
                                min="{{ $rl->tahun . '-' . str_pad($rl->bulan, 2, '0', STR_PAD_LEFT) . '-01' }}"
                                max="{{ date('Y-m-t', mktime(0, 0, 0, $rl->bulan, 1, $rl->tahun)) }}" required>
                            @error('tanggal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Jenis Biaya <span
                                    class="text-danger">*</span></label>
                            <select name="kode_biaya"
                                class="form-select form-select-sm @error('kode_biaya') is-invalid @enderror" required>
                                <option value="">-- Pilih --</option>
                                @foreach ($labels as $kode => $label)
                                    <option value="{{ $kode }}"
                                        {{ old('kode_biaya') === $kode ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('kode_biaya')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nominal (Rp) <span
                                    class="text-danger">*</span></label>
                            <input type="text" id="nominal_display"
                                class="form-control form-control-sm @error('nominal') is-invalid @enderror"
                                placeholder="0" required>
                            <input type="hidden" name="nominal" id="nominal" value="{{ old('nominal', 0) }}">
                            @error('nominal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Keterangan</label>
                            <input type="text" name="keterangan" class="form-control form-control-sm"
                                value="{{ old('keterangan') }}" placeholder="Opsional">
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="fa fa-save"></i> Simpan
                        </button>
                    </form>
                </div>
            </div>

            {{-- Ringkasan Total per Jenis --}}
            <div class="card mt-3">
                <div class="card-header bg-light p-3">
                    <h6 class="mb-0 small fw-bold">Akumulasi Bulan Ini</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="font-size:11px;">Jenis Biaya</th>
                                <th class="text-end" style="font-size:11px;">Total (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $grandTotal = 0; @endphp
                            @foreach ($labels as $kode => $label)
                                @php
                                    $total = $totals[$kode] ?? 0;
                                    $grandTotal += $total;
                                @endphp
                                @if ($total > 0)
                                    <tr>
                                        <td style="font-size:11px;">{{ $label }}</td>
                                        <td class="text-end" style="font-size:11px;">{{ $fmt($total) }}</td>
                                    </tr>
                                @endif
                            @endforeach
                            @if ($grandTotal == 0)
                                <tr>
                                    <td colspan="2" class="text-center text-muted small py-2">Belum ada entri</td>
                                </tr>
                            @endif
                        </tbody>
                        @if ($grandTotal > 0)
                            <tfoot>
                                <tr class="table-dark">
                                    <td class="fw-bold small">TOTAL</td>
                                    <td class="text-end fw-bold small">{{ $fmt($grandTotal) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        {{-- Riwayat Entri --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header p-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Riwayat Entri Harian</h6>
                    <small class="text-muted">{{ $entries->sum(fn($g) => $g->count()) }} entri</small>
                </div>
                <div class="card-body p-0">
                    @forelse ($entries as $tanggal => $group)
                        <div class="border-bottom">
                            <div class="px-3 py-2 bg-light d-flex justify-content-between align-items-center">
                                <span class="fw-semibold small">
                                    {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('l, d F Y') }}
                                </span>
                                <span class="text-muted small">
                                    Total: <strong>{{ $fmt($group->sum('nominal')) }}</strong>
                                </span>
                            </div>
                            <table class="table table-sm mb-0">
                                <tbody>
                                    @foreach ($group as $entry)
                                        <tr>
                                            <td style="width:180px; font-size:12px;">
                                                <span class="badge bg-light text-dark border">
                                                    {{ $labels[$entry->kode_biaya] ?? $entry->kode_biaya }}
                                                </span>
                                            </td>
                                            <td class="text-muted small">{{ $entry->keterangan ?? '-' }}</td>
                                            <td class="text-end fw-semibold small">Rp {{ $fmt($entry->nominal) }}</td>
                                            <td style="width:50px;" class="text-center">
                                                <button type="button"
                                                    class="btn btn-xs btn-outline-danger btn-hapus-harian"
                                                    data-id="{{ $entry->id }}" title="Hapus">
                                                    Hapus
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @empty
                        <div class="text-center text-muted py-5">
                            <i class="fa fa-inbox fa-2x mb-2"></i>
                            <div class="small">Belum ada entri harian. Tambahkan dari form di kiri.</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>

    <script>
        // Format angka menjadi rupiah
        function formatRupiah(angka) {
            var number_string = angka.toString().replace(/[^,\d]/g, '');
            var split = number_string.split(',');
            var sisa = split[0].length % 3;
            var rupiah = split[0].substr(0, sisa);
            var ribuan = split[0].substr(sisa).match(/\d{3}/gi);

            if (ribuan) {
                separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }

            rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
            return rupiah;
        }

        // Parse rupiah menjadi angka
        function parseRupiah(rupiah) {
            return parseInt(rupiah.toString().replace(/\./g, '')) || 0;
        }

        // Inisialisasi input nominal
        $(document).ready(function() {
            var $display = $('#nominal_display');
            var $hidden = $('#nominal');

            // Set nilai awal
            if ($hidden.val() > 0) {
                $display.val(formatRupiah($hidden.val()));
            }

            // Event ketika mengetik
            $display.on('input', function() {
                var value = $(this).val();
                var angka = parseRupiah(value);
                $hidden.val(angka);
                $(this).val(formatRupiah(angka));
            });
        });

        $(document).on('click', '.btn-hapus-harian', function() {
            var id = $(this).data('id');
            var $row = $(this).closest('tr');
            alertify.confirm('Hapus entri ini?', '', function() {
                $.ajax({
                    url: '{{ url('keuangan/rugi-laba/harian') }}/' + id,
                    method: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        if (res.success) {
                            alertify.success(res.message);
                            setTimeout(() => location.reload(), 500);
                        }
                    },
                    error: function() {
                        alertify.error('Gagal menghapus.');
                    }
                });
            }, function() {}).set('labels', {
                ok: 'Hapus',
                cancel: 'Batal'
            });
        });
    </script>
@endsection
