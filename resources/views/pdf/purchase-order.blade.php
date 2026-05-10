<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>PO {{ $po->no_po }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 9px;
            line-height: 1.3;
        }

        .header-info {
            margin-bottom: 15px;
        }

        .header-info table {
            width: 100%;
            margin-bottom: 5px;
        }

        .header-info td {
            padding: 2px 5px;
        }

        .header-info td:first-child {
            font-weight: bold;
            width: 80px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table.data-table th {
            background-color: #000;
            color: #fff;
            font-weight: bold;
            padding: 6px 4px;
            text-align: center;
            border: 1px solid #000;
            font-size: 8px;
        }

        table.data-table td {
            padding: 4px 4px;
            border: 1px solid #000;
            text-align: center;
            font-size: 8px;
        }

        table.data-table td.text-left {
            text-align: left;
        }

        table.data-table tr.kg-row td {
            background-color: #f5f5f5;
            font-weight: normal;
        }

        table.data-table tr.karung-row td {
            background-color: #ffffff;
        }

        table.data-table tr.total-row td {
            background-color: #e0e0e0;
            color: #000;
            font-weight: bold;
        }

        .empty-cell {
            background-color: white !important;
        }

        .page-break {
            page-break-after: always;
        }

        h3 {
            text-align: center;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .signature-section {
            margin-top: 40px;
            width: 100%;
        }

        .signature-section table {
            width: 100%;
            border-collapse: collapse;
        }

        .signature-box {
            width: 45%;
            text-align: center;
            vertical-align: top;
            padding: 10px;
        }

        .signature-label {
            font-weight: bold;
            margin-bottom: 60px;
            display: block;
        }

        .signature-name {
            border-top: 1px solid #000;
            padding-top: 5px;
            display: inline-block;
            min-width: 150px;
        }
    </style>
</head>

<body>
    <h3>PURCHASE ORDER - {{ $po->no_po }}</h3>

    <div class="header-info">
        <table>
            <tr>
                <td>No. PO</td>
                <td>: {{ $po->no_po }}</td>
            </tr>
            <tr>
                <td>Tanggal</td>
                <td>: {{ $po->tanggal_po->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td>CV</td>
                <td>: {{ $po->cv?->nama_cv ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30px;">NO</th>
                <th style="width: 60px;">TANGGAL</th>
                <th style="width: 70px;">KENDARAAN</th>
                <th style="width: 60px;">No. DO</th>
                <th style="width: 100px;">PENERIMA</th>
                @foreach ($kodePakanList as $kp)
                    <th style="width: 40px;">{{ $kp->kode }}</th>
                @endforeach
                <th style="width: 45px;">Bag</th>
                <th style="width: 55px;">Ongkos</th>
            </tr>
        </thead>
        <tbody>
            @php
                $no = 1;
                $subtotalsKg = array_fill(0, count($kodePakanList), 0);
                $subtotalsKarung = array_fill(0, count($kodePakanList), 0);
                $grandTotalKg = 0;
                $grandTotalKarung = 0;
                $grandTotalOngkos = 0;
            @endphp

            @foreach ($po->kendaraans->sortBy('no_polisi') as $kendaraan)
                @foreach ($kendaraan->penerimas as $penerima)
                    @php
                        $totalKgPenerima = 0;
                        $totalKarungPenerima = 0;
                        $totalOngkosPenerima = 0;
                    @endphp

                    {{-- ROW 1: KG values --}}
                    <tr class="kg-row">
                        <td class="empty-cell"></td>
                        <td class="empty-cell"></td>
                        <td class="empty-cell"></td>
                        <td class="empty-cell"></td>
                        <td class="empty-cell"></td>
                        @foreach ($kodePakanList as $i => $kp)
                            @php
                                $pakan = $penerima->pakans->firstWhere('kode_pakan_id', $kp->id);
                                if ($pakan) {
                                    $subtotalsKg[$i] += $pakan->jumlah_kg;
                                    $totalKgPenerima += $pakan->jumlah_kg;
                                    $totalOngkosPenerima += $pakan->jumlah_kg * $pakan->ongkos_oa;
                                }
                            @endphp
                            <td>{{ $pakan ? number_format($pakan->jumlah_kg, 0, ',', '.') : '' }}</td>
                        @endforeach
                        <td><strong>{{ number_format($totalKgPenerima, 0, ',', '.') }}</strong></td>
                        <td><strong>{{ number_format($totalOngkosPenerima, 0, ',', '.') }}</strong></td>
                    </tr>

                    {{-- ROW 2: KARUNG values --}}
                    <tr class="karung-row">
                        <td>{{ $no++ }}</td>
                        <td>{{ $po->tanggal_po->format('d/m/Y') }}</td>
                        <td>{{ $kendaraan->no_polisi }}</td>
                        <td>{{ $kendaraan->no_surat_jalan ?? '-' }}</td>
                        <td class="text-left">{{ $penerima->nama_penerima }}</td>
                        @foreach ($kodePakanList as $i => $kp)
                            @php
                                $pakan = $penerima->pakans->firstWhere('kode_pakan_id', $kp->id);
                                if ($pakan) {
                                    $subtotalsKarung[$i] += $pakan->jumlah_karung;
                                    $totalKarungPenerima += $pakan->jumlah_karung;
                                }
                            @endphp
                            <td>{{ $pakan ? $pakan->jumlah_karung : '' }}</td>
                        @endforeach
                        <td><strong>{{ $totalKarungPenerima }}</strong></td>
                        <td></td>
                    </tr>

                    @php
                        $grandTotalKg += $totalKgPenerima;
                        $grandTotalKarung += $totalKarungPenerima;
                        $grandTotalOngkos += $totalOngkosPenerima;
                    @endphp
                @endforeach
            @endforeach

            {{-- TOTAL ROW 1: KG --}}
            <tr class="total-row">
                <td colspan="5">TOTAL</td>
                @foreach ($subtotalsKg as $s)
                    <td>{{ $s > 0 ? number_format($s, 0, ',', '.') : '' }}</td>
                @endforeach
                <td>{{ number_format($grandTotalKg, 0, ',', '.') }}</td>
                <td>{{ number_format($grandTotalOngkos, 0, ',', '.') }}</td>
            </tr>

            {{-- TOTAL ROW 2: KARUNG --}}
            <tr class="total-row">
                <td colspan="5"></td>
                @foreach ($subtotalsKarung as $s)
                    <td>{{ $s > 0 ? $s : '' }}</td>
                @endforeach
                <td>{{ $grandTotalKarung }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    {{-- Signature Section --}}
    <div class="signature-section">
        <table>
            <tr>
                <td class="signature-box">
                    <span class="signature-label">Dibuat Oleh,</span>
                    <br><br><br>
                    <span
                        class="signature-name">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</span>
                </td>
                <td style="width: 10%;"></td>
                <td class="signature-box">
                    <span class="signature-label">Disetujui Oleh,</span>
                    <br><br><br>
                    <span
                        class="signature-name">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</span>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
