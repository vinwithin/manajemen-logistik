<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>PO {{ $po->no_po }} - PT Sum</title>
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

        /* ===== KWITANSI ===== */
        .kwitansi-wrap {
            font-family: 'Times New Roman', Times, serif;
            width: 70%;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }

        .kwit-header-table {
            width: 90%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .kwit-header-table td {
            vertical-align: middle;
        }


        .kwit-company-name {
            font-size: 16px;
            font-weight: bold;
            text-align: left;
            color: rgb(123, 123, 239);
            letter-spacing: 1px;
        }

        .kwit-company-sub {
            font-size: 10px;
            margin-top: 0;
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
            font-size: 12px;
            white-space: nowrap;
            color: #000;
        }

        .kwit-body-table td.kwit-colon {
            width: 8px;
        }

        .kwit-body-table td.kwit-value {
            font-size: 12px;

        }

        .kwit-amount-box {
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

    @php
        $cv = $po->cv;
        $cvLogo = $cv?->logo;
        $cvLogoBase64 = null;
        if ($cvLogo && Storage::disk('public')->exists($cvLogo)) {
            $type = pathinfo($cvLogo, PATHINFO_EXTENSION);
            $data = Storage::disk('public')->get($cvLogo);
            $cvLogoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }
        $cvNama = $cv?->nama_cv ?? '-';
        $cvCode = $cv?->code ?? '-';
        $cvAlamat = $cv?->alamat ?? '-';
        $cvNamaBank = $cv?->nama_bank ?? '-';
        $cvNoRek = $cv?->no_rekening ?? '-';
        $cvAtasNama = $cv?->atas_nama_rekening ?? $cvNama;
        $cvPimpinan = $cv?->nama_pimpinan ?? '';
        $cvPrefix = $cv?->no_dokumen_prefix ?? '';
        $noKwitansi = $cvPrefix ? $cvPrefix . '/' . $po->tanggal_po->format('III/Y') : $po->no_po;
        $logoInisial = strtoupper(substr(preg_replace('/^CV\.?\s*/i', '', $cvNama), 0, 2));

        $logoWidth = 170;
        $logoHeight = 50;
        
        if ($cv) {
            $namaCvLower = strtolower(trim($cvCode));
            
           if (str_contains($namaCvLower, 'tr')) {
                $top = 20;
                $logoWidth = 200;
                $logoHeight = 70;
            } elseif (str_contains($namaCvLower, 'hrz')) {
                $top = 0;
                $logoWidth = 200;
                $logoHeight = 40;
            
            } elseif (str_contains($namaCvLower, 'htg')) {
                $top = 14;
                $logoWidth = 200;
                $logoHeight = 70;
            
            } elseif (str_contains($namaCvLower, 'hnn')) {
                $top = 14;
                $logoWidth = 200;
                $logoHeight = 70;
            }
            // Tambahkan kondisi lain untuk CV lainnya di sini
        }
    @endphp

    {{-- Header dengan Logo --}}
    <div style="position: relative; margin-bottom: 5px;">
        @if ($cvLogo && Storage::disk('public')->exists($cvLogo))
            <div style="position: absolute; left: -25px; top: -45px;">
                <img src="{{ $cvLogoBase64 }}"
                    style="width: {{ $logoWidth }}px; height: {{ $logoHeight }}px; object-fit: contain; display: block; margin: 0; padding: 0;"
                    alt="Logo CV">
            </div>
        @endif

        <div style="position: absolute; left: -52px; top: {{ $top }}px;">
            <!-- <div style="font-weight: bold; font-size: 10px; margin-top: 2px; margin-bottom: 0;">{{ $cvNama }}
            </div> -->
            <div style="font-size: 9px; padding-top: 0; margin-top: 0; color:rgb(123, 123, 239); font-style: italic;">
                {{ $cvAlamat }}</div>
        </div>
        <!-- <div style="position: absolute; right: -25px; top: -25px; font-size:9px; color:#555; font-weight:bold;">
            No. {{ $noKwitansi }}
        </div> -->

        <div class="doc-title">REKAPITULASI PENGIRIMAN PAKAN</div>
        <div class="doc-title">PT. SURYA UNGGAS MANDIRI</div>
        <div class="doc-title">UNIT JAMBI</div>
        <div class="doc-title">
            {{ $po->tanggal_po->translatedFormat('d F Y') }}
           
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
                <th style="width:42px;">Tanggal Bongkar</th>

                <th style="width:65px;">Tujuan Bongkar</th>

                <th style="width:42px;">Jumlah<br>(Kg)</th>
                <th style="width:30px;">Bag</th>
                <th style="width:30px;">Ongkos</th>
                <th style="width:45px;">Total<br>Ongkos </th>
            </tr>
        </thead>
        <tbody>
            @php
                $no = 1;
                $grandTotalKg = 0;
                $grandTotalKarung = 0;
                $grandTotalHarga = 0;
                $rowIdx = 0;
            @endphp

            @foreach ($po->kendaraans->sortBy('no_polisi') as $kendaraan)
                @php
                    $kendaraanPakanCount = 0;
                    foreach ($kendaraan->penerimas as $p) {
                        $kendaraanPakanCount += $p->pakans->count();
                    }
                    $isFirstKendaraanPakan = true;
                @endphp
                @foreach ($kendaraan->penerimas as $penerima)
                    @foreach ($penerima->pakans as $pakan)
                        @php
                            $totalKgPakan = (float) $pakan->jumlah_kg;
                            $totalKarungPakan = (int) ($pakan->jumlah_karung ?? 0);
                            $totalHargaPakan = (float) $pakan->jumlah_kg * (float) ($pakan->harga_pt_sum ?? 0);
                            $hargaPtSum = $pakan->harga_pt_sum ?? 0;
                            $kodePakanStr = $pakan->kodePakan?->kode ?? '—';
                            $rowClass = $rowIdx % 2 === 0 ? 'row-even' : 'row-odd';
                            $rowIdx++;
                        @endphp

                        <tr class="{{ $rowClass }} isi">
                            <td class="td-no" style="vertical-align:middle;">
                                {{  $no++ }}
                            </td>
                            <td style="vertical-align:middle;">
                                {{ $po->tanggal_po->translatedFormat('d F Y') }}
                            </td>
                            <td class="td-center" style="vertical-align:middle;">
                                {{ $kodePakanStr }}
                            </td>
                            <td style="vertical-align:middle;">
                                {{ $penerima->no_do }}
                            </td>
                            <td style="vertical-align:middle;">
                                {{  $kendaraan->no_polisi }}
                            </td>
                            <td style="vertical-align:middle;">
                                {{ $penerima->tiba_at?->translatedFormat('d F Y') ?? '-' }}

                            </td>
                           
                            <td class="td-center">
                                @if ($penerima->tujuan?->type == 'direct')
                                    {{ Str::upper($penerima->nama_penerima ?? '-') }}
                                @else
                                    {{ Str::upper($penerima->tujuan?->nama ?? '-') }}
                                @endif
                            </td>
                            <td class="td-kg-val">
                                {{ $totalKgPakan > 0 ? number_format($totalKgPakan, 0, ',', '.') : '' }}
                            </td>
                            <td class="td-karung-val">
                                {{ $totalKarungPakan > 0 ? number_format($totalKarungPakan, 0, ',', '.') : '' }}
                            </td>
                            <td>{{ $hargaPtSum > 0 ? number_format($hargaPtSum, 0, ',', '.') : '-' }}</td>
                            <td class="td-harga">
                                {{ $totalHargaPakan > 0 ? number_format($totalHargaPakan, 0, ',', '.') : '-' }}
                            </td>
                            @php $isFirstKendaraanPakan = false; @endphp
                        </tr>

                        @php
                            $grandTotalKg += $totalKgPakan;
                            $grandTotalKarung += $totalKarungPakan;
                            $grandTotalHarga += $totalHargaPakan;
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
            <td>
                <span class="sign-label">Jambi, {{ now()->translatedFormat('d F Y') }}</span>

                <span
                    class="sign-line">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
            </td>
        </tr>
    </table>

    <div class="page-break"></div>



    <div class="kwitansi-wrap">

        <div class="kwit-outer">
            <table class="kwit-header-table">
                <tr>
                    <td
                        style="padding:0; margin:0; vertical-align:top; text-align:left; white-space: nowrap; width: 100%;">
                        <div class="kwit-company-name" style="margin-top: 2px; margin-left:0;">{{ $cvNama }}
                        </div>
                        <div class="kwit-company-sub" style="margin-left:0;">{{ $cvAlamat }}</div>
                    </td>
                    <!-- <td
                        style="text-align:right; font-size:8px; color:#555; vertical-align:top; white-space: nowrap; font-weight:bold;">
                        No :&nbsp;&nbsp; {{ $noKwitansi }}
                    </td> -->
                </tr>
            </table>

            <div class="kwit-title-bar">KWITANSI</div>

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
                        No. PO : {{ $po->no_po }} &mdash; {{ $po->tanggal_po->translatedFormat('d F Y') }}
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

            <table style="width: 100%; border-collapse: collapse; margin-top: 40px; margin-bottom: 20px">
                <tr>
                    <td style="vertical-align: top; width: 50%; margin-top: 20px">
                        <div style="font-weight: bold; font-size: 12px; line-height: 2; margin: 0;">PEMBAYARAN :</div>
                        <div style="font-size: 12px; line-height: 2; margin: 0;">{{ $cvNamaBank }}</div>
                        <div style="font-size: 12px; line-height: 2; margin: 0;">AN. {{ $cvAtasNama }}</div>
                        <div style="font-size: 12px; line-height: 2; margin: 0;">NO REK : {{ $cvNoRek }}</div>
                    </td>
                    <td style="vertical-align: top; text-align: left; width: 50%;  ">
                        <span style="display: block; margin-bottom: 45px; font-size: 12px; margin-top: 0;">
                            Jambi, {{ $po->tanggal_po->translatedFormat('d F Y') }}
                        </span>
                        <span class="kwit-sign-line"></span>
                        <div style="font-weight: bold; font-size: 12px; margin-top: 4px;">{{ $cvPimpinan }}</div>
                    </td>
                </tr>
            </table>

        </div>

    </div>

</body>

</html>
