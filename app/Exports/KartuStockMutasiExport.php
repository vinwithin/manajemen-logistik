<?php

namespace App\Exports;

use App\Models\GudangMutasiStok;
use App\Models\KodePakan;
use App\Models\Tujuan;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class KartuStockMutasiExport implements FromArray, WithEvents, WithTitle
{
    protected ?Tujuan $gudang;

    protected ?string $dariTanggal;

    protected ?string $sampaiTanggal;

    protected $mutasis;

    protected $kodePakanList; // semua kode pakan dari tabel kode_pakans

    const FIXED_COLS = 6;

    const COLS_PER_PAKAN = 5;

    public function __construct(
        ?int $tujuanId,
        ?string $dariTanggal,
        ?string $sampaiTanggal,
        ?int $kodePakanId = null,
        ?string $tipe = null
    ) {
        $this->dariTanggal = $dariTanggal;
        $this->sampaiTanggal = $sampaiTanggal;
        $this->gudang = $tujuanId ? Tujuan::find($tujuanId) : null;

        $query = GudangMutasiStok::with([
            'tujuan',
            'kodePakan',
            'poPenerima.kendaraan.po',
            'gudangLansirPakan.penerima.kendaraan.lansirHeader',
        ])->orderBy('created_at', 'desc');

        if ($tujuanId) {
            $query->where('tujuan_id', $tujuanId);
        }
        if ($kodePakanId) {
            $query->where('kode_pakan_id', $kodePakanId);
        }
        if ($tipe) {
            $query->where('tipe', $tipe);
        }
        if ($dariTanggal) {
            $query->whereDate('created_at', '>=', $dariTanggal);
        }
        if ($sampaiTanggal) {
            $query->whereDate('created_at', '<=', $sampaiTanggal);
        }

        $this->mutasis = $query->get();

        // Jika filter kode pakan, hanya tampilkan kolom pakan yang dipilih
        if ($kodePakanId) {
            $this->kodePakanList = KodePakan::where('id', $kodePakanId)->get();
        } else {
            $this->kodePakanList = KodePakan::orderBy('kode')->get();
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function pakanColIndex(int $pakanIndex, int $sub): int
    {
        return self::FIXED_COLS + 1 + ($pakanIndex * self::COLS_PER_PAKAN) + $sub;
    }

    private function colLetter(int $colIndex): string
    {
        return Coordinate::stringFromColumnIndex($colIndex);
    }

    private function lastColIndex(): int
    {
        return self::FIXED_COLS + ($this->kodePakanList->count() * self::COLS_PER_PAKAN);
    }

    // ── array() ────────────────────────────────────────────────────────────

    public function array(): array
    {
        $pakanCount = $this->kodePakanList->count();
        $totalCols = self::FIXED_COLS + ($pakanCount * self::COLS_PER_PAKAN);

        $rows = [];

        // ── Row 1: Judul ─────────────────────────────────────────────────
        $row1 = array_fill(0, $totalCols, '');
        $row1[0] = 'KARTU STOCK PT. SURYA UNGGAS MANDIRI';
        $rows[] = $row1;

        // ── Row 2: Expedisi + Tanggal Harian ─────────────────────────────
        $row2 = array_fill(0, $totalCols, '');
        $row2[0] = 'Expedisi';
        $row2[1] = ':';
        $row2[2] = 'TR';
        $row2[$totalCols - 2] = 'Tanggal Harian :';
        $row2[$totalCols - 1] = $this->dariTanggal
            ? date('d/m/y', strtotime($this->dariTanggal))
            : date('d/m/y');
        $rows[] = $row2;

        // ── Row 3: Lokasi ─────────────────────────────────────────────────
        $row3 = array_fill(0, $totalCols, '');
        $row3[0] = 'Lokasi';
        $row3[1] = ':';
        $row3[2] = $this->gudang?->nama ?? '-';
        $rows[] = $row3;

        $rows[] = array_fill(0, $totalCols, '');

        $row5 = array_fill(0, $totalCols, '');
        foreach ($this->kodePakanList as $i => $kp) {
            $col = self::FIXED_COLS + ($i * self::COLS_PER_PAKAN); // 0-based
            $row5[$col] = $kp->kode.($kp->nama ? ' ('.$kp->nama.')' : '');
        }
        $rows[] = $row5;

        $row6 = [
            'Tgl Pkn Masuk Gudang',
            'Plat Mobil',
            'Tgl & No.DO CPI',
            'No.SJ ke Kandang',
            'Nama Peternak',
            '',
        ];
        foreach ($this->kodePakanList as $kp) {
            $row6[] = 'Masuk';
            $row6[] = 'PIR';
            $row6[] = 'CO.Farm';
            $row6[] = 'CH';
            $row6[] = 'Sisa';
        }
        $rows[] = $row6;

        $rowSaldo = array_fill(0, $totalCols, '');
        $rowSaldo[4] = 'Sisa Stok :';

        foreach ($this->kodePakanList as $i => $kp) {
            $sisaCol = self::FIXED_COLS + ($i * self::COLS_PER_PAKAN) + 4;

            $lastMutasi = $this->mutasis->where('kode_pakan_id', $kp->id)->last();
            $rowSaldo[$sisaCol] = $lastMutasi ? (float) $lastMutasi->saldo_kg_after : '';
        }
        $rows[] = $rowSaldo;

        foreach ($this->mutasis as $m) {
            $isMasuk = $m->tipe === 'masuk';

            // Tgl Pkn Masuk Gudang — hanya pakan MASUK
            $tglMasukGudang = $isMasuk
                ? ($m->created_at?->format('d/m/Y') ?? '-')
                : '';

            if ($isMasuk) {
                // Masuk: dari PO penerima
                $platMobil = $m->poPenerima?->kendaraan?->no_polisi ?? '-';
                $po = $m->poPenerima?->kendaraan?->po;
                $tglDoCpi = $po
                    ? (($po->tanggal_po?->format('d/m/Y') ?? '').' / '.($po->no_po ?? ''))
                    : '-';
                $noSj = '';
                $penerima = '';
            } else {
                // Keluar: dari lansir gudang — gunakan relasi baru
                $kendaraan = $m->gudangLansirPakan?->penerima?->kendaraan;
                $lansirHeader = $kendaraan?->lansirHeader;

                $platMobil = $kendaraan?->no_polisi ?? '-';
                $tglDoCpi = '-';
                $noSj = $kendaraan?->no_surat_jalan ?? '-';
                $penerima = $m->gudangLansirPakan?->penerima?->nama_penerima ?? '-';
            }

            $dataRow = [$tglMasukGudang, $platMobil, $tglDoCpi, $noSj, $penerima, ''];

            // Inisialisasi semua kolom pakan kosong
            foreach ($this->kodePakanList as $kp) {
                $dataRow[] = ''; // Masuk
                $dataRow[] = ''; // PIR
                $dataRow[] = ''; // CO.Farm
                $dataRow[] = ''; // CH
                $dataRow[] = ''; // Sisa
            }

            // Isi nilai kolom pakan
            $pakanIndex = $this->kodePakanList->search(
                fn ($kp) => $kp->id === $m->kode_pakan_id
            );
            if ($pakanIndex === false) {
                $rows[] = $dataRow;

                continue;
            }

            $base = self::FIXED_COLS + ($pakanIndex * self::COLS_PER_PAKAN); // 0-based

            if ($isMasuk) {
                $dataRow[$base] = (float) $m->jumlah_kg; // kolom Masuk
            } else {
                // Keluar: tentukan sub-kolom PIR / CO.Farm / CH
                $subKeluar = $this->resolveKeluarSubCol($m);
                $dataRow[$base + $subKeluar] = (float) $m->jumlah_kg;
            }

            $dataRow[$base + 4] = (float) $m->saldo_kg_after; // Sisa

            $rows[] = $dataRow;
        }

        // ── Baris total ───────────────────────────────────────────────────
        $rows[] = array_fill(0, $totalCols, '');

        $rowTotalMasuk = array_fill(0, $totalCols, '');
        $rowTotalKeluar = array_fill(0, $totalCols, '');
        $rowSelisih = array_fill(0, $totalCols, '');

        $rowTotalMasuk[4] = 'Total Masuk';
        $rowTotalKeluar[4] = 'Total Keluar';
        $rowSelisih[4] = 'Selisih';

        foreach ($this->kodePakanList as $i => $kp) {
            $base = self::FIXED_COLS + ($i * self::COLS_PER_PAKAN); // 0-based
            $totalMasuk = $this->mutasis->where('kode_pakan_id', $kp->id)->where('tipe', 'masuk')->sum('jumlah_kg');
            $totalKeluar = $this->mutasis->where('kode_pakan_id', $kp->id)->where('tipe', 'keluar')->sum('jumlah_kg');
            $rowTotalMasuk[$base] = (float) $totalMasuk;
            $rowTotalKeluar[$base] = (float) $totalKeluar;
            $rowSelisih[$base] = (float) ($totalMasuk - $totalKeluar);
        }

        $rows[] = $rowTotalMasuk;
        $rows[] = $rowTotalKeluar;
        $rows[] = $rowSelisih;

        return $rows;
    }

    private function resolveKeluarSubCol(GudangMutasiStok $m): int
    {
        $tipe = strtolower($m->poPenerima?->tujuan->type);
        if (str_contains($tipe, 'direct')) {
            return 1;
        }
        if (str_contains($tipe, 'co_farm')) {
            return 2;
        }
        if (str_contains($tipe, 'rent_farm')) {
            return 3;
        }

        return 1; // default PIR
    }

    // ── title() ────────────────────────────────────────────────────────────

    public function title(): string
    {
        $gudang = $this->gudang?->nama ?? 'Semua';

        return substr("KS {$gudang}", 0, 31);
    }

    // ── registerEvents() ───────────────────────────────────────────────────

    public function registerEvents(): array
    {
        $pakanCount = $this->kodePakanList->count();
        $totalCols = self::FIXED_COLS + ($pakanCount * self::COLS_PER_PAKAN);
        $lastColIdx = $totalCols;
        $lastColLtr = $this->colLetter($lastColIdx);

        $mutasisCount = $this->mutasis->count();

        $titleRow = 1;
        $headerRow1 = 5;
        $headerRow2 = 6;
        $saldoRow = 7;
        $dataStart = 8;
        $dataEnd = $dataStart + $mutasisCount - 1;
        $totalStart = $dataEnd + 2;
        $lastRow = $totalStart + 2;

        return [
            AfterSheet::class => function (AfterSheet $event) use (
                $lastColIdx, $lastColLtr,
                $titleRow, $headerRow1, $headerRow2, $saldoRow,
                $dataStart, $dataEnd, $totalStart, $lastRow, $mutasisCount
            ) {
                $sheet = $event->sheet->getDelegate();

                $thin = Border::BORDER_THIN;
                $medium = Border::BORDER_MEDIUM;

                $borderThin = ['allBorders' => ['borderStyle' => $thin]];

                // ── Row 1: Judul ─────────────────────────────────────────
                $sheet->mergeCells("A{$titleRow}:{$lastColLtr}{$titleRow}");
                $sheet->getStyle("A{$titleRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'name' => 'Arial', 'underline' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension($titleRow)->setRowHeight(28);

                // ── Row 2-3: Info ─────────────────────────────────────────
                foreach ([2, 3] as $r) {
                    $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setName('Arial')->setSize(10);
                    $sheet->getStyle("B{$r}:C{$r}")->getFont()->setName('Arial')->setSize(10);
                }
                $tgLabelCol = $this->colLetter($lastColIdx - 1);
                $tgValCol = $lastColLtr;

                $periodeLabel = 'Tanggal Harian :';
                $periodeValue = $this->dariTanggal
                    ? date('d/m/y', strtotime($this->dariTanggal))
                    : date('d/m/y');
                if ($this->sampaiTanggal && $this->sampaiTanggal !== $this->dariTanggal) {
                    $periodeValue .= ' s/d '.date('d/m/y', strtotime($this->sampaiTanggal));
                }

                $sheet->getCell("{$tgLabelCol}2")->setValue($periodeLabel);
                $sheet->getCell("{$tgValCol}2")->setValue($periodeValue);

                $sheet->getStyle("{$tgLabelCol}2")->applyFromArray([
                    'font' => ['bold' => true, 'name' => 'Arial', 'size' => 10],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                ]);
                $sheet->getStyle("{$tgValCol}2")->applyFromArray([
                    'font' => ['bold' => true, 'name' => 'Arial', 'size' => 12],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);

                // ── Row 5-6: Headers ──────────────────────────────────────
                $headerFill = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1D4ED8']];
                $headerFont = ['bold' => true, 'name' => 'Arial', 'size' => 9, 'color' => ['argb' => 'FFFFFFFF']];
                $centerWrap = ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true];

                $fixedCols = ['A', 'B', 'C', 'D', 'E', 'F'];
                $fixedLabels = [
                    'Tgl Pkn Masuk Gudang',
                    'Plat Mobil',
                    'Tgl & No.DO CPI',
                    'No.SJ ke Kandang',
                    'Nama Peternak',
                    '',
                ];
                foreach ($fixedCols as $idx => $col) {
                    $sheet->mergeCells("{$col}{$headerRow1}:{$col}{$headerRow2}");
                    $sheet->getCell("{$col}{$headerRow1}")->setValue($fixedLabels[$idx]);
                    $sheet->getStyle("{$col}{$headerRow1}")->applyFromArray([
                        'font' => $headerFont,
                        'fill' => $headerFill,
                        'alignment' => $centerWrap,
                        'borders' => $borderThin,
                    ]);
                }

                foreach ($this->kodePakanList as $i => $kp) {
                    $startIdx = self::FIXED_COLS + 1 + ($i * self::COLS_PER_PAKAN);
                    $endIdx = $startIdx + self::COLS_PER_PAKAN - 1;
                    $startLtr = $this->colLetter($startIdx);
                    $endLtr = $this->colLetter($endIdx);

                    $sheet->mergeCells("{$startLtr}{$headerRow1}:{$endLtr}{$headerRow1}");
                    $sheet->getStyle("{$startLtr}{$headerRow1}")->applyFromArray([
                        'font' => $headerFont,
                        'fill' => $headerFill,
                        'alignment' => $centerWrap,
                        'borders' => $borderThin,
                    ]);

                    $subLabels = ['Masuk', 'PIR', 'CO.Farm', 'CH', 'Sisa'];
                    for ($s = 0; $s < self::COLS_PER_PAKAN; $s++) {
                        $colLtr = $this->colLetter($startIdx + $s);
                        $sheet->getCell("{$colLtr}{$headerRow2}")->setValue($subLabels[$s]);
                        $sheet->getStyle("{$colLtr}{$headerRow2}")->applyFromArray([
                            'font' => $headerFont,
                            'fill' => $headerFill,
                            'alignment' => $centerWrap,
                            'borders' => $borderThin,
                        ]);
                    }
                }

                $sheet->getRowDimension($headerRow1)->setRowHeight(24);
                $sheet->getRowDimension($headerRow2)->setRowHeight(20);

                // ── Row 7: Sisa Stok ──────────────────────────────────────
                $sheet->mergeCells("A{$saldoRow}:D{$saldoRow}");
                $sheet->mergeCells("E{$saldoRow}:F{$saldoRow}");
                $sheet->getStyle("E{$saldoRow}")->applyFromArray([
                    'font' => ['bold' => true, 'name' => 'Arial', 'size' => 9],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFF00']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getStyle("A{$saldoRow}:{$lastColLtr}{$saldoRow}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFF00']],
                    'borders' => $borderThin,
                    'font' => ['name' => 'Arial', 'size' => 9, 'bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension($saldoRow)->setRowHeight(18);

                // ── Data rows ─────────────────────────────────────────────
                if ($mutasisCount > 0) {
                    for ($r = $dataStart; $r <= $dataEnd; $r++) {
                        $sheet->getStyle("A{$r}:{$lastColLtr}{$r}")->applyFromArray([
                            'font' => ['name' => 'Arial', 'size' => 9],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => ($r % 2 === 0 ? 'FFF3F4F6' : 'FFFFFFFF')]],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                            'borders' => $borderThin,
                        ]);
                        $sheet->getStyle("E{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        $sheet->getRowDimension($r)->setRowHeight(18);
                    }

                    $sheet->getStyle("A{$headerRow1}:{$lastColLtr}{$dataEnd}")
                        ->getBorders()->getOutline()->setBorderStyle($medium);
                }

                // ── Total rows ────────────────────────────────────────────
                for ($r = $totalStart; $r <= $lastRow; $r++) {
                    $sheet->getStyle("A{$r}:{$lastColLtr}{$r}")->applyFromArray([
                        'font' => ['bold' => true, 'name' => 'Arial', 'size' => 10],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFEF3C7']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                        'borders' => $borderThin,
                    ]);
                    $sheet->mergeCells("A{$r}:D{$r}");
                    $sheet->getStyle("E{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getRowDimension($r)->setRowHeight(20);
                }

                // ── Column widths ─────────────────────────────────────────
                $fixedWidths = [14, 14, 16, 12, 22, 4];
                foreach ($fixedWidths as $i => $w) {
                    $sheet->getColumnDimension($this->colLetter($i + 1))->setWidth($w);
                }
                foreach ($this->kodePakanList as $i => $kp) {
                    $base = self::FIXED_COLS + 1 + ($i * self::COLS_PER_PAKAN);
                    $subWidths = [10, 10, 10, 10, 10];
                    foreach ($subWidths as $s => $w) {
                        $sheet->getColumnDimension($this->colLetter($base + $s))->setWidth($w);
                    }
                }

                // ── Freeze ────────────────────────────────────────────────
                $sheet->freezePane("A{$dataStart}");
            },
        ];
    }
}
