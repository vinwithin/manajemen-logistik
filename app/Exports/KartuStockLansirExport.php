<?php

namespace App\Exports;

use App\Models\GudangLansirHeader;
use App\Models\KodePakan;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class KartuStockLansirExport implements FromArray, WithEvents, WithTitle
{
    protected GudangLansirHeader $header;

    protected array $kodePakanList;

    // Sub-kolom per kode pakan: Masuk, Keluar (PIR, CO.Farm, CH), Sisa
    // Sesuai format: Masuk | Keluar (3 sub) | Sisa = 5 kolom per kode pakan
    const SUB_COLS = 5; // Masuk, Keluar-PIR, Keluar-CO.Farm, Keluar-CH, Sisa

    public function __construct(GudangLansirHeader $header)
    {
        $this->header = $header->load([
            'gudang',
            'kendaraans.penerimas.pakans.kodePakan',
            'kendaraans.penerimas.tims',
            'creator',
        ]);

        // Ambil kode pakan unik dari lansir ini
        $this->kodePakanList = KodePakan::orderBy('kode')->get()->all();

    }

    public function array(): array
    {
        $rows = [];
        $kpCount = count($this->kodePakanList);

        // ── Row 1: Judul ──────────────────────────────────────────────
        $rows[] = ['KARTU STOCK PT. SURYA UNGGAS MANDIRI'];

        // ── Row 2: Expedisi & Tanggal ─────────────────────────────────
        $rows[] = [
            'Expedisi', ':', $this->header->gudang?->nama ?? '-',
            '', '', '', '', '', '',
            'Tanggal Harian :', $this->header->tanggal_lansir->format('d/m/y'),
        ];

        // ── Row 3: Lokasi ─────────────────────────────────────────────
        $rows[] = [
            'Lokasi', ':', $this->header->gudang?->nama ?? '-',
        ];

        // ── Row 4: Blank ──────────────────────────────────────────────
        $rows[] = [];

        // ── Row 5: Header level 1 ─────────────────────────────────────
        // Kolom tetap: Tgl Pkn Masuk Gudang | Plat Mobil | Tgl & No.DO CPI | No.SJ ke Kandang | Nama Peternak
        // Lalu per kode pakan: [KodePakan (ukuran)] → Masuk | Keluar (PIR, CO.Farm, CH) | Sisa
        $h1 = [
            'Tgl Pkn Masuk Gudang',
            'Plat Mobil',
            'Tgl & No.DO CPI',
            'No.SJ ke Kandang',
            'Nama Peternak',
        ];
        foreach ($this->kodePakanList as $kp) {
            $label = $kp->kode;
            if ($kp->berat_per_karung ?? null) {
                $label .= ' ('.$kp->berat_per_karung.')';
            }
            $h1[] = $label; // Masuk
            $h1[] = '';     // Keluar (merged)
            $h1[] = '';     // Keluar sub
            $h1[] = '';     // Keluar sub
            $h1[] = '';     // Sisa
        }
        $rows[] = $h1;

        // ── Row 6: Header level 2 ─────────────────────────────────────
        $h2 = ['', '', '', '', ''];
        foreach ($this->kodePakanList as $kp) {
            $h2[] = 'Masuk';
            $h2[] = 'Keluar';
            $h2[] = '';
            $h2[] = '';
            $h2[] = 'Sisa';
        }
        $rows[] = $h2;

        // ── Row 7: Header level 3 (sub-keluar) ───────────────────────
        $h3 = ['', '', '', '', ''];
        foreach ($this->kodePakanList as $kp) {
            $h3[] = '';       // Masuk (merged dari atas)
            $h3[] = 'PIR';
            $h3[] = 'CO.Farm';
            $h3[] = 'CH';
            $h3[] = '';       // Sisa (merged dari atas)
        }
        $rows[] = $h3;

        // ── Row 8: Sisa Stok awal ─────────────────────────────────────
        $sisaRow = ['', '', '', 'Sisa Stok :', ''];
        foreach ($this->kodePakanList as $kp) {
            $sisaRow[] = 0; // Sisa awal (bisa diisi manual)
            $sisaRow[] = '';
            $sisaRow[] = '';
            $sisaRow[] = '';
            $sisaRow[] = 0;
        }
        $rows[] = $sisaRow;

        // ── Data rows ─────────────────────────────────────────────────
        $allPenerimas = $this->header->kendaraans->flatMap(fn ($k) => $k->penerimas);

        foreach ($allPenerimas as $penerima) {
            $kendaraan = $penerima->kendaraan;

            // Cek apakah ada keterangan dari tim (digunakan sebagai catatan baris)
            $keterangan = $penerima->tims->pluck('keterangan')->filter()->implode(', ');

            $row = [
                $this->header->tanggal_lansir->format('d/m/y'),
                $kendaraan->no_polisi ?? '',
                $kendaraan->no_surat_jalan ?? '',  // Tgl & No.DO CPI
                '',                                 // No.SJ ke Kandang (dari keterangan pakan)
                $penerima->nama_penerima,
            ];

            // Kolom per kode pakan
            foreach ($this->kodePakanList as $kp) {
                $pakan = $penerima->pakans->firstWhere('kode_pakan_id', $kp->id);
                $jumlahKg = $pakan ? (float) $pakan->jumlah_kg : 0;

                $row[] = '';          // Masuk (kosong untuk lansir keluar)
                $row[] = $jumlahKg ?: ''; // Keluar PIR (default semua ke PIR)
                $row[] = '';          // Keluar CO.Farm
                $row[] = '';          // Keluar CH
                $row[] = '';          // Sisa (dihitung manual/formula)
            }

            $rows[] = $row;

            // Baris keterangan jika ada
            if ($keterangan) {
                $ketRow = ['', '', '', '', ''];
                foreach ($this->kodePakanList as $kp) {
                    $ketRow[] = '';
                    $ketRow[] = '';
                    $ketRow[] = '';
                    $ketRow[] = '';
                    $ketRow[] = '';
                }
                // Sisipkan keterangan di kolom pertama
                $ketRow[0] = $keterangan;
                $rows[] = $ketRow;
            }
        }

        // ── Baris total ───────────────────────────────────────────────
        $totalRow = ['', '', '', 'TOTAL', ''];
        foreach ($this->kodePakanList as $kp) {
            $totalKeluar = $allPenerimas->sum(function ($p) use ($kp) {
                $pakan = $p->pakans->firstWhere('kode_pakan_id', $kp->id);

                return $pakan ? (float) $pakan->jumlah_kg : 0;
            });
            $totalRow[] = '';
            $totalRow[] = $totalKeluar ?: '';
            $totalRow[] = '';
            $totalRow[] = '';
            $totalRow[] = '';
        }
        $rows[] = $totalRow;

        return $rows;
    }

    public function title(): string
    {
        return substr('KS '.$this->header->no_lansir, 0, 31);
    }

    public function registerEvents(): array
    {
        $header = $this->header;
        $kpCount = count($this->kodePakanList);
        $subCols = self::SUB_COLS; // 5 kolom per kode pakan

        return [
            AfterSheet::class => function (AfterSheet $event) use ($kpCount, $subCols) {
                $sheet = $event->sheet->getDelegate();

                // Baris layout:
                // 1 = Judul
                // 2 = Expedisi / Tanggal
                // 3 = Lokasi
                // 4 = Blank
                // 5 = Header level 1
                // 6 = Header level 2
                // 7 = Header level 3
                // 8 = Sisa Stok
                // 9+ = Data
                $titleRow = 1;
                $hRow1 = 5;
                $hRow2 = 6;
                $hRow3 = 7;
                $sisaRow = 8;
                $dataStart = 9;
                $totalRow = $sheet->getHighestRow();

                // Kolom tetap: A-E (1-5)
                // Per kode pakan: 5 kolom masing-masing
                $fixedCols = 5;
                $totalCols = $fixedCols + ($kpCount * $subCols);
                $lastCol = $this->col($totalCols);

                // ── Judul ─────────────────────────────────────────────
                $sheet->mergeCells("A{$titleRow}:{$lastCol}{$titleRow}");
                $sheet->getStyle("A{$titleRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'name' => 'Arial'],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension($titleRow)->setRowHeight(24);

                // ── Info rows (2-3) ───────────────────────────────────
                $sheet->getStyle('A2:K3')->getFont()->setName('Arial')->setSize(10);
                $sheet->getStyle('A2')->getFont()->setBold(true);
                $sheet->getStyle('A3')->getFont()->setBold(true);

                // ── Header level 1 (row 5): merge per kode pakan ─────
                // Kolom A-E: merge 3 baris (hRow1 sampai hRow3)
                foreach (range(1, $fixedCols) as $ci) {
                    $c = $this->col($ci);
                    $sheet->mergeCells("{$c}{$hRow1}:{$c}{$hRow3}");
                    $sheet->getStyle("{$c}{$hRow1}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER)
                        ->setWrapText(true);
                }

                // Per kode pakan: merge 5 kolom di row 5
                for ($i = 0; $i < $kpCount; $i++) {
                    $startC = $this->col($fixedCols + 1 + $i * $subCols);
                    $endC = $this->col($fixedCols + ($i + 1) * $subCols);
                    $sheet->mergeCells("{$startC}{$hRow1}:{$endC}{$hRow1}");
                    $sheet->getStyle("{$startC}{$hRow1}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER);

                    // Row 6: Masuk (merge 2 baris), Keluar (merge 3 kolom), Sisa (merge 2 baris)
                    $masukC = $this->col($fixedCols + 1 + $i * $subCols);
                    $keluar1 = $this->col($fixedCols + 2 + $i * $subCols);
                    $keluar3 = $this->col($fixedCols + 4 + $i * $subCols);
                    $sisaC = $this->col($fixedCols + 5 + $i * $subCols);

                    // Masuk: merge row 6-7
                    $sheet->mergeCells("{$masukC}{$hRow2}:{$masukC}{$hRow3}");
                    $sheet->getStyle("{$masukC}{$hRow2}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER);

                    // Keluar: merge 3 kolom di row 6
                    $sheet->mergeCells("{$keluar1}{$hRow2}:{$keluar3}{$hRow2}");
                    $sheet->getStyle("{$keluar1}{$hRow2}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER);

                    // Sisa: merge row 6-7
                    $sheet->mergeCells("{$sisaC}{$hRow2}:{$sisaC}{$hRow3}");
                    $sheet->getStyle("{$sisaC}{$hRow2}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER);
                }

                // ── Style header rows ─────────────────────────────────
                $headerBg = [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFFBBF24'], // Amber/kuning
                ];
                $headerStyle = [
                    'font' => ['bold' => true, 'name' => 'Arial', 'size' => 9],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFBBF24']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ];
                $sheet->getStyle("A{$hRow1}:{$lastCol}{$hRow3}")->applyFromArray($headerStyle);

                // ── Style Sisa Stok row ───────────────────────────────
                $sheet->getStyle("A{$sisaRow}:{$lastCol}{$sisaRow}")->applyFromArray([
                    'font' => ['bold' => true, 'name' => 'Arial', 'size' => 9],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFEF9C3']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);

                // ── Style data rows ───────────────────────────────────
                for ($r = $dataStart; $r <= $totalRow; $r++) {
                    $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
                        'font' => ['name' => 'Arial', 'size' => 9],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    ]);
                }

                // ── Style total row ───────────────────────────────────
                $sheet->getStyle("A{$totalRow}:{$lastCol}{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true, 'name' => 'Arial', 'size' => 9],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE5E7EB']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);

                // ── Highlight baris keterangan (merah muda) ───────────
                for ($r = $dataStart; $r <= $totalRow; $r++) {
                    $cellA = $sheet->getCell("A{$r}")->getValue();
                    // Jika kolom A berisi teks panjang (keterangan), highlight merah
                    if ($cellA && strlen((string) $cellA) > 10 && ! is_numeric($cellA)) {
                        $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFECACA']],
                            'font' => ['bold' => true, 'color' => ['argb' => 'FF991B1B']],
                        ]);
                    }
                }

                // ── Column widths ─────────────────────────────────────
                $sheet->getColumnDimension('A')->setWidth(14);
                $sheet->getColumnDimension('B')->setWidth(12);
                $sheet->getColumnDimension('C')->setWidth(14);
                $sheet->getColumnDimension('D')->setWidth(14);
                $sheet->getColumnDimension('E')->setWidth(20);

                for ($i = 0; $i < $kpCount; $i++) {
                    for ($j = 0; $j < $subCols; $j++) {
                        $c = $this->col($fixedCols + 1 + $i * $subCols + $j);
                        $sheet->getColumnDimension($c)->setWidth(8);
                    }
                }

                // ── Row heights ───────────────────────────────────────
                $sheet->getRowDimension($hRow1)->setRowHeight(30);
                $sheet->getRowDimension($hRow2)->setRowHeight(20);
                $sheet->getRowDimension($hRow3)->setRowHeight(20);
                $sheet->getRowDimension($sisaRow)->setRowHeight(20);
                for ($r = $dataStart; $r <= $totalRow; $r++) {
                    $sheet->getRowDimension($r)->setRowHeight(18);
                }

                // ── Freeze panes ──────────────────────────────────────
                $sheet->freezePane("F{$dataStart}");
            },
        ];
    }

    private function col(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)).$letter;
            $index = intdiv($index, 26);
        }

        return $letter;
    }
}
