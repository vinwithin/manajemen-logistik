<?php

namespace App\Exports;

use App\Models\GudangMutasiStok;
use App\Models\KodePakan;
use App\Models\Tujuan;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class StokKeluarExport implements FromArray, WithEvents, WithTitle
{
    protected ?Tujuan $gudang;
    protected ?string $dari;
    protected ?string $sampai;
    protected $mutasis;
    protected $kodePakanList;

    public function __construct(?int $tujuanId, ?string $dari, ?string $sampai)
    {
        $this->dari    = $dari;
        $this->sampai  = $sampai;
        $this->gudang  = $tujuanId ? Tujuan::find($tujuanId) : null;

        $query = GudangMutasiStok::with([
            'tujuan',
            'kodePakan',
            'gudangLansirPakan.penerima.kendaraan.lansirHeader.cv',
            'gudangLansirPakan.penerima.tujuan',
        ])
        ->where('tipe', 'keluar')
        ->orderBy('created_at');

        if ($tujuanId) $query->where('tujuan_id', $tujuanId);
        if ($dari)     $query->whereDate('created_at', '>=', $dari);
        if ($sampai)   $query->whereDate('created_at', '<=', $sampai);

        $this->mutasis = $query->get();

        $this->kodePakanList = KodePakan::orderBy('kode')->get();
    }

    public function array(): array
    {
        $rows = [];

        // ── Header info ───────────────────────────────────────────────────
        $rows[] = ['REKAP STOK KELUAR — PT. SURYA UNGGAS MANDIRI'];
        $rows[] = ['Gudang', ':', $this->gudang?->nama ?? 'Semua Gudang'];
        $rows[] = ['Periode', ':', ($this->dari ?? '-') . ' s/d ' . ($this->sampai ?? '-')];
        $rows[] = ['Tanggal Export', ':', now()->format('d/m/Y H:i')];
        $rows[] = [];

        // ── Header tabel ──────────────────────────────────────────────────
        $rows[] = [
            'No', 'Tanggal', 'No Lansir', 'CV', 'Gudang Asal',
            'No. Polisi', 'No. SJ', 'Penerima', 'Tujuan',
            'Kode Pakan', 'Nama Pakan', 'Jumlah (kg)', 'Jumlah (karung)',
            'Ongkos OA (Rp/kg)', 'Total OA (Rp)',
            'Harga PT Sum (Rp/kg)', 'Total PT Sum (Rp)',
        ];

        // ── Data ──────────────────────────────────────────────────────────
        $no = 1;
        $totalKg = 0;
        $totalOa = 0;
        $totalPtSum = 0;

        foreach ($this->mutasis as $m) {
            $lansirPakan  = $m->gudangLansirPakan;
            $penerima     = $lansirPakan?->penerima;
            $kendaraan    = $penerima?->kendaraan;
            $lansirHeader = $kendaraan?->lansirHeader;

            $jumlahKg     = (float) $m->jumlah_kg;
            $ongkosOa     = (float) ($lansirPakan?->ongkos_oa ?? 0);
            $hargaPtSum   = (float) ($lansirPakan?->harga_pt_sum ?? 0);
            $totalOaPakan = $jumlahKg * $ongkosOa;
            $totalPtSumPakan = $jumlahKg * $hargaPtSum;

            $totalKg     += $jumlahKg;
            $totalOa     += $totalOaPakan;
            $totalPtSum  += $totalPtSumPakan;

            $rows[] = [
                $no++,
                $m->created_at?->format('d/m/Y') ?? '-',
                $lansirHeader?->no_lansir ?? '-',
                $lansirHeader?->cv?->nama_cv ?? '-',
                $m->tujuan?->nama ?? '-',
                $kendaraan?->no_polisi ?? '-',
                $kendaraan?->no_hp ?? '-',
                $penerima?->nama_penerima ?? '-',
                $penerima?->tujuan?->nama ?? '-',
                $m->kodePakan?->kode ?? '-',
                $m->kodePakan?->nama ?? '-',
                $jumlahKg,
                (int) ($lansirPakan?->jumlah_karung ?? ceil($jumlahKg / 50)),
                $ongkosOa > 0 ? $ongkosOa : '',
                $totalOaPakan > 0 ? $totalOaPakan : '',
                $hargaPtSum > 0 ? $hargaPtSum : '',
                $totalPtSumPakan > 0 ? $totalPtSumPakan : '',
            ];
        }

        // ── Baris total ───────────────────────────────────────────────────
        $rows[] = [];
        $rows[] = [
            '', '', '', '', '', '', '', '', '', '', 'TOTAL',
            $totalKg, '', '', $totalOa, '', $totalPtSum,
        ];

        return $rows;
    }

    public function title(): string
    {
        return substr('Stok Keluar ' . ($this->gudang?->nama ?? 'Semua'), 0, 31);
    }

    public function registerEvents(): array
    {
        $rowCount = $this->mutasis->count();
        $dataStart = 7; // baris 1-5 info + 1 blank + 1 header
        $dataEnd   = $dataStart + $rowCount - 1;
        $totalRow  = $dataEnd + 2;
        $lastCol   = 'Q'; // 17 kolom

        return [
            AfterSheet::class => function (AfterSheet $event) use ($dataStart, $dataEnd, $totalRow, $lastCol, $rowCount) {
                $sheet = $event->sheet->getDelegate();

                // Judul
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->getStyle('A1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 14, 'name' => 'Arial'],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(26);

                // Info rows
                foreach ([2, 3, 4] as $r) {
                    $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setName('Arial')->setSize(10);
                }

                // Header tabel
                $hRow = 6;
                $sheet->getStyle("A{$hRow}:{$lastCol}{$hRow}")->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'name' => 'Arial', 'size' => 9],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2563EB']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                $sheet->getRowDimension($hRow)->setRowHeight(28);

                // Data rows
                if ($rowCount > 0) {
                    for ($r = $dataStart; $r <= $dataEnd; $r++) {
                        $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
                            'font'      => ['name' => 'Arial', 'size' => 9],
                            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $r % 2 === 0 ? 'FFF3F4F6' : 'FFFFFFFF']],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                        ]);
                        // Kolom teks rata kiri
                        foreach (['D', 'E', 'F', 'H', 'I', 'J', 'K'] as $col) {
                            $sheet->getStyle("{$col}{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        }
                        $sheet->getRowDimension($r)->setRowHeight(18);
                    }
                }

                // Total row
                $sheet->getStyle("A{$totalRow}:{$lastCol}{$totalRow}")->applyFromArray([
                    'font'      => ['bold' => true, 'name' => 'Arial', 'size' => 10],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE5E7EB']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                $sheet->mergeCells("A{$totalRow}:J{$totalRow}");
                $sheet->getRowDimension($totalRow)->setRowHeight(22);

                // Border outline tabel
                $sheet->getStyle("A{$hRow}:{$lastCol}{$totalRow}")
                    ->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);

                // Column widths
                $widths = [5, 12, 16, 18, 16, 14, 14, 20, 16, 10, 18, 12, 12, 14, 14, 16, 16];
                foreach ($widths as $i => $w) {
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
                    $sheet->getColumnDimension($col)->setWidth($w);
                }

                // Freeze header
                $sheet->freezePane("A{$dataStart}");
            },
        ];
    }
}
