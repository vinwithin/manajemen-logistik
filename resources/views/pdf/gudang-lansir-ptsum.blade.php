<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Lansir Gudang {{ $from ?? 'Semua' }} - {{ $to ?? 'Semua' }} - PT Sum</title>
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
        $cv = $headers->first()?->cv;
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
        $noKwitansi = $noSurat ?? ($cvPrefix ? $cvPrefix . '/' . now()->format('III/Y') : now()->format('Y/m'));
        $logoInisial = strtoupper(substr(preg_replace('/^CV\.?\s*/i', '', $cvNama), 0, 2));

        $top = 14;
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
        }
    @endphp

    {{-- Header dengan Logo --}}
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
        <tr>
            {{-- Kiri: Logo + Alamat --}}
            <td style="width: 28%; vertical-align: top; padding: 0;">
                @if ($cvLogo && Storage::disk('public')->exists($cvLogo))
                    <img src="{{ $cvLogoBase64 }}"
                        style="width: {{ $logoWidth }}px; height: {{ $logoHeight }}px; object-fit: contain; display: block; margin: 0 0 4px 0;"
                        alt="Logo CV">
                @endif
                <div
                    style="font-size: 12px; color: black; font-style: italic; line-height: 1.4; word-wrap: break-word; text-align:start; width:100%; margin:0;">
                    {{ $cvAlamat }}
                </div>
            </td>
            {{-- Tengah: Judul --}}
            <td style="width: 44%; vertical-align: middle; text-align: center; padding: 0;">
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
                            {{ $periodeFrom->day }} &ndash; {{ $periodeTo->day }}
                            {{ $periodeFrom->translatedFormat('F Y') }}
                        @else
                            {{ $periodeFrom->translatedFormat('j F Y') }} &ndash;
                            {{ $periodeTo->translatedFormat('j F Y') }}
                        @endif
                    @elseif ($from)
                        Dari {{ \Illuminate\Support\Carbon::parse($from)->locale('id')->translatedFormat('j F Y') }}
                    @elseif ($to)
                        Sampai {{ \Illuminate\Support\Carbon::parse($to)->locale('id')->translatedFormat('j F Y') }}
                    @else
                        Semua Periode
                    @endif
                </div>
            </td>
            {{-- Kanan: No Surat --}}
            <td style="width: 28%; vertical-align: top; text-align: center;">
                <div style="font-size: 10px !important; color: black;">No. {{ $noKwitansi }}</div>
            </td>
        </tr>
    </table>

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

            @foreach ($headers as $header)
                @foreach ($header->kendaraans as $kendaraan)
                    @php
                        $penerimaCount = $kendaraan->penerimas->count();
                        $isFirstPenerima = true;
                    @endphp
                    @foreach ($kendaraan->penerimas as $penerima)
                        @php
                            $totalKgPenerima = (float) $penerima->pakans->sum('jumlah_kg');
                            $totalKarungPenerima = (int) $penerima->pakans->sum(
                                fn($p) => (int) ($p->jumlah_karung ?? 0),
                            );
                            $totalHargaPenerima = (float) $penerima->pakans->sum(
                                fn($p) => (float) $p->jumlah_kg * (float) ($p->harga_pt_sum ?? 0),
                            );
                            $hargaPtSum = $totalKgPenerima > 0 ? $totalHargaPenerima / $totalKgPenerima : 0;
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
                            <td class="td-no" style="vertical-align:middle;">
                                {{ $no++ }}</td>
                            <td style="vertical-align:middle;">
                                {{ $header->tanggal_lansir->translatedFormat('d F Y') }}</td>
                            <td class="td-center" style="vertical-align:middle;">
                                {{ $kodePakanStr !== '' ? $kodePakanStr : '—' }}</td>
                            <td style="vertical-align:middle;">
                                {{ $penerima->no_surat_jalan ?? '-' }}</td>
                            <td style="vertical-align:middle;">
                                {{ $kendaraan->no_polisi }}</td>

                            <td class="td-center">{{ Str::upper($penerima->nama_penerima ?? '-') }}</td>
                            <td class="td-center">{{ Str::upper($penerima->tujuan?->nama ?? '-') }}</td>

                            <td class="td-kg-val">
                                {{ $totalKgPenerima > 0 ? number_format($totalKgPenerima, 0, ',', '.') : '' }}</td>
                            <td class="td-karung-val">
                                {{ $totalKarungPenerima > 0 ? number_format($totalKarungPenerima, 0, ',', '.') : '' }}
                            </td>

                            {{-- Harga PT SUM per kg (rata-rata tertimbang kg) --}}
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
                        style="padding:0; margin:0; vertical-align:top; text-align:left; width:70%;">
                        <div class="kwit-company-name" style="margin-top: 2px; margin-left:0;">{{ $cvNama }}
                        </div>
                        <div class="kwit-company-sub" style="margin-left: 0;">{{ $cvAlamat }}</div>
                    </td>
                    <td
                        style="text-align:right; font-size:8px; color:#555; vertical-align:top; white-space: nowrap; font-weight:bold; width:30%;">
                        No :&nbsp;&nbsp; {{ $noKwitansi }}
                    </td>
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
                        Pembayaran Angkutan Pakan dari Gudang ke {{ $tujuanNama }}<br>
                        Periode :
                        @if ($from && $to)
                            @php
                                $kwFrom = \Illuminate\Support\Carbon::parse($from)->locale('id');
                                $kwTo = \Illuminate\Support\Carbon::parse($to)->locale('id');
                            @endphp
                            @if ($kwFrom->isSameDay($kwTo))
                                {{ $kwFrom->translatedFormat('j F Y') }}
                            @elseif ($kwFrom->month === $kwTo->month && $kwFrom->year === $kwTo->year)
                                {{ $kwFrom->day }} &ndash; {{ $kwTo->day }}
                                {{ $kwFrom->translatedFormat('F Y') }}
                            @else
                                {{ $kwFrom->translatedFormat('j F Y') }} &ndash;
                                {{ $kwTo->translatedFormat('j F Y') }}
                            @endif
                        @elseif ($from)
                            {{ \Illuminate\Support\Carbon::parse($from)->locale('id')->translatedFormat('j F Y') }}
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

            <table style="width: 100%; border-collapse: collapse; margin-top: 40px; margin-bottom: 20px">
                <tr>
                    <td style="vertical-align: top; width: 50%; margin-top: 20px">
                        <div style="font-weight: bold; font-size: 12px; line-height: 2; margin: 0;">PEMBAYARAN :</div>
                        <div style="font-size: 12px; line-height: 2; margin: 0;">{{ $cvNamaBank }}</div>
                        <div style="font-size: 12px; line-height: 2; margin: 0;">AN. {{ $cvAtasNama }}</div>
                        <div style="font-size: 12px; line-height: 2; margin: 0;">NO REK : {{ $cvNoRek }}</div>
                    </td>
                    <td style="vertical-align: top; text-align: left; width: 50%;">
                        <span style="display: block; margin-bottom: 45px; font-size: 12px; margin-top: 0;">
                            Jambi,
                            @if ($to)
                                {{ date('d', strtotime($to)) }}
                            @endif
                            {{ now()->translatedFormat('F Y') }}
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
