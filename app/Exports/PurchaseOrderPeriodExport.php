<?php

namespace App\Exports;

use App\Models\KodePakan;
use App\Models\PurchaseOrder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use App\Traits\WithUserTujuan;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PurchaseOrderPeriodExport implements FromArray, WithEvents, WithTitle
{
    protected Collection $pos;
    use WithUserTujuan;

    protected array $kodePakanList;

    protected string $from;

    protected string $to;

    // NO | TANGGAL | No PO | KENDARAAN | No. DO | Tujuan | PENERIMA = 7 kolom (A-G)
    protected int $identitasCols = 7;

    public function __construct(string $from, string $to, ?int $cvId = null)
    {
        $this->from = $from;
        $this->to = $to;
        $tujuans = $this->getUserTujuan();


        $query = PurchaseOrder::with([
            'cv',
            'kendaraans' => function ($q) {
                $q->where('status', '!=', 'batal');
            },
            'kendaraans.supplier',
            'kendaraans.tujuan',
            'kendaraans.penerimas.pakans.kodePakan',
            'kendaraans.penerimas.tujuan',
            'kendaraans.penerimas.penerima.tujuan',
            'kendaraans.penerimas.lansirs.mobils',
            'kendaraans.penerimas.lansirs.tims',
        ])
         ->where(function ($q) use ($tujuans) {
                        $q->whereHas('kendaraans.penerimas.penerima', function ($q) use ($tujuans) {
                            $q->whereIn('tujuan_id', $tujuans->pluck('id'));
                        })
                        ->orWhereDoesntHave('kendaraans.penerimas')
                        ->orWhereHas('kendaraans.penerimas', function ($q) {
                            $q->whereDoesntHave('penerima');               // penerima orphan
                        });
                    })
                    ->where('status', '!=', 'batal')->orderBy('tanggal_po', 'asc')->orderBy('no_po', 'asc');

        if ($from) {
            $query->whereDate('tanggal_po', '>=', $from);
        }
        if ($to) {
            $query->whereDate('tanggal_po', '<=', $to);
        }
        if ($cvId) {
            $query->where('cv_id', $cvId);
        }

        $this->pos = $query->get();

        $this->kodePakanList = KodePakan::orderBy('kode')->get()->all();
    }

    public function array(): array
    {
        $rows = [];
        $kpCount = count($this->kodePakanList);
        $idCols = $this->identitasCols;

        // ── Header baris 1 ───────────────────────────────────────────
        $header1 = ['NO', 'TANGGAL', 'No PO', 'KENDARAAN', 'No. DO', 'Tujuan', 'PENERIMA'];

        // Group Jumlah Karung (n kolom)
        $header1[] = 'Jumlah Karung';
        for ($i = 1; $i < $kpCount; $i++) {
            $header1[] = '';
        }

        // Group KG (n kolom)
        $header1[] = 'KG';
        for ($i = 1; $i < $kpCount; $i++) {
            $header1[] = '';
        }

        // Kolom tunggal (merge 2 baris di AfterSheet)
        $header1[] = 'Ongkos Angkut';
        $header1[] = 'Jumlah (Rp)';
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

        $rows[] = $header1;

        // ── Header baris 2 ───────────────────────────────────────────
        // Kolom identitas kosong (merge 2 baris di AfterSheet)
        $header2 = array_fill(0, $idCols, '');

        // Sub-header Jumlah Karung
        foreach ($this->kodePakanList as $kp) {
            $header2[] = $kp->kode;
        }

        // Sub-header KG
        foreach ($this->kodePakanList as $kp) {
            $header2[] = $kp->kode;
        }

        // Kolom tunggal kosong (merge 2 baris)
        $header2[] = ''; // Ongkos Angkut
        $header2[] = ''; // Jumlah (Rp)
        $header2[] = ''; // CV
        $header2[] = ''; // Keterangan

        // ── LANSIR MOBIL SUB-HEADERS ──────────────────────────────────
        $header2[] = ''; // Spacer
        $header2[] = 'Tanggal';
        $header2[] = 'No Polisi';
        $header2[] = 'Sopir';
        $header2[] = 'Jumlah (kg)';
        $header2[] = 'Jumlah (bag)';
        $header2[] = 'Ongkos';
        $header2[] = 'Total';

        // ── TIM BONGKAR SUB-HEADERS ───────────────────────────────────
        $header2[] = ''; // Spacer
        $header2[] = 'Nama Tim';
        $header2[] = 'Jumlah (kg)';
        $header2[] = 'Upah/kg';
        $header2[] = 'Total';

        $rows[] = $header2;

        // ── Data: 1 baris per penerima ────────────────────────────────
        $no = 1;
        foreach ($this->pos as $po) {
            foreach ($po->kendaraans->where('status', '!=', 'batal')->sortBy('no_polisi') as $kendaraan) {

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
                        $po->tanggal_po->translatedFormat('d F Y'),
                        $po->no_po,
                        $kendaraan->no_polisi,
                        $penerima?->no_do ?? '-',
                        $namaTujuan,
                        $penerima?->nama_penerima ?? '',
                    ];

                    if ($penerima !== null) {
                        // ── Generate row pertama dengan identitas (pakan, dll) ──
                        // Jumlah Karung per kode pakan
                        foreach ($this->kodePakanList as $kp) {
                            $pakan = $penerima->pakans->firstWhere('kode_pakan_id', $kp->id);
                            $row[] = ($pakan && $pakan->jumlah_karung) ? $pakan->jumlah_karung : '';
                        }

                        // KG per kode pakan
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
                        $oaAngkut = $penerima->pakans->whereNotNull('ongkos_oa')->first()?->ongkos_oa;
                        $row[] = $oaAngkut ?: '';

                        // Jumlah (Rp)
                        $row[] = $totalOngkos > 0 ? $totalOngkos : '';

                        // CV
                        $row[] = $po->cv?->nama_cv ?? '';

                        // Keterangan: tipe tujuan penerima (kosong jika tidak ada)
                        $row[] = $penerima->penerima?->tujuan?->type ?? '';

                        // Simpan row pertama dengan identitas untuk nanti
                        $rowWithIdentitas = $row;

                        // ── Loop SEMUA lansir penerima ──
                        $lansirs = $penerima->lansirs;
                        $isFirstLansir = true;

                        foreach ($lansirs as $lansir) {
                            if ($isFirstLansir) {
                                // Pakai row dengan identitas untuk lansir pertama
                                $currentRow = $rowWithIdentitas;
                                $isFirstLansir = false;
                            } else {
                                // Untuk lansir selanjutnya, identitas kosong
                                $currentRow = array_fill(0, $idCols + ($kpCount * 2) + 4, '');
                            }

                            // ── LANSIR MOBIL DATA ─────────────────────────────────
                            if ($lansir && $lansir->mobils->count() > 0) {
                                $firstMobil = $lansir->mobils->first();
                                $currentRow[] = ''; // Spacer
                                $currentRow[] = $lansir->tanggal_lansir->translatedFormat('d F Y') ?? '';
                                $currentRow[] = $firstMobil->no_polisi ?? '';
                                $currentRow[] = $firstMobil->nama_sopir ?? '';
                                $currentRow[] = $firstMobil->berat ?? '';
                                $currentRow[] = $firstMobil->jumlah_karung ?? '';
                                $currentRow[] = number_format($firstMobil->ongkos, '0', ',', '.') ?? '';
                                $currentRow[] = (float) ($firstMobil->berat ?? 0) * (float) ($firstMobil->ongkos ?? 0);
                            } else {
                                $currentRow[] = ''; // Spacer
                                $currentRow[] = ''; // Spacer
                                $currentRow[] = '';
                                $currentRow[] = '';
                                $currentRow[] = '';
                                $currentRow[] = '';
                                $currentRow[] = '';
                                $currentRow[] = '';
                            }

                            // ── TIM BONGKAR DATA ──────────────────────────────────
                            if ($lansir && $lansir->tims->count() > 0) {
                                $firstTim = $lansir->tims->first();
                                $totalBerat = $lansir->mobils->sum('berat');
                                $currentRow[] = ''; // Spacer
                                $currentRow[] = $firstTim->nama_tim ?? '';
                                $currentRow[] = $firstTim->berat ?? $totalBerat;
                                $currentRow[] = $firstTim->upah ?? '';
                                $currentRow[] = (float) ($firstTim->berat ?? $totalBerat) * (float) ($firstTim->upah ?? 0);
                            } else {
                                $currentRow[] = ''; // Spacer
                                $currentRow[] = '';
                                $currentRow[] = '';
                                $currentRow[] = '';
                                $currentRow[] = '';
                            }

                            $rows[] = $currentRow;

                            // ── Extra mobils/tims untuk lansir ini ──
                            $extraMobils = $lansir->mobils->slice(1)->values();
                            $extraTims = $lansir->tims->slice(1)->values();
                            $extraCount = max($extraMobils->count(), $extraTims->count());

                            for ($ei = 0; $ei < $extraCount; $ei++) {
                                $extraRow = array_fill(0, $idCols + ($kpCount * 2) + 4, '');

                                // Mobil lansir extra
                                $mobil = $extraMobils->get($ei);
                                $extraRow[] = ''; // Spacer
                                $extraRow[] = $lansir->tanggal_lansir->format('d/m/Y') ?? '';
                                $extraRow[] = $mobil ? ($mobil->no_polisi ?? '') : '';
                                $extraRow[] = $mobil ? ($mobil->nama_sopir ?? '') : '';
                                $extraRow[] = $mobil ? ($mobil->berat ?? '') : '';
                                $extraRow[] = $mobil ? ($mobil->jumlah_karung ?? '') : '';
                                $extraRow[] = $mobil ? (number_format($mobil->ongkos, '0', ',', '.') ?? '') : '';
                                $extraRow[] = $mobil ? (float) ($mobil->berat ?? 0) * (float) ($mobil->ongkos ?? 0) : '';

                                // Tim bongkar extra
                                $tim = $extraTims->get($ei);
                                $extraRow[] = ''; // Spacer
                                $extraRow[] = $tim ? ($tim->nama_tim ?? '') : '';
                                $extraRow[] = $tim ? ($tim->berat ?? '') : '';
                                $extraRow[] = $tim ? ($tim->upah ?? '') : '';
                                $extraRow[] = $tim ? (float) ($tim->berat ?? 0) * (float) ($tim->upah ?? 0) : '';

                                $rows[] = $extraRow;
                            }
                        }

                        // Jika tidak ada lansir sama sekali, tambahkan row dengan identitas saja
                        if ($lansirs->count() === 0) {
                            // Tambahkan kolom lansir & tim kosong
                            $rowWithIdentitas[] = '';
                            $rowWithIdentitas[] = '';
                            $rowWithIdentitas[] = '';
                            $rowWithIdentitas[] = '';
                            $rowWithIdentitas[] = '';
                            $rowWithIdentitas[] = '';
                            $rowWithIdentitas[] = '';
                            $rowWithIdentitas[] = '';
                            $rowWithIdentitas[] = '';
                            $rowWithIdentitas[] = '';
                            $rowWithIdentitas[] = '';
                            $rowWithIdentitas[] = '';
                            $rowWithIdentitas[] = '';
                            $rows[] = $rowWithIdentitas;
                        }
                    } else {
                        // Kendaraan belum punya penerima: muatan & OA dari level kendaraan
                        // (karung/kg di kolom kode pakan pertama agar SUM per kolom tetap benar)
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
                        $row[] = $po->cv?->nama_cv ?? '';
                        
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
                        $rows[] = $row;
                    }
                }
            }
        }

        // ── Baris TOTAL ───────────────────────────────────────────────
        // Kolom identitas + n karung + n kg + oa + jumlah + cv + keterangan
        $totalRow = array_fill(0, $idCols, '');
        $totalRow[0] = 'TOTAL';
        for ($i = 0; $i < ($kpCount * 2) + 2; $i++) {
            $totalRow[] = ''; // diisi formula SUM di AfterSheet
        }
        $totalRow[] = ''; // CV
        $totalRow[] = ''; // Keterangan

        // Lansir Mobil totals
        $totalRow[] = 'TOTAL';
        $totalRow[] = ''; // Spacer
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

        $rows[] = $totalRow;

        return $rows;
    }

    public function title(): string
    {
        $clean = fn($s) => preg_replace('/[\/\\\?\*\[\]:]/', '-', $s);

        return substr('PO ' . $clean($this->from) . ' sd ' . $clean($this->to), 0, 31);
    }

    public function registerEvents(): array
    {
        $pos = $this->pos;
        $kodePakanCount = count($this->kodePakanList);
        $from = $this->from;
        $to = $this->to;
        $poCount = $this->pos->count();
        $idCols = $this->identitasCols;

        return [
            AfterSheet::class => function (AfterSheet $event) use ($pos, $kodePakanCount, $from, $to, $poCount, $idCols) {
                $sheet = $event->sheet->getDelegate();

                // ── Insert 3 baris info di atas ──────────────────────
                $sheet->insertNewRowBefore(1, 3);

                $sheet->setCellValue('A1', 'Periode');
                $sheet->setCellValue('B1', date('d/m/Y', strtotime($from)) . ' - ' . date('d/m/Y', strtotime($to)));
                $sheet->setCellValue('A2', 'Jumlah PO');
                $sheet->setCellValue('B2', $poCount);
                $sheet->setCellValue('A3', 'Tanggal Export');
                $sheet->setCellValue('B3', now()->translatedFormat('d F Y'));
                $sheet->getStyle('A1:A3')->getFont()->setBold(true);

                // ── Posisi baris ──────────────────────────────────────
                $hRow1 = 4; // header group
                $hRow2 = 5; // header kode pakan
                $dataStartRow = 6;
                $totalRowNum = $sheet->getHighestRow();

                //
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
                $lansirEndCol = $idCols + 2 * $kodePakanCount + 12; // 17+2n
                $spacer2Col = $idCols + 2 * $kodePakanCount + 13; // 18+2n
                $timStartCol = $idCols + 2 * $kodePakanCount + 14; // 19+2n
                $timEndCol = $idCols + 2 * $kodePakanCount + 17;
                $totalCols = $timEndCol;

                $lastCol = $this->getColumnLetter($totalCols);
                $lastIdentitasCol = $this->getColumnLetter($idCols);
                $nextDataCol = $this->getColumnLetter($idCols + 1);

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

                // Kolom identitas (A–G): merge 2 baris header
                foreach (range('A', $lastIdentitasCol) as $col) {
                    $sheet->mergeCells("{$col}{$hRow1}:{$col}{$hRow2}");
                    $sheet->getStyle("{$col}{$hRow1}:{$col}{$hRow2}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER);
                }

                // Group "Jumlah Karung" — merge horizontal di hRow1
                if ($kodePakanCount > 1) {
                    $sheet->mergeCells("{$karungStartLetter}{$hRow1}:{$karungEndLetter}{$hRow1}");
                }
                $sheet->getStyle("{$karungStartLetter}{$hRow1}:{$karungEndLetter}{$hRow1}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // Group "KG" — merge horizontal di hRow1
                if ($kodePakanCount > 1) {
                    $sheet->mergeCells("{$kgStartLetter}{$hRow1}:{$kgEndLetter}{$hRow1}");
                }
                $sheet->getStyle("{$kgStartLetter}{$hRow1}:{$kgEndLetter}{$hRow1}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // "Ongkos Angkut" — merge 2 baris
                $sheet->mergeCells("{$oaLetter}{$hRow1}:{$oaLetter}{$hRow2}");
                $sheet->getStyle("{$oaLetter}{$hRow1}:{$oaLetter}{$hRow2}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // "Jumlah (Rp)" — merge 2 baris
                $sheet->mergeCells("{$jumlahLetter}{$hRow1}:{$jumlahLetter}{$hRow2}");
                $sheet->getStyle("{$jumlahLetter}{$hRow1}:{$jumlahLetter}{$hRow2}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // "CV" — merge 2 baris
                $sheet->mergeCells("{$cvLetter}{$hRow1}:{$cvLetter}{$hRow2}");
                $sheet->getStyle("{$cvLetter}{$hRow1}:{$cvLetter}{$hRow2}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // "Keterangan" — merge 2 baris
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
                // Main table columns (karung, kg, oa, jumlah)
                for ($ci = $karungStartCol; $ci <= $jumlahCol; $ci++) {
                    $col = $this->getColumnLetter($ci);
                    $sheet->setCellValue(
                        "{$col}{$totalRowNum}",
                        "=SUM({$col}{$dataStartRow}:{$col}" . ($totalRowNum - 1) . ')'
                    );
                }

                // Lansir columns (skip spacer, skip no polisi & sopir)
                for ($ci = $lansirStartCol + 3; $ci <= $lansirEndCol; $ci++) {
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
                // Merge seluruh kolom identitas (A–G)
                $sheet->mergeCells("A{$totalRowNum}:{$lastIdentitasCol}{$totalRowNum}");
                $sheet->getStyle("A{$totalRowNum}:{$lastCol}{$totalRowNum}")
                    ->applyFromArray([
                        'font' => ['bold' => true, 'name' => 'Arial', 'size' => 10],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE5E7EB']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    ]);

                // ── Style data rows ───────────────────────────────────
                // Merge A–E per kendaraan (sampai No. DO). Kolom Tujuan & PENERIMA tidak di-merge antar penerima.
                $currentRow = $dataStartRow;
                $colorIndex = 0;
                $colors = ['FFF3F4F6', 'FFFFFFFF'];
                $tujuanColLetter = $this->getColumnLetter($idCols - 1);
                $penerimaColLetter = $lastIdentitasCol;

                foreach ($pos as $po) {
                    foreach ($po->kendaraans->where('status', '!=', 'batal')->sortBy('no_polisi') as $kendaraan) {
                        // Hitung total baris per kendaraan
                        $totalRows = 0;

                        if ($kendaraan->penerimas->count() > 0) {
                            foreach ($kendaraan->penerimas as $penerima) {
                                $totalRows++;
                                $lansir = $penerima->lansirs->first();
                                if ($lansir) {
                                    $totalRows += max(
                                        $lansir->mobils->count() - 1,
                                        $lansir->tims->count() - 1
                                    );
                                }
                            }
                        } else {
                            $totalRows = 1;
                        }

                        $kendaraanStart = $currentRow;
                        $kendaraanEnd = $currentRow + $totalRows - 1;

                        // Merge A–E (sampai No. DO) per kendaraan jika > 1 baris
                        if ($totalRows > 1) {
                            $mergeEndCol = $this->getColumnLetter(5);
                            foreach (range('A', $mergeEndCol) as $col) {
                                $sheet->mergeCells("{$col}{$kendaraanStart}:{$col}{$kendaraanEnd}");
                                $sheet->getStyle("{$col}{$kendaraanStart}:{$col}{$kendaraanEnd}")
                                    ->getAlignment()
                                    ->setVertical(Alignment::VERTICAL_CENTER)
                                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                            }
                        }

                        // Per penerima: merge kolom Tujuan & PENERIMA jika ada baris lansir tambahan
                        $penerimaRow = $currentRow;
                        if ($kendaraan->penerimas->count() > 0) {
                            foreach ($kendaraan->penerimas as $penerima) {
                                $penerimaRows = 0;

                                // Hitung total baris untuk penerima ini
                                $lansirs = $penerima->lansirs;
                                if ($lansirs->count() > 0) {
                                    foreach ($lansirs as $lansir) {
                                        $penerimaRows++; // 1 baris untuk lansir ini
                                        // Tambah baris untuk extra mobils/tims
                                        $extraMobils = $lansir->mobils->slice(1)->values();
                                        $extraTims = $lansir->tims->slice(1)->values();
                                        $penerimaRows += max($extraMobils->count(), $extraTims->count());
                                    }
                                } else {
                                    $penerimaRows = 1; // Tidak ada lansir, cuma 1 baris identitas
                                }

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
                        for ($r = $kendaraanStart; $r <= $kendaraanEnd; $r++) {
                            $sheet->getStyle("A{$r}:{$lastIdentitasCol}{$r}")
                                ->applyFromArray([
                                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF3F4F6']],
                                    'font' => ['name' => 'Arial', 'size' => 10],
                                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                                ]);
                            $sheet->getStyle("{$nextDataCol}{$r}:{$ketLetter}{$r}")
                                ->applyFromArray([
                                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $fill]],
                                    'font' => ['name' => 'Arial', 'size' => 10],
                                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                                ]);
                            $sheet->getStyle("{$lansirStartLetter}{$r}:{$lansirEndLetter}{$r}")
                                ->applyFromArray([
                                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFEF3C7']],
                                    'font' => ['name' => 'Arial', 'size' => 10],
                                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                                ]);
                            $sheet->getStyle("{$timStartLetter}{$r}:{$timEndLetter}{$r}")
                                ->applyFromArray([
                                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD1FAE5']],
                                    'font' => ['name' => 'Arial', 'size' => 10],
                                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                                ]);
                        }

                        $currentRow += $totalRows;
                        $colorIndex++;
                    }
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
