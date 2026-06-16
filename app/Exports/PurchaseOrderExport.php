<?php

namespace App\Exports;

use App\Models\KodePakan;
use App\Models\PurchaseOrder;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PurchaseOrderExport implements FromArray, WithEvents, WithTitle
{
    protected PurchaseOrder $po;

    protected array $kodePakanList;

    /** No | Tanggal | No Polisi | No. DO | Tujuan | Penerima */
    protected int $identitasCols = 6;

    public function __construct(PurchaseOrder $po)
    {
        $this->po = $po->load([
            'cv',
            'kendaraans' => function ($q) {
                $q->where('status', '!=', 'batal');
            },
            'kendaraans.tujuan',
            'kendaraans.penerimas.tujuan',
            'kendaraans.penerimas.pakans.kodePakan',
            'kendaraans.penerimas.lansirs.mobils',
            'kendaraans.penerimas.lansirs.tims',
        ]);

        $this->kodePakanList = KodePakan::orderBy('kode')->get()->all();
    }

    public function array(): array
    {
        $rows = [];
        $kpCount = count($this->kodePakanList);
        $idCols = $this->identitasCols;

        // ── Header baris 1: label group ───────────────────────────────
        $header1 = ['No', 'Tanggal', 'No Polisi', 'No. DO', 'Tujuan', 'Penerima'];

        // Group Jumlah Karung
        $header1[] = 'Jumlah (bag)';
        for ($i = 1; $i < $kpCount; $i++) {
            $header1[] = '';
        }

        // Group KG
        $header1[] = 'Jumlah (kg)';
        for ($i = 1; $i < $kpCount; $i++) {
            $header1[] = '';
        }

        $header1[] = 'Ongkos Angkut';
        $header1[] = 'Jumlah(Rp)';
        $header1[] = 'CV';
        $header1[] = 'Keterangan';

        // ── LANSIR MOBIL COLUMNS ──────────────────────────────────────
        $header1[] = ''; // Spacer
        $header1[] = 'LANSIR MOBIL';
        $header1[] = '';
        $header1[] = '';
        $header1[] = '';
        $header1[] = '';
        $header1[] = '';

        // ── TIM BONGKAR COLUMNS ───────────────────────────────────────
        $header1[] = ''; // Spacer
        $header1[] = 'TIM BONGKAR';
        $header1[] = '';
        $header1[] = '';
        $header1[] = '';
        $header1[] = '';

        $rows[] = $header1;

        // ── Header baris 2: kode pakan + lansir detail ────────────────
        $header2 = array_fill(0, $idCols, '');

        foreach ($this->kodePakanList as $kp) {
            $header2[] = $kp->kode; // sub-header Jumlah Karung
        }
        foreach ($this->kodePakanList as $kp) {
            $header2[] = $kp->kode; // sub-header KG
        }

        $header2[] = ''; // Ongkos Angkut
        $header2[] = ''; // Jumlah (Rp)
        $header2[] = ''; // CV
        $header2[] = ''; // ket

        // ── LANSIR MOBIL SUB-HEADERS ──────────────────────────────────
        $header2[] = ''; // Spacer
        $header2[] = 'No Polisi';
        $header2[] = 'Sopir';
        $header2[] = 'Jumlah (kg)';
        $header2[] = 'Jumlah (bag)';
        $header2[] = 'Ongkos';
        $header2[] = 'Total (Rp)';

        // ── TIM BONGKAR SUB-HEADERS ───────────────────────────────────
        $header2[] = ''; // Spacer
        $header2[] = 'Nama Tim';
        $header2[] = 'Jumlah (kg)';
        $header2[] = 'Jumlah (bag)';
        $header2[] = 'Upah/kg';
        $header2[] = 'Total (Rp)';

        $rows[] = $header2;

        // ── Data: 1 baris per penerima (atau 1 baris muatan kendaraan jika belum ada penerima) ──
        $no = 1;
        foreach ($this->po->kendaraans->sortBy('no_polisi') as $kendaraan) {
            foreach (
                $kendaraan->penerimas->count() > 0
                    ? $kendaraan->penerimas
                    : [null] as $penerima
            ) {
                $lansir = null;

                $namaTujuan = $penerima !== null
                    ? ($penerima->tujuan?->nama ?? '')
                    : ($kendaraan->tujuan?->nama ?? '');

                $row = [
                    $no++,
                    $this->po->tanggal_po->translatedFormat('d F Y'),
                    $kendaraan->no_polisi,
                    $kendaraan->no_hp ?? '-',
                    $namaTujuan,
                    $penerima?->nama_penerima ?? '',
                ];

                if ($penerima !== null) {

                    // Kolom Jumlah Karung per kode pakan
                    foreach ($this->kodePakanList as $kp) {
                        $pakan = $penerima->pakans->firstWhere('kode_pakan_id', $kp->id);
                        $row[] = ($pakan && $pakan->jumlah_karung) ? $pakan->jumlah_karung : '';
                    }

                    // Kolom KG per kode pakan
                    $totalOngkos = 0;
                    foreach ($this->kodePakanList as $kp) {
                        $pakan = $penerima->pakans->firstWhere('kode_pakan_id', $kp->id);
                        if ($pakan && $pakan->jumlah_kg) {
                            $row[] = $pakan->jumlah_kg;
                            $totalOngkos += (float) $pakan->jumlah_kg * (float) ($pakan->ongkos_oa ?? 0);
                        } else {
                            $row[] = '';
                        }
                    }

                    // Ongkos Angkut
                    $oaAngkut = $penerima->pakans
                        ->whereNotNull('ongkos_oa')
                        ->first()?->ongkos_oa;
                    $row[] = $oaAngkut ?: '';

                    // Jumlah (Rp)
                    $row[] = $totalOngkos > 0 ? $totalOngkos : '';

                    // CV
                    $row[] = $this->po->cv?->nama_cv ?? '';

                    $lansir = $penerima->lansirs->first();

                    // Keterangan: tipe tujuan penerima (kosong jika tidak ada)
                    $row[] = $penerima->tujuan?->type ?? '';

                    // ── LANSIR MOBIL DATA ─────────────────────────────────
                    if ($lansir && $lansir->mobils->count() > 0) {
                        $firstMobil = $lansir->mobils->first();
                        $row[] = ''; // Spacer
                        $row[] = $firstMobil->no_polisi ?? '';
                        $row[] = $firstMobil->nama_sopir ?? '';
                        $row[] = $firstMobil->berat ?? '';
                        $row[] = $firstMobil->jumlah_karung ?? '';
                        $row[] = $firstMobil->ongkos ?? '';
                        $row[] = (float) ($firstMobil->berat ?? 0) * (float) ($firstMobil->ongkos ?? 0);
                    } else {
                        $row[] = ''; // Spacer
                        $row[] = '';
                        $row[] = '';
                        $row[] = '';
                        $row[] = '';
                        $row[] = '';
                        $row[] = '';
                    }

                    // ── TIM BONGKAR DATA ──────────────────────────────────
                    if ($lansir && $lansir->tims->count() > 0) {
                        $firstTim = $lansir->tims->first();
                        $totalBerat = $lansir->mobils->sum('berat');
                        $row[] = ''; // Spacer
                        $row[] = $firstTim->nama_tim ?? '';
                        $row[] = $firstTim->berat ?? $totalBerat;
                        $row[] = $firstTim->jumlah_karung ?? '';
                        $row[] = $firstTim->upah ?? '';
                        $row[] = (float) ($firstTim->berat ?? $totalBerat) * (float) ($firstTim->upah ?? 0);
                    } else {
                        $row[] = ''; // Spacer
                        $row[] = '';
                        $row[] = '';
                        $row[] = '';
                        $row[] = '';
                        $row[] = '';
                    }
                } else {
                    // Kendaraan belum punya penerima: muatan & OA level kendaraan
                    $kgMuatan = (float) ($kendaraan->jumlah_kg ?? 0);
                    $karungMuatan = $kendaraan->jumlah_karung;
                    $ongkosAngkut = $kendaraan->ongkos_angkut;

                    foreach ($this->kodePakanList as $idx => $_kp) {
                        if ($idx === 0 && $karungMuatan !== null && $karungMuatan !== '') {
                            $row[] = $karungMuatan;
                        } elseif ($idx === 0 && $karungMuatan === 0) {
                            $row[] = 0;
                        } else {
                            $row[] = '';
                        }
                    }

                    $totalOaKendaraan = 0.0;
                    foreach ($this->kodePakanList as $idx => $_kp) {
                        if ($idx === 0 && $kgMuatan > 0) {
                            $row[] = $kgMuatan;
                            $totalOaKendaraan = $kgMuatan * (float) ($ongkosAngkut ?? 0);
                        } else {
                            $row[] = '';
                        }
                    }

                    $row[] = ($ongkosAngkut !== null && $ongkosAngkut !== '') ? $ongkosAngkut : '';
                    $row[] = $totalOaKendaraan > 0 ? $totalOaKendaraan : '';
                    
                    // CV
                    $row[] = $this->po->cv?->nama_cv ?? '';
                    
                    $row[] = 'Belum ada penerima';

                    $row[] = '';
                    $row[] = '';
                    $row[] = '';
                    $row[] = '';
                    $row[] = '';
                    $row[] = '';
                    $row[] = '';

                    $row[] = '';
                    $row[] = '';
                    $row[] = '';
                    $row[] = '';
                    $row[] = '';
                    $row[] = '';
                }

                $rows[] = $row;

                // Tambahkan baris extra untuk mobil/tim tambahan (zip bersama)
                if ($penerima !== null && $lansir) {
                    $extraMobils = $lansir->mobils->slice(1)->values();
                    $extraTims = $lansir->tims->slice(1)->values();
                    $extraCount = max($extraMobils->count(), $extraTims->count());

                    for ($ei = 0; $ei < $extraCount; $ei++) {
                        $extraRow = array_fill(0, $idCols + ($kpCount * 2) + 4, '');

                        // Mobil lansir extra
                        $mobil = $extraMobils->get($ei);
                        $extraRow[] = ''; // Spacer
                        $extraRow[] = $mobil ? ($mobil->no_polisi ?? '') : '';
                        $extraRow[] = $mobil ? ($mobil->nama_sopir ?? '') : '';
                        $extraRow[] = $mobil ? ($mobil->berat ?? '') : '';
                        $extraRow[] = $mobil ? ($mobil->jumlah_karung ?? '') : '';
                        $extraRow[] = $mobil ? ($mobil->ongkos ?? '') : '';
                        $extraRow[] = $mobil ? (float) ($mobil->berat ?? 0) * (float) ($mobil->ongkos ?? 0) : '';

                        // Tim bongkar extra
                        $tim = $extraTims->get($ei);
                        $extraRow[] = ''; // Spacer
                        $extraRow[] = $tim ? ($tim->nama_tim ?? '') : '';
                        $extraRow[] = $tim ? ($tim->berat ?? '') : '';
                        $extraRow[] = $tim ? ($tim->jumlah_karung ?? '') : '';
                        $extraRow[] = $tim ? ($tim->upah ?? '') : '';
                        $extraRow[] = $tim ? (float) ($tim->berat ?? 0) * (float) ($tim->upah ?? 0) : '';

                        $rows[] = $extraRow;
                    }
                }
            }
        }

        // ── Baris TOTAL ───────────────────────────────────────────────
        $totalRow = array_fill(0, $idCols, '');
        $totalRow[0] = 'Total';
        // Empty cells for karung columns
        for ($i = 0; $i < $kpCount; $i++) {
            $totalRow[] = '';
        }
        // Empty cells for kg columns
        for ($i = 0; $i < $kpCount; $i++) {
            $totalRow[] = '';
        }
        $totalRow[] = ''; // Ongkos Angkut
        $totalRow[] = ''; // Jumlah (Rp)
        $totalRow[] = ''; // CV
        $totalRow[] = ''; // Keterangan

        // Lansir Mobil totals
        $totalRow[] = ''; // Spacer
        $totalRow[] = 'TOTAL';
        $totalRow[] = '';
        $totalRow[] = ''; // Will be formula - Jumlah (kg)
        $totalRow[] = ''; // Will be formula - Jumlah (bag)
        $totalRow[] = ''; // Will be formula - Ongkos
        $totalRow[] = ''; // Will be formula - Total

        // Tim Bongkar totals
        $totalRow[] = ''; // Spacer
        $totalRow[] = 'TOTAL';
        $totalRow[] = ''; // Will be formula - Jumlah (kg)
        $totalRow[] = ''; // Will be formula - Upah/kg
        $totalRow[] = ''; // Will be formula - Total
        $totalRow[] = ''; // Will be formula - Total

        $rows[] = $totalRow;

        return $rows;
    }

    public function title(): string
    {
        $clean = preg_replace('/[\/\\\?\*\[\]:]/', '-', $this->po->no_po);

        return substr('PO ' . $clean, 0, 31);
    }

    public function registerEvents(): array
    {
        $po = $this->po;
        $kodePakanCount = count($this->kodePakanList);

        return [
            AfterSheet::class => function (AfterSheet $event) use ($po, $kodePakanCount) {
                $sheet = $event->sheet->getDelegate();
                $idCols = $this->identitasCols;

                // ── Insert 3 baris info di atas ──────────────────────
                $sheet->insertNewRowBefore(1, 3);

                $sheet->setCellValue('A1', 'No. PO');
                $sheet->setCellValue('B1', $po->no_po);
                $sheet->setCellValue('A2', 'Tanggal');
                $sheet->setCellValue('B2', $po->tanggal_po->format('d/m/Y'));
                $sheet->setCellValue('A3', 'CV');
                $sheet->setCellValue('B3', $po->cv?->nama_cv ?? '-');
                $sheet->getStyle('A1:A3')->getFont()->setBold(true);

                // ── Posisi baris ──────────────────────────────────────
                $hRow1 = 4;  // header group (Jumlah Karung | KG | ...)
                $hRow2 = 5;  // header kode pakan (S11 | S12 | ...)
                $dataStartRow = 6;
                $totalRowNum = $sheet->getHighestRow();

                // ── Posisi kolom (1-based) ───────────────────────────
                // 1..idCols = identitas | lalu karung, kg, OA, jumlah, cv, ket, lansir, tim
                $karungStartCol = $idCols + 1;
                $karungEndCol = $idCols + $kodePakanCount;
                $kgStartCol = $idCols + $kodePakanCount + 1;
                $kgEndCol = $idCols + 2 * $kodePakanCount;
                $oaCol = $idCols + 2 * $kodePakanCount + 1;
                $jumlahCol = $idCols + 2 * $kodePakanCount + 2;
                $cvCol = $idCols + 2 * $kodePakanCount + 3;
                $ketCol = $idCols + 2 * $kodePakanCount + 4;
                $spacer1Col = $idCols + 2 * $kodePakanCount + 5;
                $lansirStartCol = $idCols + 2 * $kodePakanCount + 6;
                $lansirEndCol = $idCols + 2 * $kodePakanCount + 11;
                $spacer2Col = $idCols + 2 * $kodePakanCount + 12;
                $timStartCol = $idCols + 2 * $kodePakanCount + 13;
                $timEndCol = $idCols + 2 * $kodePakanCount + 17;
                $totalCols = $timEndCol;

                $lastCol = $this->getColumnLetter($totalCols);
                $lastIdentitasCol = $this->getColumnLetter($idCols);
                $tujuanColLetter = $this->getColumnLetter($idCols - 1);
                $penerimaColLetter = $this->getColumnLetter($idCols);
                $karungStartLetter = $this->getColumnLetter($karungStartCol);
                $karungEndLetter = $this->getColumnLetter($karungEndCol);
                $kgStartLetter = $this->getColumnLetter($kgStartCol);
                $kgEndLetter = $this->getColumnLetter($kgEndCol);
                $oaLetter = $this->getColumnLetter($oaCol);
                $jumlahLetter = $this->getColumnLetter($jumlahCol);
                $cvLetter = $this->getColumnLetter($cvCol);
                $ketLetter = $this->getColumnLetter($ketCol);
                $spacer1Letter = $this->getColumnLetter($spacer1Col);
                $lansirStartLetter = $this->getColumnLetter($lansirStartCol);
                $lansirEndLetter = $this->getColumnLetter($lansirEndCol);
                $spacer2Letter = $this->getColumnLetter($spacer2Col);
                $timStartLetter = $this->getColumnLetter($timStartCol);
                $timEndLetter = $this->getColumnLetter($timEndCol);

                // ── Merge header level 1 ──────────────────────────────

                // Kolom identitas: merge 2 baris
                foreach (range('A', $lastIdentitasCol) as $col) {
                    $sheet->mergeCells("{$col}{$hRow1}:{$col}{$hRow2}");
                    $sheet->getStyle("{$col}{$hRow1}:{$col}{$hRow2}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER);
                }

                // Group "Jumlah Karung"
                if ($kodePakanCount > 1) {
                    $sheet->mergeCells("{$karungStartLetter}{$hRow1}:{$karungEndLetter}{$hRow1}");
                }
                $sheet->getStyle("{$karungStartLetter}{$hRow1}:{$karungEndLetter}{$hRow1}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // Group "KG"
                if ($kodePakanCount > 1) {
                    $sheet->mergeCells("{$kgStartLetter}{$hRow1}:{$kgEndLetter}{$hRow1}");
                }
                $sheet->getStyle("{$kgStartLetter}{$hRow1}:{$kgEndLetter}{$hRow1}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // "Ongkos Angkut": merge 2 baris
                $sheet->mergeCells("{$oaLetter}{$hRow1}:{$oaLetter}{$hRow2}");
                $sheet->getStyle("{$oaLetter}{$hRow1}:{$oaLetter}{$hRow2}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // "Jumlah (Rp)": merge 2 baris
                $sheet->mergeCells("{$jumlahLetter}{$hRow1}:{$jumlahLetter}{$hRow2}");
                $sheet->getStyle("{$jumlahLetter}{$hRow1}:{$jumlahLetter}{$hRow2}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // "CV": merge 2 baris
                $sheet->mergeCells("{$cvLetter}{$hRow1}:{$cvLetter}{$hRow2}");
                $sheet->getStyle("{$cvLetter}{$hRow1}:{$cvLetter}{$hRow2}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // "Keterangan": merge 2 baris
                $sheet->mergeCells("{$ketLetter}{$hRow1}:{$ketLetter}{$hRow2}");
                $sheet->getStyle("{$ketLetter}{$hRow1}:{$ketLetter}{$hRow2}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // Spacer 1 column: merge 2 baris
                $sheet->mergeCells("{$spacer1Letter}{$hRow1}:{$spacer1Letter}{$hRow2}");

                // "LANSIR MOBIL": merge horizontal di row 1
                $sheet->mergeCells("{$lansirStartLetter}{$hRow1}:{$lansirEndLetter}{$hRow1}");
                $sheet->getStyle("{$lansirStartLetter}{$hRow1}:{$lansirEndLetter}{$hRow1}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // Spacer 2 column: merge 2 baris
                $sheet->mergeCells("{$spacer2Letter}{$hRow1}:{$spacer2Letter}{$hRow2}");

                // "TIM BONGKAR": merge horizontal di row 1
                $sheet->mergeCells("{$timStartLetter}{$hRow1}:{$timEndLetter}{$hRow1}");
                $sheet->getStyle("{$timStartLetter}{$hRow1}:{$timEndLetter}{$hRow1}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // ── Style kedua baris header ──────────────────────────
                $headerStyle = [
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'name' => 'Arial', 'size' => 10],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2563EB']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ];
                $sheet->getStyle("A{$hRow1}:{$lastCol}{$hRow1}")->applyFromArray($headerStyle);
                $sheet->getStyle("A{$hRow2}:{$lastCol}{$hRow2}")->applyFromArray($headerStyle);

                // ── Formula SUM di baris TOTAL ────────────────────────
                // Main table columns
                for ($ci = $karungStartCol; $ci <= $jumlahCol; $ci++) {
                    $col = $this->getColumnLetter($ci);
                    $sheet->setCellValue(
                        "{$col}{$totalRowNum}",
                        "=SUM({$col}{$dataStartRow}:{$col}" . ($totalRowNum - 1) . ')'
                    );
                }

                // Lansir columns (skip spacer, skip no polisi & sopir)
                for ($ci = $lansirStartCol + 2; $ci <= $lansirEndCol; $ci++) {
                    $col = $this->getColumnLetter($ci);
                    $sheet->setCellValue(
                        "{$col}{$totalRowNum}",
                        "=SUM({$col}{$dataStartRow}:{$col}" . ($totalRowNum - 1) . ')'
                    );
                }

                // Tim Bongkar columns (skip spacer, skip nama tim)
                for ($ci = $timStartCol + 1; $ci <= $timEndCol; $ci++) {
                    $col = $this->getColumnLetter($ci);
                    $sheet->setCellValue(
                        "{$col}{$totalRowNum}",
                        "=SUM({$col}{$dataStartRow}:{$col}" . ($totalRowNum - 1) . ')'
                    );
                }

                // ── Style & merge baris TOTAL ─────────────────────────
                $sheet->mergeCells("A{$totalRowNum}:{$lastIdentitasCol}{$totalRowNum}");
                $sheet->getStyle("A{$totalRowNum}:{$lastCol}{$totalRowNum}")
                    ->applyFromArray([
                        'font' => ['bold' => true, 'name' => 'Arial', 'size' => 10],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE5E7EB']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    ]);

                // ── Style data rows + merge A–D per kendaraan (sampai No. DO) ─────────
                $currentRow = $dataStartRow;
                $colorIndex = 0;
                $colors = ['FFF3F4F6', 'FFFFFFFF'];

                foreach ($po->kendaraans->sortBy('no_polisi') as $kendaraan) {
                    // Hitung total baris per kendaraan (termasuk extra rows lansir)
                    $totalRows = 0;
                    if ($kendaraan->penerimas->count() > 0) {
                        foreach ($kendaraan->penerimas as $penerima) {
                            $lansir = $penerima->lansirs->first();
                            $extraCount = 0;
                            if ($lansir) {
                                $extraCount = max(
                                    $lansir->mobils->count() - 1,
                                    $lansir->tims->count() - 1
                                );
                            }
                            $totalRows += 1 + $extraCount;
                        }
                    } else {
                        $totalRows = 1;
                    }

                    $start = $currentRow;
                    $end = $currentRow + $totalRows - 1;

                    // Merge A–D jika > 1 baris
                    if ($totalRows > 1) {
                        foreach (['A', 'B', 'C', 'D'] as $col) {
                            $sheet->mergeCells("{$col}{$start}:{$col}{$end}");
                            $sheet->getStyle("{$col}{$start}:{$col}{$end}")
                                ->getAlignment()
                                ->setVertical(Alignment::VERTICAL_CENTER)
                                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        }
                    }

                    // Merge kolom Tujuan & Penerima per penerima jika ada extra rows lansir
                    $penerimaRow = $currentRow;
                    if ($kendaraan->penerimas->count() > 0) {
                        foreach ($kendaraan->penerimas as $penerima) {
                            $lansir = $penerima->lansirs->first();
                            $extraCount = 0;
                            if ($lansir) {
                                $extraCount = max(
                                    $lansir->mobils->count() - 1,
                                    $lansir->tims->count() - 1
                                );
                            }
                            $penerimaRows = 1 + $extraCount;
                            if ($penerimaRows > 1) {
                                $penerimaEnd = $penerimaRow + $penerimaRows - 1;
                                $sheet->mergeCells("{$tujuanColLetter}{$penerimaRow}:{$tujuanColLetter}{$penerimaEnd}");
                                $sheet->getStyle("{$tujuanColLetter}{$penerimaRow}:{$tujuanColLetter}{$penerimaEnd}")
                                    ->getAlignment()
                                    ->setVertical(Alignment::VERTICAL_CENTER)
                                    ->setHorizontal(Alignment::HORIZONTAL_LEFT);
                                $sheet->mergeCells("{$penerimaColLetter}{$penerimaRow}:{$penerimaColLetter}{$penerimaEnd}");
                                $sheet->getStyle("{$penerimaColLetter}{$penerimaRow}:{$penerimaColLetter}{$penerimaEnd}")
                                    ->getAlignment()
                                    ->setVertical(Alignment::VERTICAL_CENTER)
                                    ->setHorizontal(Alignment::HORIZONTAL_LEFT);
                            }
                            $penerimaRow += $penerimaRows;
                        }
                    }

                    $fill = $colors[$colorIndex % 2];
                    for ($r = $start; $r <= $end; $r++) {
                        $sheet->getStyle("A{$r}:D{$r}")
                            ->applyFromArray([
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF3F4F6']],
                                'font' => ['name' => 'Arial', 'size' => 10],
                                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                            ]);
                        $sheet->getStyle("{$tujuanColLetter}{$r}:{$ketLetter}{$r}")
                            ->applyFromArray([
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $fill]],
                                'font' => ['name' => 'Arial', 'size' => 10],
                                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                            ]);
                        // Lansir columns styling
                        $sheet->getStyle("{$lansirStartLetter}{$r}:{$lansirEndLetter}{$r}")
                            ->applyFromArray([
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFEF3C7']], // Light yellow
                                'font' => ['name' => 'Arial', 'size' => 10],
                                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                            ]);
                        // Tim Bongkar columns styling
                        $sheet->getStyle("{$timStartLetter}{$r}:{$timEndLetter}{$r}")
                            ->applyFromArray([
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD1FAE5']], // Light green
                                'font' => ['name' => 'Arial', 'size' => 10],
                                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                            ]);
                    }

                    $currentRow += $totalRows;
                    $colorIndex++;
                }

                // ── Border seluruh tabel ──────────────────────────────
                $sheet->getStyle("A{$hRow1}:{$lastCol}{$totalRowNum}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                // ── Auto size kolom ───────────────────────────────────
                foreach (range('A', $lastCol) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // ── Row height ────────────────────────────────────────
                for ($r = $hRow1; $r <= $totalRowNum; $r++) {
                    $sheet->getRowDimension($r)->setRowHeight(22);
                }
            },
        ];
    }

    private function getColumnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = intdiv($index, 26);
        }

        return $letter;
    }
}
