<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>PO Periode {{ $from ?? 'Semua' }} - {{ $to ?? 'Semua' }} - PT Sum</title>
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

        /* ===== PAGE BREAK ===== */
        .page-break {
            page-break-after: always;
        }

        /* ===== KWITANSI ===== */
        .kwitansi-wrap {
            font-family: 'Times New Roman', Times, serif;
            font-size: 10px;
        }

        .kwit-header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .kwit-header-table td {
            vertical-align: middle;
            /* padding: 2px 5px; */
        }


        .kwit-company-name {
            font-size: 18px;
            font-weight: bold;
            color: rgb(123, 123, 239);
            letter-spacing: 1px;
        }

        .kwit-company-sub {
            font-size: 8px;
            color: rgb(123, 123, 239);
            font-style: italic;
        }

        .kwit-divider {
            border: none;
            border-top: 2.5px solid #000;
            margin: 6px 0 0 0;
        }

        .kwit-title-bar {
            color: #000;
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            background: rgb(162, 218, 215);
            padding: 7px 0;
            letter-spacing: 5px;
            margin: 10px 0 0 0;
        }

        .kwit-outer {
            border: 1.5px solid #000;
            margin-top: 14px;
        }

        .kwit-body-table {
            width: 95%;
            border-collapse: collapse;
        }

        .kwit-body-table td {
            padding: 5px 4px;
            vertical-align: top;
            font-size: 10px;
        }

        .kwit-body-table td.kwit-label {
            font-weight: bold;
            width: 155px;
            white-space: nowrap;
            color: #000;
        }

        .kwit-body-table td.kwit-colon {
            width: 8px;
        }

        .kwit-body-table td.kwit-value {
            border-bottom: 1px solid #aaa;
        }

        .kwit-amount-box {
            /* padding: 8px 12px; */
            margin-top: 4px;
            display: inline-block;
        }

        .kwit-amount {
            font-size: 18px;
            font-weight: bold;
            color: #000;
        }

        .kwit-terbilang {
            font-style: italic;
            font-size: 12px;
            color: #444;
        }

        .kwit-payment-box {
            padding: 8px 12px;
            margin-top: 16px;
            font-size: 9px;
        }

        .kwit-payment-box .pay-title {
            font-weight: bold;
            font-size: 9px;
            color: #000;
            margin-bottom: 3px;
        }

        .kwit-sign-table {
            width: 100%;
            border-collapse: collapse;
            /* margin-top: 18px; */
        }

        .kwit-sign-table td {
            text-align: center;
            font-size: 9px;
            vertical-align: top;
        }

        .kwit-sign-city {
            display: block;
            margin-bottom: 55px;
            font-size: 9px;
        }

        .kwit-sign-line {
            display: inline-block;
            border-top: 1px solid #000;
            min-width: 140px;
            padding-top: 4px;
            font-size: 8.5px;
        }
    </style>
</head>

<body>

    {{-- ==================== HALAMAN 1: REKAPITULASI ==================== --}}

    <div class="doc-title">REKAPITULASI PENGIRIMAN PAKAN</div>
    <div class="doc-title">PT. SURYA UNGGAS MMANDIRI</div>
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

    {{-- Tabel Utama --}}
    @php
        $kpCount = count($kodePakanList);
    @endphp

    <table class="rekap">
        <thead>
            {{-- Header Baris 1: group --}}
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
                $grandTotalKarung = 0;
                $grandTotalHarga = 0;
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
                            $totalKarungPenerima = 0;
                            $totalHargaPenerima = 0;
                            $hargaPtSum = 0;
                            $rowClass = $rowIdx % 2 === 0 ? 'row-even' : 'row-odd';
                            $rowIdx++;
                        @endphp

                        <tr class="{{ $rowClass }} isi">
                            {{-- Kolom identitas kendaraan: hanya di baris pertama, pakai rowspan --}}
                            @if ($isFirstPenerima)
                                <td class="td-no" rowspan="{{ $penerimaCount }}" style="vertical-align:middle;">
                                    {{ $no++ }}</td>
                                <td rowspan="{{ $penerimaCount }}" style="vertical-align:middle;">
                                    {{ $po->tanggal_po->translatedFormat('d F Y') }}</td>


                                <td rowspan="{{ $penerimaCount }}" style="vertical-align:middle;">
                                    {{ $kendaraan->no_surat_jalan ?? '-' }}</td>
                                <td rowspan="{{ $penerimaCount }}" style="vertical-align:middle;">
                                    {{ $kendaraan->no_polisi }}</td>
                                @php $isFirstPenerima = false; @endphp
                            @endif

                            {{-- Jumlah Karung per kode pakan --}}
                            @foreach ($kodePakanList as $i => $kp)
                                @php
                                    $pakan = $penerima->pakans->firstWhere('kode_pakan_id', $kp->id);
                                    if ($pakan) {
                                        $subtotalsKarung[$i] += $pakan->jumlah_karung;
                                        $totalKarungPenerima += $pakan->jumlah_karung;
                                    }
                                @endphp
                                <td class="td-karung-val">
                                    @if ($pakan && $pakan->jumlah_karung)
                                        {{ $pakan->jumlah_karung }}
                                    @endif
                                </td>
                            @endforeach

                            {{-- KG per kode pakan --}}
                            @foreach ($kodePakanList as $i => $kp)
                                @php
                                    $pakan = $penerima->pakans->firstWhere('kode_pakan_id', $kp->id);
                                    if ($pakan && $pakan->jumlah_kg) {
                                        $subtotalsKg[$i] += $pakan->jumlah_kg;
                                        $totalKgPenerima += $pakan->jumlah_kg;
                                        $totalHargaPenerima += $pakan->jumlah_kg * ($pakan->harga_pt_sum ?? 0);
                                        if (!$hargaPtSum && $pakan->harga_pt_sum) {
                                            $hargaPtSum = $pakan->harga_pt_sum;
                                        }
                                    }
                                @endphp
                                <td class="td-kg-val">
                                    @if ($pakan && $pakan->jumlah_kg)
                                        {{ number_format($pakan->jumlah_kg, 0, ',', '.') }}
                                    @endif
                                </td>
                            @endforeach
                            {{-- <td class="td-left">{{ $penerima->nama_penerima }}</td> --}}
                            <td class="td-center">{{ $penerima->tujuan->nama }}</td>


                            {{-- Harga PT SUM per kg --}}
                            <td>{{ $hargaPtSum > 0 ? number_format($hargaPtSum, 0, ',', '.') : '-' }}</td>

                            {{-- Total Harga --}}
                            <td class="td-harga">
                                {{ $totalHargaPenerima > 0 ? number_format($totalHargaPenerima, 0, ',', '.') : '-' }}
                            </td>


                        </tr>

                        @php
                            $grandTotalKg += $totalKgPenerima;
                            $grandTotalKarung += $totalKarungPenerima;
                            $grandTotalHarga += $totalHargaPenerima;
                        @endphp
                    @endforeach
                @endforeach
            @endforeach

            {{-- GRAND TOTAL --}}
            <tr class="row-total">
                <td colspan="4" style="text-align:left; padding-left:8px; letter-spacing:1px;">TOTAL</td>
                {{-- Subtotal karung per pakan --}}
                @foreach ($subtotalsKarung as $s)
                    <td>{{ $s > 0 ? number_format($s, 0, ',', '.') : '-' }}</td>
                @endforeach
                {{-- Subtotal kg per pakan --}}
                @foreach ($subtotalsKg as $s)
                    <td>{{ $s > 0 ? number_format($s, 0, ',', '.') : '-' }}</td>
                @endforeach
                <td></td>
                <td></td>

                <td>{{ number_format($grandTotalHarga, 0, ',', '.') }}</td>
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
            </td>
            <td>
                <span class="sign-label">{{ now()->translatedFormat('d F Y') }}</span>

                <span
                    class="sign-line">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
            </td>
        </tr>
    </table>

    <div class="page-break"></div>


    @php
        $cv = $pos->first()?->cv;
        $cvNama = $cv?->nama_cv ?? '-';
        $cvAlamat = $cv?->alamat ?? '-';
        $cvNamaBank = $cv?->nama_bank ?? '-';
        $cvNoRek = $cv?->no_rekening ?? '-';
        $cvAtasNama = $cv?->atas_nama_rekening ?? $cvNama;
        $cvPimpinan = $cv?->nama_pimpinan ?? '';
        $cvPrefix = $cv?->no_dokumen_prefix ?? '';
        $noKwitansi = $noSurat ?? ($cvPrefix ? $cvPrefix . '/' . now()->format('III/Y') : now()->format('Y/m'));
        $logoInisial = strtoupper(substr(preg_replace('/^CV\.?\s*/i', '', $cvNama), 0, 2));
    @endphp

    <div class="kwitansi-wrap">

        <div class="kwit-outer">
            <table class="kwit-header-table">
                <tr>
                    <td style="padding:0; margin:0;">
                        {{-- <div class="kwit-logo-box">{{ $logoInisial }}</div> --}}
                        <div class="kwit-company-name">{{ $cvNama }}</div>
                        <div class="kwit-company-sub">{{ $cvAlamat }}</div>
                    </td>
                    <td style="text-align:left; font-size:8px; color:#555; vertical-align:top;">
                        No. {{ $noKwitansi }}
                    </td>
                </tr>
            </table>

            <div class="kwit-title-bar">K W I T A N S I</div>

            <table class="kwit-body-table">
                <tr>
                    <td class="kwit-label">Sudah Terima dari</td>
                    <td class="kwit-colon">:</td>
                    <td class="kwit-value" style="font-weight: bold;">PT. SURYA UNGGAS MANDIRI</td>
                </tr>
                <tr>
                    <td class="kwit-label">Untuk pembayaran</td>
                    <td class="kwit-colon">:</td>
                    <td class="kwit-value">
                        Pembayaran Angkutan Pakan dari Pabrik CPI Padang<br>
                        Periode :
                        @if ($from && $to)
                            {{ date('d', strtotime($from)) }} &ndash; {{ date('d M Y', strtotime($to)) }}
                        @elseif($from)
                            {{ date('d M Y', strtotime($from)) }}
                        @else
                            -
                        @endif
                        &nbsp;&bull;&nbsp; {{ number_format($grandTotalKg, 0, ',', '.') }} kg
                    </td>
                </tr>
                <tr>
                    <td class="kwit-label">Jumlah</td>
                    <td class="kwit-colon">:</td>
                    <td>

                        <div class="kwit-amount" style="padding:0; margin:0;">Rp &nbsp;
                            {{ number_format($grandTotalHarga, 0, ',', '.') }}</div>
                        <div class="kwit-terbilang" style="padding:0; margin:0;">
                            @php
                                try {
                                    $formatter = new NumberFormatter('id', NumberFormatter::SPELLOUT);
                                    $terbilang = ucwords($formatter->format($grandTotalHarga));
                                } catch (Exception $e) {
                                    $terbilang = '...';
                                }
                            @endphp
                            Terbilang : {{ $terbilang }} Rupiah,-
                        </div>
                    </td>
                </tr>
            </table>

            <table class="kwit-body-table">
                <tr>
                    <td class="kwit-label" style="">PEMBAYARAN :</td>
                </tr>
                <tr>
                    <td class="kwit-label" style="">{{ $cvNamaBank }}</td>
                </tr>
                <tr>
                    <td class="kwit-label" style="">AN. {{ $cvAtasNama }}</td>
                </tr>
                <tr>
                    <td class="kwit-label" style="">NO REK : {{ $cvNoRek }}</td>
                </tr>
            </table>

            <table class="kwit-sign-table">
                <tr>
                    <td style="width:60%;"></td>
                    <td>
                        <span class="kwit-sign-city">
                            Jambi,
                            @if ($to)
                                {{ date('d', strtotime($to)) }}
                            @endif
                            {{ now()->translatedFormat('F Y') }}
                        </span>
                        <span
                            class="kwit-sign-line">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                    </td>
                </tr>
            </table>

        </div>

    </div>

</body>

</html>
