<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>PO Periode {{ $from ?? 'Semua' }} - {{ $to ?? 'Semua' }} - Supplier</title>
    <style>
        * {
            margin: 35px 35px 0 35px;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
        }

        /* ===== JUDUL ===== */
        .doc-title {
            text-align: center;
            font-size: 16px;
            font-weight: normal;
            margin: 0;
        }

        .doc-subtitle {
            text-align: center;
            font-size: 9px;
            margin-bottom: 8px;
        }

        /* ===== INFO HEADER ===== */
        .info-box {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            border: 1px solid #000;
        }

        .info-box td {
            padding: 3px 6px;
            font-size: 8px;
            border: 1px solid #aaa;
        }

        .info-box td.info-label {
            font-weight: bold;
            width: 80px;
        }

        .info-box td.info-value {}

        /* ===== TABEL UTAMA ===== */
        table.rekap {
            margin-bottom: 0;
            width: 95%;
            border-collapse: collapse;
            border: 1px solid #000;
        }

        .isi {
            text-align: center;
        }

        /* Header baris 1: group */
        table.rekap thead tr.head-group th {
            color: white;
            font-weight: bold;
            background: rgb(123, 123, 239);
            font-size: 11px;
            text-align: center;
            padding: 5px 2px;
            border: 1px solid #000;
            vertical-align: middle;
        }

        /* Header baris 2: sub kode pakan */
        table.rekap thead tr.head-sub th {
            color: white;
            font-weight: bold;
            font-size: 11px;
            text-align: center;
            background: rgb(123, 123, 239);
            padding: 3px 2px;
            border: 1px solid #000;
        }

        /* Data rows */
        table.rekap tbody td {
            padding: 3px 3px;
            border: 1px solid #000;
            font-size: 11px;
            text-align: center;
            vertical-align: middle;
        }

        table.rekap tbody td.td-center {
            text-align: center;
        }

        table.rekap tbody td.td-no {
            font-weight: bold;
            color: #000;
        }

        /* Nilai pakan */
        td.td-karung-val {
            color: #000;
        }

        td.td-kg-val {
            color: #000;
        }

        td.td-total {
            font-weight: bold;
            color: #000;
        }

        td.td-harga {
            color: #000;
        }

        /* Label satuan */
        .unit-label {
            font-size: 6px;
            color: #999;
            display: block;
        }

        /* Baris TOTAL */
        tr.row-total td {
            color: #000;
            font-weight: bold;
            font-size: 8px;
            padding: 5px 3px;
            border: 1px solid #000;
        }

        /* ===== SIGNATURE ===== */
        .sign-table {
            width: 90%;
            border-collapse: collapse;
            margin-top: 0;
        }

        .sign-table td {
            text-align: start;
            vertical-align: top;
            width: 33%;
            padding: 6px;
        }

        .sign-label {
            font-size: 11px;
            font-weight: normal;
            display: block;
            margin-bottom: 50px;
        }

        .sign-line {
            display: inline-block;
            border-top: 1px solid #000;
            min-width: 120px;
            padding-top: 3px;
            font-size: 7.5px;
        }

        /* ===== PAGE BREAK ===== */
        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>

    @php
        $cv = $pos->first()?->cv;
        $cvLogo = $cv?->logo;
        $cvNama = $cv?->nama_cv ?? '-';
        $cvAlamat = $cv?->alamat ?? '-';
        $cvPrefix = $cv?->no_dokumen_prefix ?? '';
        $logoInisial = strtoupper(substr(preg_replace('/^CV\.?\s*/i', '', $cvNama), 0, 2));
    @endphp

    {{-- Header dengan Logo --}}
    <div style="position: relative; margin-bottom: 15px;">
    

        <div class="doc-title">REKAPITULASI PENGIRIMAN PAKAN</div>
        <div class="doc-title">PT. SURYA UNGGAS MANDIRI</div>
        <div class="doc-title">UNIT JAMBI</div>
        <div class="doc-title">Periode :&nbsp;
            @if ($from && $to)
                @php
                    $periodeFrom = \Illuminate\Support\Carbon::parse($from)->locale('id');
                    $periodeTo = \Illuminate\Support\Carbon::parse($to)->locale('id');
                @endphp
                @if ($periodeFrom->isSameDay($periodeTo))
                    {{ $periodeFrom->translatedFormat('j F Y') }}
                @elseif ($periodeFrom->month === $periodeTo->month && $periodeFrom->year === $periodeTo->year)
                    {{ $periodeFrom->day }} &ndash; {{ $periodeTo->day }} {{ $periodeFrom->translatedFormat('F Y') }}
                @else
                    {{ $periodeFrom->translatedFormat('j F Y') }} &ndash; {{ $periodeTo->translatedFormat('j F Y') }}
                @endif
            @elseif ($from)
                Dari {{ \Illuminate\Support\Carbon::parse($from)->locale('id')->translatedFormat('j F Y') }}
            @elseif ($to)
                Sampai {{ \Illuminate\Support\Carbon::parse($to)->locale('id')->translatedFormat('j F Y') }}
            @else
                Semua Periode
            @endif
        </div>
    </div>

    {{-- Tabel Utama --}}
    <table class="rekap">
        <thead>
            <tr class="head-group">
                <th style="width:5px;">No</th>
                <th style="width:35px;">Tanggal</th>
                <th style="width:38px;">Kode Pakan</th>
                <th style="width:28px;">No. DO</th>
                <th style="width:42px;">No. Mobil</th>
                <th style="width:65px;">Tujuan</th>
                <th style="width:65px;">Cost Center</th>
                <th style="width:42px;">Jumlah<br>(Kg)</th>
                <th style="width:30px;">Bag</th>
                <th style="width:30px;">Ongkos</th>
                <th style="width:45px;">Total<br>Ongkos</th>
                <th style="width:45px;">DP</th>
                <th style="width:50px;">Supplier</th>
            </tr>
        </thead>
        <tbody>
            @php
                $no = 1;
                $grandTotalKg = 0;
                $grandTotalKarung = 0;
                $grandTotalOngkos = 0;
                $grandTotalDp = 0;
                $rowIdx = 0;
            @endphp

            @foreach ($pos as $po)
                @foreach ($po->kendaraans->sortBy('no_polisi') as $kendaraan)
                    @php
                        $penerimaCount = $kendaraan->penerimas->count();
                        $isFirstPenerima = true;
                        // Hitung total DP untuk kendaraan ini
                        $totalDpKendaraan = (float) $kendaraan->oaPayments->where('tipe_pembayaran', 'dp_supplier')->sum('jumlah_bayar');
                        $grandTotalDp += $totalDpKendaraan;
                    @endphp
                    @foreach ($kendaraan->penerimas as $penerima)
                        @php
                            $totalKgPenerima = (float) $penerima->pakans->sum('jumlah_kg');
                            $totalKarungPenerima = (int) $penerima->pakans->sum(
                                fn($p) => (int) ($p->jumlah_karung ?? 0),
                            );
                            $totalOngkosPenerima = (float) $penerima->pakans->sum(
                                fn($p) => (float) $p->jumlah_kg * (float) ($p->ongkos_oa ?? 0),
                            );
                            $ongkosPerKg = $totalKgPenerima > 0 ? $totalOngkosPenerima / $totalKgPenerima : 0;
                            $kodePakanStr = $penerima->pakans
                                ->map(fn($p) => $p->kodePakan?->kode)
                                ->filter()
                                ->unique()
                                ->values()
                                ->implode(', ');
                            $rowClass = $rowIdx % 2 === 0 ? 'row-even' : 'row-odd';
                            $rowIdx++;
                        @endphp

                        <tr class="{{ $rowClass }} isi">
                            @if ($isFirstPenerima)
                                <td class="td-no" rowspan="{{ $penerimaCount }}" style="vertical-align:middle;">
                                    {{ $no++ }}</td>
                                <td rowspan="{{ $penerimaCount }}" style="vertical-align:middle;">
                                    {{ $po->tanggal_po->translatedFormat('d F Y') }}</td>
                                <td class="td-center" style="vertical-align:middle;">
                                    {{ $kodePakanStr !== '' ? $kodePakanStr : '—' }}</td>
                                <td rowspan="{{ $penerimaCount }}" style="vertical-align:middle;">
                                    {{ $kendaraan->no_surat_jalan ?? '-' }}</td>
                                <td rowspan="{{ $penerimaCount }}" style="vertical-align:middle;">
                                    {{ $kendaraan->no_polisi }}</td>
                                @php $isFirstPenerima = false; @endphp
                            @else
                                <td class="td-center">
                                    {{ $kodePakanStr !== '' ? $kodePakanStr : '—' }}</td>
                            @endif
                            <td class="td-center">{{ Str::upper($penerima->nama_penerima ?? '-') }}</td>
                            <td class="td-center">{{ Str::upper($penerima->tujuan?->nama ?? '-') }}</td>

                            <td class="td-kg-val">
                                {{ $totalKgPenerima > 0 ? number_format($totalKgPenerima, 0, ',', '.') : '' }}</td>
                            <td class="td-karung-val">
                                {{ $totalKarungPenerima > 0 ? number_format($totalKarungPenerima, 0, ',', '.') : '' }}
                            </td>

                            {{-- Ongkos OA per kg --}}
                            <td>{{ $ongkosPerKg > 0 ? number_format($ongkosPerKg, 0, ',', '.') : '-' }}</td>

                            {{-- Total Ongkos --}}
                            <td class="td-harga">
                                {{ $totalOngkosPenerima > 0 ? number_format($totalOngkosPenerima, 0, ',', '.') : '-' }}
                            </td>

                            {{-- DP --}}
                            @if ($loop->first)
                                <td rowspan="{{ $penerimaCount }}" style="vertical-align:middle; font-weight:bold;">
                                    {{ $totalDpKendaraan > 0 ? number_format($totalDpKendaraan, 0, ',', '.') : '-' }}
                                </td>
                            @endif

                             <td rowspan="{{ $isFirstPenerima ? 1 : 1 }}" style="font-weight: bold;">
                                {{ $kendaraan->supplier?->nama ?? '-' }}
                            </td>
                        </tr>

                        @php
                            $grandTotalKg += $totalKgPenerima;
                            $grandTotalKarung += $totalKarungPenerima;
                            $grandTotalOngkos += $totalOngkosPenerima;
                        @endphp
                    @endforeach
                @endforeach
            @endforeach

            {{-- GRAND TOTAL --}}
            <tr class="row-total">
                <td colspan="7" style="text-align:center; padding-left:8px; letter-spacing:1px;">TOTAL</td>
                <td>{{ $grandTotalKg > 0 ? number_format($grandTotalKg, 0, ',', '.') : '-' }}</td>
                <td>{{ $grandTotalKarung > 0 ? number_format($grandTotalKarung, 0, ',', '.') : '-' }}</td>
                <td colspan="1"></td>
                <td>{{ number_format($grandTotalOngkos, 0, ',', '.') }}</td>
                <td>{{ $grandTotalDp > 0 ? number_format($grandTotalDp, 0, ',', '.') : '-' }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    {{-- Tanda Tangan --}}
    <table class="sign-table">
        <tr>
            <td>
                <span class="sign-label">Diverifikasi Oleh, <br>Finance Accounting</span>
                <span
                    class="sign-line">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
            </td>
            <td>
                <span class="sign-label">Diketahui Oleh, <br> Branch Manager</span>
                <span
                    class="sign-line">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
            </td>
            <td>
                <span class="sign-label">Jambi, {{ now()->translatedFormat('d F Y') }}</span>
                <span
                    class="sign-line">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
            </td>
        </tr>
    </table>

</body>

</html>
