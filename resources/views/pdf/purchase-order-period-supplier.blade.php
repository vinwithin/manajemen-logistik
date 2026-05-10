<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>PO Periode {{ $from ?? 'Semua' }} - {{ $to ?? 'Semua' }} - Supplier</title>
    <style>
        * {
            margin: 18px;
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
            font-size: 12px;
            font-weight: normal;
            text-transform: uppercase;
            /* letter-spacing: 1px; */
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
            width: 100%;
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
            font-size: 7.5px;
            text-align: center;
            padding: 5px 2px;
            border: 1px solid #000;
            vertical-align: middle;
        }

        /* Header baris 2: sub kode pakan */
        table.rekap thead tr.head-sub th {
            color: white;
            font-weight: bold;
            font-size: 7px;
            text-align: center;
            background: rgb(123, 123, 239);
            padding: 3px 2px;
            border: 1px solid #000;
        }

        /* Data rows */
        table.rekap tbody td {
            padding: 3px 3px;
            border: 1px solid #000;
            font-size: 7.5px;
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
            font-weight: bold;
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
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }

        .sign-table td {
            text-align: start;
            vertical-align: top;
            width: 33%;
            padding: 6px;
        }

        .sign-label {
            font-size: 10px;
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

        .page-break {
            page-break-after: always;
        }

        /* ===== NOTA ===== */
        .nota-wrap {
            font-family: 'Times New Roman', Times, serif;
            font-size: 10px;
            color: #000;
        }

        .nota-header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .nota-header-table td {
            vertical-align: top;
            padding: 2px 4px;
            font-size: 9px;
        }

        .nota-logo-box {
            font-weight: bold;
            font-size: 22px;
            color: #000;
        }

        .nota-company-sub {
            font-size: 8px;
            font-style: italic;
        }

        .nota-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            text-decoration: underline;
            margin: 8px 0 2px 0;
        }

        .nota-no {
            text-align: center;
            font-size: 9px;
            margin-bottom: 10px;
        }

        .nota-yth {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .nota-yth td {
            padding: 2px 4px;
            font-size: 9px;
        }

        .nota-yth td.nota-yth-label {
            width: 60px;
            font-weight: bold;
        }

        .nota-yth td.nota-yth-line {
            border-bottom: 1px solid #000;
            min-width: 150px;
        }

        table.nota-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000;
            margin-bottom: 8px;
        }

        table.nota-table th {
            border: 1px solid #000;
            padding: 5px 4px;
            text-align: center;
            font-size: 9px;
            font-weight: bold;
        }

        table.nota-table td {
            border: 1px solid #000;
            padding: 4px 4px;
            text-align: center;
            font-size: 9px;
        }

        table.nota-table td.nota-td-left {
            text-align: left;
        }

        table.nota-table tr.nota-total td {
            font-weight: bold;
            border-top: 2px solid #000;
        }

        .nota-terbilang {
            font-size: 8.5px;
            font-style: italic;
            margin-bottom: 20px;
        }

        .nota-sign-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .nota-sign-table td {
            text-align: center;
            vertical-align: top;
            font-size: 9px;
            padding: 4px;
        }

        .nota-sign-city {
            display: block;
            margin-bottom: 50px;
            font-size: 9px;
            text-align: right;
        }

        .nota-sign-line {
            display: inline-block;
            border-top: 1px solid #000;
            min-width: 100px;
            padding-top: 3px;
            font-size: 8.5px;
            font-weight: bold;
        }
    </style>
</head>

<body>


    <div class="doc-title">REKAPITULASI PENGIRIMAN PAKAN</div>
    <div class="doc-title">PT. SURYA UNGGAS MANDIRI</div>
    <div class="doc-title">UNIT JAMBI</div>
    <div class="doc-title">PERIODE :&nbsp;
        @if ($from && $to)
            {{ date('d/m/Y', strtotime($from)) }} &ndash; {{ date('d/m/Y', strtotime($to)) }}
        @elseif($from)
            Dari {{ date('d/m/Y', strtotime($from)) }}
        @elseif($to)
            Sampai {{ date('d/m/Y', strtotime($to)) }}
        @else
            Semua Periode
        @endif
    </div>

    @php $kpCount = count($kodePakanList); @endphp

    <table class="rekap">
        <thead>
            <tr class="head-group">
                <th rowspan="2" style="width:18px;">NO</th>
                <th rowspan="2" style="width:48px;">TANGGAL</th>
                <th rowspan="2" style="width:48px;">No. DO</th>
                <th rowspan="2" style="width:52px;">No. Mobil</th>


                {{-- Group Jumlah Karung --}}
                <th colspan="{{ $kpCount }}" widt>JUMLAH (BAG)</th>
                {{-- Group KG --}}
                <th colspan="{{ $kpCount }}">JUMLAH (KG)</th>

                <th rowspan="2" style="width:80px;">Tujuan</th>
                {{-- <th rowspan="2" style="width:80px;">PENERIMA</th> --}}
                {{-- Kolom tunggal --}}
                <th rowspan="2" style="width:38px;">ONGKOS(Rp/kg)</th>
                <th rowspan="2" style="width:55px;">TOTAL<br>HARGA (Rp)</th>
                <th rowspan="2" style="width:55px;">SUPPLIER</th>
            </tr>
            {{-- Header Baris 2: kode pakan --}}
            <tr class="head-sub">
                @foreach ($kodePakanList as $kp)
                    <th style="width:25px;">{{ $kp->kode }}</th>
                @endforeach
                @foreach ($kodePakanList as $kp)
                    <th style="width:25px;">{{ $kp->kode }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php
                $no = 1;
                $subtotalsKarung = array_fill(0, $kpCount, 0);
                $subtotalsKg = array_fill(0, $kpCount, 0);
                $grandTotalKg = 0;
                $grandTotalOngkos = 0;
                $rowIdx = 0;
            @endphp

            @foreach ($pos as $po)
                @foreach ($po->kendaraans->sortBy('no_polisi') as $kendaraan)
                    @php
                        $penerimaCount = $kendaraan->penerimas->count();
                        $isFirstPenerima = true;
                    @endphp
                    @foreach ($kendaraan->penerimas as $penerima)
                        @php
                            $totalKgPenerima = 0;
                            $totalOngkosPenerima = 0;
                            $ongkosPerKg = 0;
                            $rowClass = $rowIdx % 2 === 0 ? 'row-even' : 'row-odd';
                            $rowIdx++;
                        @endphp
                        <tr class="{{ $rowClass }}">
                            @if ($isFirstPenerima)
                                <td class="td-no" rowspan="{{ $penerimaCount }}" style="vertical-align:middle;">
                                    {{ $no++ }}</td>
                                <td rowspan="{{ $penerimaCount }}" style="vertical-align:middle;">
                                    {{ $po->tanggal_po->format('d/m/Y') }}</td>
                                <td rowspan="{{ $penerimaCount }}" style="vertical-align:middle;">
                                    {{ $kendaraan->no_surat_jalan ?? '-' }}</td>
                                <td rowspan="{{ $penerimaCount }}" style="vertical-align:middle;">
                                    {{ $kendaraan->no_polisi }}</td>

                                @php $isFirstPenerima = false; @endphp
                            @endif

                            @foreach ($kodePakanList as $i => $kp)
                                @php
                                    $pakan = $penerima->pakans->firstWhere('kode_pakan_id', $kp->id);
                                    if ($pakan) {
                                        $subtotalsKarung[$i] += $pakan->jumlah_karung;
                                    }
                                @endphp
                                <td class="td-karung-val">
                                    {{ $pakan && $pakan->jumlah_karung ? $pakan->jumlah_karung : '' }}</td>
                            @endforeach

                            @foreach ($kodePakanList as $i => $kp)
                                @php
                                    $pakan = $penerima->pakans->firstWhere('kode_pakan_id', $kp->id);
                                    if ($pakan && $pakan->jumlah_kg) {
                                        $subtotalsKg[$i] += $pakan->jumlah_kg;
                                        $totalKgPenerima += $pakan->jumlah_kg;
                                        $totalOngkosPenerima += $pakan->jumlah_kg * ($pakan->ongkos_oa ?? 0);
                                        if (!$ongkosPerKg && $pakan->ongkos_oa) {
                                            $ongkosPerKg = $pakan->ongkos_oa;
                                        }
                                    }
                                @endphp
                                <td class="td-kg-val">
                                    {{ $pakan && $pakan->jumlah_kg ? number_format($pakan->jumlah_kg, 0, ',', '.') : '' }}
                                </td>
                            @endforeach

                            <td class="td-center">{{ $penerima->tujuan?->nama ?? '-' }}</td>
                            <td>{{ $ongkosPerKg > 0 ? number_format($ongkosPerKg, 0, ',', '.') : '-' }}</td>
                            <td class="td-total">
                                {{ $totalOngkosPenerima > 0 ? number_format($totalOngkosPenerima, 0, ',', '.') : '-' }}
                            </td>
                            <td style="font-weight: bold">{{$kendaraan->supplier?->nama ?? '-'}}</td>
                        </tr>
                        @php
                            $grandTotalKg += $totalKgPenerima;
                            $grandTotalOngkos += $totalOngkosPenerima;
                        @endphp
                    @endforeach
                @endforeach
            @endforeach

            <tr class="row-total">
                <td colspan="4" style="text-align:left; padding-left:8px; letter-spacing:1px;">TOTAL</td>
                @foreach ($subtotalsKarung as $s)
                    <td>{{ $s > 0 ? number_format($s, 0, ',', '.') : '-' }}</td>
                @endforeach
                @foreach ($subtotalsKg as $s)
                    <td>{{ $s > 0 ? number_format($s, 0, ',', '.') : '-' }}</td>
                @endforeach
                <td></td>
                <td></td>
                <td>{{ number_format($grandTotalOngkos, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <table class="sign-table">
        <tr>
            <td>
                <span class="sign-label">Diverifikasi Oleh, <br>Finance Accounting</span>
                <span
                    class="sign-line">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</span>
            </td>
            <td>
                <span class="sign-label">Diketahui Oleh, <br>Branch Manager</span>
                <span
                    class="sign-line">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</span>
            </td>
            <td>
                <span class="sign-label"></span>
                <span
                    class="sign-line">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</span>
            </td>
        </tr>
    </table>

    {{-- ==================== PAGE BREAK ==================== --}}
    <div class="page-break"></div>

    {{-- ==================== HALAMAN 2: NOTA PEMBAYARAN ==================== --}}
    @php
        $cv = $pos->first()?->cv;
        $cvNama = $cv?->nama_cv ?? '-';
        $cvAlamat = $cv?->alamat ?? '-';
        $cvPimpinan = $cv?->nama_pimpinan ?? '';
        $cvPrefix = $cv?->no_dokumen_prefix ?? '';
        $noNota = $cvPrefix ? $cvPrefix . '/SUP/' . now()->format('Y/m') : 'SUP/' . now()->format('Y/m');
        $totalNota = 0;
        $notaRows = [];
        $noRow = 1;
        foreach ($pos as $po) {
            foreach ($po->kendaraans->sortBy('no_polisi') as $kendaraan) {
                foreach ($kendaraan->penerimas as $penerima) {
                    $totalKg = $penerima->pakans->sum('jumlah_kg');
                    $ongkos = $penerima->pakans->whereNotNull('ongkos_oa')->first()?->ongkos_oa ?? 0;
                    $total = $totalKg * $ongkos;
                    $totalNota += $total;
                    $notaRows[] = [
                        'no' => $noRow++,
                        'tgl' => $po->tanggal_po->format('d-M-Y'),
                        'no_mobil' => $kendaraan->no_polisi,
                        'tujuan' => $penerima->tujuan?->nama ?? '-',
                        'volume' => $totalKg,
                        'harga' => $ongkos,
                        'uang_jalan' => 0,
                        'total' => $total,
                    ];
                }
            }
        }
        try {
            $formatter = new NumberFormatter('id', NumberFormatter::SPELLOUT);
            $terbilang = ucwords($formatter->format($totalNota));
        } catch (Exception $e) {
            $terbilang = '...';
        }
        $periodeStr = '';
        if ($from && $to) {
            $periodeStr = date('d M Y', strtotime($from)) . ' s/d ' . date('d M Y', strtotime($to));
        } elseif ($from) {
            $periodeStr = 'Dari ' . date('d M Y', strtotime($from));
        } elseif ($to) {
            $periodeStr = 'Sampai ' . date('d M Y', strtotime($to));
        }
    @endphp

    <div class="nota-wrap">
        <table class="nota-header-table">
            <tr>
                <td style="width:55%;">
                    <div class="nota-logo-box">
                        {{ strtoupper(substr(preg_replace('/^CV\.?\s*/i', '', $cvNama), 0, 2)) }}</div>
                    <div style="font-size:9px; font-weight:bold;">{{ $cvNama }}</div>
                    <div class="nota-company-sub">{{ $cvAlamat }}</div>
                </td>
                <td style="width:45%; vertical-align:middle;">
                    <table class="nota-yth">
                        <tr>
                            <td class="nota-yth-label">Yth</td>
                            <td style="width:10px;"></td>
                            <td class="nota-yth-line"><strong>PT. SURYA UNGGAS MANDIRI</strong></td>
                        </tr>
                        <tr>
                            <td class="nota-yth-label">Alamat</td>
                            <td style="width:10px;"></td>
                            <td class="nota-yth-line">Jambi</td>
                        </tr>
                        @if ($periodeStr)
                            <tr>
                                <td class="nota-yth-label">Periode</td>
                                <td style="width:10px;"></td>
                                <td class="nota-yth-line">{{ $periodeStr }}</td>
                            </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>

        <div class="nota-title">NOTA PEMBAYARAN</div>
        <div class="nota-no">No : &nbsp; {{ $noNota }}</div>

        <table class="nota-table">
            <thead>
                <tr>
                    <th style="width:25px;">No</th>
                    <th style="width:65px;">Tgl DO</th>
                    <th style="width:65px;">No Mobil</th>
                    <th style="width:70px;">Tujuan</th>
                    <th style="width:55px;">Volume</th>
                    <th style="width:60px;">Harga (Rp/kg)</th>
                    <th style="width:55px;">Uang Jalan</th>
                    <th style="width:65px;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($notaRows as $row)
                    <tr>
                        <td>{{ $row['no'] }}</td>
                        <td>{{ $row['tgl'] }}</td>
                        <td>{{ $row['no_mobil'] }}</td>
                        <td class="nota-td-left">{{ $row['tujuan'] }}</td>
                        <td>{{ number_format($row['volume'], 0, ',', '.') }}</td>
                        <td>{{ number_format($row['harga'], 0, ',', '.') }}</td>
                        <td>{{ number_format($row['uang_jalan'], 0, ',', '.') }}</td>
                        <td>{{ number_format($row['total'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                @for ($i = count($notaRows); $i < 8; $i++)
                    <tr>
                        <td>&nbsp;</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                @endfor
                <tr class="nota-total">
                    <td colspan="4" style="text-align:center; letter-spacing:2px;">TOTAL</td>
                    <td></td>
                    <td></td>
                    <td>0</td>
                    <td>{{ number_format($totalNota, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <div class="nota-terbilang">Terbilang : <em>{{ $terbilang }} Rupiah,-</em></div>

        <table class="nota-sign-table">
            <tr>
                <td style="width:33%;">
                    <span style="display:block; margin-bottom:50px;">Disetujui,</span>
                    <span
                        class="nota-sign-line">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</span>
                </td>
                <td style="width:33%;">
                    <span style="display:block; margin-bottom:50px;">Diperiksa,</span>
                    <span
                        class="nota-sign-line">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</span>
                </td>
                <td style="width:33%;">
                    <span class="nota-sign-city">Jambi, {{ now()->format('d-M-y') }}</span>
                    <span style="display:block; margin-bottom:5px;">Hormat kami,</span>
                    <span style="display:block; margin-bottom:45px;"></span>
                    <span
                        class="nota-sign-line">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</span>
                    @if ($cvPimpinan)
                        <br><strong>{{ $cvPimpinan }}</strong>
                    @endif
                </td>
            </tr>
        </table>
    </div>

</body>

</html>
