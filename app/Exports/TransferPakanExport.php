<?php

namespace App\Exports;

use App\Models\KodePakan;
use App\Models\TransferPakanHeader;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TransferPakanExport implements FromArray, WithEvents, WithTitle
{
    protected Collection $headers;

    protected array $kodePakanList;

    protected ?string $from;

    protected ?string $to;

    protected ?int $cvId;

    // NO | TANGGAL | No Transfer | PENGIRIM | No. POLISI | No. SJ | PENERIMA | TUJUAN = 8 kolom
    protected int $identitasCols = 8;

    public function __construct(?string $from = null, ?string $to = null, ?int $cvId = null)
    {
        $this->from = $from;
        $this->to = $to;
        $this->cvId = $cvId;

        $query = TransferPakanHeader::with([
            'kendaraans.penerimas.pakans.kodePakan',
            'kendaraans.penerimas.tujuan',
            'kendaraans.penerimas.tims',
        ])->orderBy('tanggal_transfer')->orderBy('no_transfer');

        if ($from) {
            $query->whereDate('tanggal_transfer', '>=', $from);
        }
        if ($to) {
            $query->whereDate('tanggal_transfer', '<=', $to);
        }
        if ($cvId) {
            $query->where('cv_id', $cvId);
        }

        $this->headers = $query->get();

        $this->kodePakanList = KodePakan::orderBy('kode')->get()->all();
    }

    public function array(): array
    {
        $rows = [];
        $kpCount = count($this->kodePakanList);
        $idCols = $this->identitasCols;

        // ── Header baris 1 ──────────────────────────────────────────────
        $header1 = ['NO', 'TANGGAL', 'No Transfer', 'PENGIRIM', 'No. POLISI', 'No. SJ', 'PENERIMA', 'TUJUAN'];

        $header1[] = 'Jumlah (karung)';
        for ($i = 1; $i < $kpCount; $i++) {
            $header1[] = '';
        }

        $header1[] = 'Jumlah (kg)';
        for ($i = 1; $i < $kpCount; $i++) {
            $header1[] = '';
        }

        $header1[] = 'Ongkos OA';
        $header1[] = 'Total OA (Rp)';
        $header1[] = 'Harga PT Sum';
        $header1[] = 'Total PT Sum (Rp)';
        $header1[] = 'Keterangan';

        $header1[] = ''; // Spacer
        $header1[] = 'TIM BONGKAR';
        $header1[] = '';
        $header1[] = '';
        $header1[] = '';
        $header1[] = '';

        $rows[] = $header1;

        // ── Header baris 2 ──────────────────────────────────────────────
        $header2 = array_fill(0, $idCols, '');

        foreach ($this->kodePakanList as $kp) {
            $header2[] = $kp->kode;
        }
        foreach ($this->kodePakanList as $kp) {
            $header2[] = $kp->kode;
        }

        $header2[] = '';
        $header2[] = '';
        $header2[] = '';
        $header2[] = '';
        $header2[] = '';

        $header2[] = '';
        $header2[] = 'Nama Tim';
        $header2[] = 'Jumlah (kg)';
        $header2[] = 'Upah/kg';
        $header2[] = 'Total (Rp)';
        $header2[] = 'Keterangan';

        $rows[] = $header2;

        // ── Data rows ───────────────────────────────────────────────────
        $no = 1;
        foreach ($this->headers as $header) {
            foreach ($header->kendaraans as $kendaraan) {
                foreach ($kendaraan->penerimas as $penerima) {
                    $totalOa = 0;
                    $totalPtSum = 0;

                    $row = [
                        $no++,
                        $header->tanggal_transfer->format('d/m/Y'),
                        $header->no_transfer,
                        $header->nama_pengirim ?? '-',
                        $kendaraan->no_polisi,
                        $penerima->no_surat_jalan ?? '-',
                        $penerima->nama_penerima,
                        $penerima->tujuan?->nama ?? '-',
                    ];

                    // Karung per kode pakan
                    foreach ($this->kodePakanList as $kp) {
                        $pakan = $penerima->pakans->firstWhere('kode_pakan_id', $kp->id);
                        $row[] = ($pakan && $pakan->jumlah_karung) ? (int) $pakan->jumlah_karung : '';
                    }

                    // KG per kode pakan
                    foreach ($this->kodePakanList as $kp) {
                        $pakan = $penerima->pakans->firstWhere('kode_pakan_id', $kp->id);
                        if ($pakan && $pakan->jumlah_kg) {
                            $row[] = (float) $pakan->jumlah_kg;
                            $totalOa += (float) $pakan->jumlah_kg * (float) ($pakan->ongkos_oa ?? 0);
                            $totalPtSum += (float) $pakan->jumlah_kg * (float) ($pakan->harga_pt_sum ?? 0);
                        } else {
                            $row[] = '';
                        }
                    }

                    $oaPerKg = $penerima->pakans->whereNotNull('ongkos_oa')->where('ongkos_oa', '>', 0)->first()?->ongkos_oa;
                    $hargaPtSum = $penerima->pakans->where('harga_pt_sum', '>', 0)->first()?->harga_pt_sum;
                    $ket = $penerima->pakans->whereNotNull('keterangan')->first()?->keterangan ?? '';

                    $row[] = $oaPerKg ?: '';
                    $row[] = $totalOa > 0 ? $totalOa : '';
                    $row[] = $hargaPtSum ?: '';
                    $row[] = $totalPtSum > 0 ? $totalPtSum : '';
                    $row[] = $ket;

                    // Tim Bongkar baris pertama
                    $firstTim = $penerima->tims->first();
                    if ($firstTim) {
                        $totalUpah = (float) ($firstTim->jumlah_kg ?? 0) * (float) ($firstTim->upah_per_kg ?? 0);
                        $row[] = '';
                        $row[] = $firstTim->nama_tim;
                        $row[] = (float) $firstTim->jumlah_kg;
                        $row[] = $firstTim->upah_per_kg > 0 ? (float) $firstTim->upah_per_kg : '';
                        $row[] = $totalUpah > 0 ? $totalUpah : '';
                        $row[] = $firstTim->keterangan ?? '';
                    } else {
                        $row[] = '';
                        $row[] = '';
                        $row[] = '';
                        $row[] = '';
                        $row[] = '';
                        $row[] = '';
                    }

                    $rows[] = $row;

                    // Extra rows tim ke-2, ke-3, dst
                    if ($penerima->tims->count() > 1) {
                        foreach ($penerima->tims->skip(1) as $tim) {
                            $totalUpah = (float) ($tim->jumlah_kg ?? 0) * (float) ($tim->upah_per_kg ?? 0);
                            $extraRow = array_fill(0, $idCols + ($kpCount * 2) + 5, '');
                            $extraRow[] = '';
                            $extraRow[] = $tim->nama_tim;
                            $extraRow[] = (float) $tim->jumlah_kg;
                            $extraRow[] = $tim->upah_per_kg > 0 ? (float) $tim->upah_per_kg : '';
                            $extraRow[] = $totalUpah > 0 ? $totalUpah : '';
                            $extraRow[] = $tim->keterangan ?? '';
                            $rows[] = $extraRow;
                        }
                    }
                }
            }
        }

        // ── Baris TOTAL ─────────────────────────────────────────────────
        $totalRow = array_fill(0, $idCols, '');
        $totalRow[0] = 'TOTAL';
        for ($i = 0; $i < ($kpCount * 2) + 4; $i++) {
            $totalRow[] = '';
        }
        $totalRow[] = '';
        $totalRow[] = '';
        $totalRow[] = 'TOTAL';
        $totalRow[] = '';
        $totalRow[] = '';
        $totalRow[] = '';
        $totalRow[] = '';
        $rows[] = $totalRow;

        return $rows;
    }

    public function title(): string
    {
        $clean = fn ($s) => preg_replace('/[\/\\\?\*\[\]:]/', '-', $s);
        if ($this->from && $this->to) {
            return substr('TP '.$clean($this->from).' sd '.$clean($this->to), 0, 31);
        }

        return 'Transfer Pakan';
    }

    public function registerEvents(): array
    {
        $headers = $this->headers;
        $kpCount = count($this->kodePakanList);
        $from = $this->from;
        $to = $this->to;
        $idCols = $this->identitasCols;

        return [
            AfterSheet::class => function (AfterSheet $event) use ($headers, $kpCount, $from, $to, $idCols) {
                $sheet = $event->sheet->getDelegate();

                // Insert 3 baris info
                $sheet->insertNewRowBefore(1, 3);
                $sheet->setCellValue('A1', 'Rekap Transfer Pakan');
                $sheet->setCellValue('A2', 'Periode');
                if ($from && $to) {
                    $sheet->setCellValue('B2', date('d/m/Y', strtotime($from)).' - '.date('d/m/Y', strtotime($to)));
                } elseif ($from) {
                    $sheet->setCellValue('B2', 'Dari '.date('d/m/Y', strtotime($from)));
                } elseif ($to) {
                    $sheet->setCellValue('B2', 'Sampai '.date('d/m/Y', strtotime($to)));
                } else {
                    $sheet->setCellValue('B2', 'Semua Periode');
                }
                $sheet->setCellValue('A3', 'Tanggal Export');
                $sheet->setCellValue('B3', now()->format('d/m/Y H:i'));
                $sheet->getStyle('A1:A3')->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle('A1')->getFont()->setSize(13);

                $hRow1 = 4;
                $hRow2 = 5;
                $dataStart = 6;
                $totalRowNum = $sheet->getHighestRow();

                $karungStart = $idCols + 1;
                $karungEnd = $idCols + $kpCount;
                $kgStart = $idCols + $kpCount + 1;
                $kgEnd = $idCols + 2 * $kpCount;
                $oaCol = $idCols + 2 * $kpCount + 1;
                $totalOaCol = $idCols + 2 * $kpCount + 2;
                $hargaPtSumCol = $idCols + 2 * $kpCount + 3;
                $totalPtSumCol = $idCols + 2 * $kpCount + 4;
                $ketCol = $idCols + 2 * $kpCount + 5;
                $spacerCol = $idCols + 2 * $kpCount + 6;
                $timStart = $idCols + 2 * $kpCount + 7;
                $timEnd = $idCols + 2 * $kpCount + 11;
                $totalCols = $timEnd;

                $lastCol = $this->getColumnLetter($totalCols);
                $lastIdentitasCol = $this->getColumnLetter($idCols);
                $nextDataCol = $this->getColumnLetter($idCols + 1);
                $karungStartLtr = $this->getColumnLetter($karungStart);
                $karungEndLtr = $this->getColumnLetter($karungEnd);
                $kgStartLtr = $this->getColumnLetter($kgStart);
                $kgEndLtr = $this->getColumnLetter($kgEnd);
                $oaLtr = $this->getColumnLetter($oaCol);
                $totalOaLtr = $this->getColumnLetter($totalOaCol);
                $hargaPtSumLtr = $this->getColumnLetter($hargaPtSumCol);
                $totalPtSumLtr = $this->getColumnLetter($totalPtSumCol);
                $ketLtr = $this->getColumnLetter($ketCol);
                $spacerLtr = $this->getColumnLetter($spacerCol);
                $timStartLtr = $this->getColumnLetter($timStart);
                $timEndLtr = $this->getColumnLetter($timEnd);

                // Merge identitas (2 baris)
                for ($c = 1; $c <= $idCols; $c++) {
                    $cl = $this->getColumnLetter($c);
                    $sheet->mergeCells("{$cl}{$hRow1}:{$cl}{$hRow2}");
                    $sheet->getStyle("{$cl}{$hRow1}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER);
                }

                if ($kpCount > 1) {
                    $sheet->mergeCells("{$karungStartLtr}{$hRow1}:{$karungEndLtr}{$hRow1}");
                }
                $sheet->getStyle("{$karungStartLtr}{$hRow1}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                if ($kpCount > 1) {
                    $sheet->mergeCells("{$kgStartLtr}{$hRow1}:{$kgEndLtr}{$hRow1}");
                }
                $sheet->getStyle("{$kgStartLtr}{$hRow1}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                foreach ([$oaLtr, $totalOaLtr, $hargaPtSumLtr, $totalPtSumLtr, $ketLtr, $spacerLtr] as $cl) {
                    $sheet->mergeCells("{$cl}{$hRow1}:{$cl}{$hRow2}");
                    $sheet->getStyle("{$cl}{$hRow1}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                }

                $sheet->mergeCells("{$timStartLtr}{$hRow1}:{$timEndLtr}{$hRow1}");
                $sheet->getStyle("{$timStartLtr}{$hRow1}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                $headerStyle = [
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'name' => 'Arial', 'size' => 10],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2563EB']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ];
                $sheet->getStyle("A{$hRow1}:{$lastCol}{$hRow1}")->applyFromArray($headerStyle);
                $sheet->getStyle("A{$hRow2}:{$lastCol}{$hRow2}")->applyFromArray($headerStyle);

                // SUM di baris TOTAL
                for ($ci = $karungStart; $ci <= $totalPtSumCol; $ci++) {
                    $cl = $this->getColumnLetter($ci);
                    $sheet->setCellValue("{$cl}{$totalRowNum}", "=SUM({$cl}{$dataStart}:{$cl}".($totalRowNum - 1).')');
                }
                foreach ([$timStart + 1, $timStart + 3] as $ci) {
                    $cl = $this->getColumnLetter($ci);
                    $sheet->setCellValue("{$cl}{$totalRowNum}", "=SUM({$cl}{$dataStart}:{$cl}".($totalRowNum - 1).')');
                }

                // Style baris TOTAL
                $sheet->mergeCells("A{$totalRowNum}:{$lastIdentitasCol}{$totalRowNum}");
                $sheet->getStyle("A{$totalRowNum}:{$lastCol}{$totalRowNum}")->applyFromArray([
                    'font' => ['bold' => true, 'name' => 'Arial', 'size' => 10],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE5E7EB']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                // Style data rows
                $currentRow = $dataStart;
                $colorIndex = 0;
                $colors = ['FFF3F4F6', 'FFFFFFFF'];

                foreach ($headers as $header) {
                    foreach ($header->kendaraans as $kendaraan) {
                        $fill = $colors[$colorIndex % 2];
                        foreach ($kendaraan->penerimas as $penerima) {
                            $penerimaRows = max(1, $penerima->tims->count());
                            $start = $currentRow;
                            $end = $currentRow + $penerimaRows - 1;

                            if ($penerimaRows > 1) {
                                for ($c = 1; $c <= 5; $c++) {
                                    $cl = $this->getColumnLetter($c);
                                    $sheet->mergeCells("{$cl}{$start}:{$cl}{$end}");
                                    $sheet->getStyle("{$cl}{$start}")->getAlignment()
                                        ->setVertical(Alignment::VERTICAL_CENTER)
                                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                }
                            }

                            for ($r = $start; $r <= $end; $r++) {
                                $sheet->getStyle("A{$r}:H{$r}")->applyFromArray([
                                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF3F4F6']],
                                    'font' => ['name' => 'Arial', 'size' => 10],
                                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                                ]);
                                $sheet->getStyle("{$nextDataCol}{$r}:{$ketLtr}{$r}")->applyFromArray([
                                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $fill]],
                                    'font' => ['name' => 'Arial', 'size' => 10],
                                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                                ]);
                                $sheet->getStyle("{$timStartLtr}{$r}:{$timEndLtr}{$r}")->applyFromArray([
                                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD1FAE5']],
                                    'font' => ['name' => 'Arial', 'size' => 10],
                                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                                ]);
                                $sheet->getRowDimension($r)->setRowHeight(20);
                            }
                            $currentRow += $penerimaRows;
                        }
                        $colorIndex++;
                    }
                }

                $sheet->getStyle("A{$hRow1}:{$lastCol}{$totalRowNum}")
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                foreach (range('A', $lastCol) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                $sheet->getRowDimension($hRow1)->setRowHeight(24);
                $sheet->getRowDimension($hRow2)->setRowHeight(20);
                $sheet->getRowDimension($totalRowNum)->setRowHeight(22);
            },
        ];
    }

    private function getColumnLetter(int $index): string
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
