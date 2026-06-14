<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Lansir Periode</title>
    <style>
        @page { margin: 22px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111827; }
        h2 { margin: 0 0 4px; text-align: center; font-size: 16px; }
        .period { margin-bottom: 14px; text-align: center; color: #4b5563; }
        h3 { margin: 14px 0 6px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th, td { border: 1px solid #9ca3af; padding: 4px; }
        th { background: #2563eb; color: #fff; text-align: center; }
        .number { text-align: right; white-space: nowrap; }
        .total { font-weight: bold; background: #d1fae5; }
        .empty { text-align: center; color: #6b7280; }
    </style>
</head>
<body>
    <h2>Rekap Lansir</h2>
    <div class="period">Periode {{ $fromLabel }} s.d. {{ $toLabel }}</div>

    @foreach ([
        ['title' => 'Mobil Lansir', 'rows' => $mobilRows, 'pelaksana' => 'Mobil Lansir', 'tarif' => 'Ongkos'],
        ['title' => 'Tim Bongkar', 'rows' => $timRows, 'pelaksana' => 'Nama Tim', 'tarif' => 'Upah'],
    ] as $section)
        <h3>{{ $section['title'] }}</h3>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>No. PO</th>
                    <th>Kendaraan PO</th>
                    <th>Penerima</th>
                    <th>{{ $section['pelaksana'] }}</th>
                    <th>Berat</th>
                    <th>Karung</th>
                    <th>{{ $section['tarif'] }}</th>
                    <th>Total</th>
                    <th>Status Bayar</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($section['rows'] as $row)
                    <tr>
                        <td>{{ $row['tanggal'] }}</td>
                        <td>{{ $row['no_po'] }}</td>
                        <td>{{ $row['kendaraan_po'] }}</td>
                        <td>{{ $row['penerima'] }}</td>
                        <td>{{ $row['pelaksana'] }}</td>
                        <td class="number">{{ number_format($row['berat'], 0, ',', '.') }}</td>
                        <td class="number">{{ number_format($row['karung'], 0, ',', '.') }}</td>
                        <td class="number">{{ number_format($row['tarif'], 0, ',', '.') }}</td>
                        <td class="number">{{ number_format($row['total'], 0, ',', '.') }}</td>
                        <td>{{ $row['status_bayar'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="empty">Tidak ada data pada periode ini.</td></tr>
                @endforelse
                <tr class="total">
                    <td colspan="8">Grand Total</td>
                    <td class="number">{{ number_format($section['rows']->sum('total'), 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    @endforeach
</body>
</html>
