<?php

namespace App\Exports;

use App\Models\LansirPayment;
use App\Models\PoItemLansir;
use App\Models\PurchaseOrder;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class RekapLansirExport implements ShouldAutoSize, WithEvents, WithTitle
{
    public function __construct(private PurchaseOrder $po) {}

    public function title(): string
    {
        return 'Rekap Lansir';
    }

    public function registerEvents(): array
    {
        $po = $this->po;

        return [
            AfterSheet::class => function (AfterSheet $event) use ($po) {
                $sheet = $event->sheet->getDelegate();

                // Load all events with relations
                $events = PoItemLansir::with([
                    'item.penerimaList',
                    'item.tujuan',
                    'mobils',
                    'tims',
                ])->whereHas('item', fn ($q) => $q->where('po_id', $po->id))
                    ->get();

                $paymentMobil = LansirPayment::where('po_id', $po->id)
                    ->where('tipe', LansirPayment::TIPE_MOBIL)->first();
                $paymentTim = LansirPayment::where('po_id', $po->id)
                    ->where('tipe', LansirPayment::TIPE_TIM)->first();

                $row = 1;

                // ── PO Info ──────────────────────────────────────────
                $sheet->setCellValue('A'.$row, 'No. PO');
                $sheet->setCellValue('B'.$row, $po->no_po);
                $sheet->getStyle('A'.$row)->getFont()->setBold(true);
                $row++;

                $sheet->setCellValue('A'.$row, 'Tanggal');
                $sheet->setCellValue('B'.$row, $po->tanggal_po->format('d/m/Y'));
                $sheet->getStyle('A'.$row)->getFont()->setBold(true);
                $row++;

                $sheet->setCellValue('A'.$row, 'CV');
                $sheet->setCellValue('B'.$row, $po->cv?->nama_cv ?? '-');
                $sheet->getStyle('A'.$row)->getFont()->setBold(true);
                $row++;

                $row++; // blank

                // ── TABEL MOBIL LANSIR ────────────────────────────────
                $mobilHeaderRow = $row;
                $mobilHeaders = ['Tanggal', 'Nopol', 'Sopir', 'Asal Pakan', 'Peternak', 'Jumlah (Bag)', 'OA', 'TOTAL OA'];
                foreach ($mobilHeaders as $col => $header) {
                    $sheet->setCellValueByColumnAndRow($col + 1, $row, $header);
                }
                $this->styleHeaderRow($sheet, $row, count($mobilHeaders));
                $row++;

                $grandTotalOa = 0;
                foreach ($events as $event) {
                    $tanggal = $event->selesai_at?->format('d/m/y') ?? '-';
                    $penerimaList = $event->item->penerimaList;
                    $asalPakan = $event->item->tujuan?->nama ?? '-';
                    $penerima = $penerimaList->isNotEmpty()
                        ? $penerimaList->pluck('nama')->join(', ')
                        : ($event->item->nama_penerima ?? '-');

                    foreach ($event->mobils as $mobil) {
                        $totalOa = ($mobil->berat ?? 0) * ($mobil->ongkos ?? 0);
                        $grandTotalOa += $totalOa;

                        $sheet->setCellValueByColumnAndRow(1, $row, $tanggal);
                        $sheet->setCellValueByColumnAndRow(2, $row, $mobil->no_polisi);
                        $sheet->setCellValueByColumnAndRow(3, $row, $mobil->nama_sopir ?? '-');
                        $sheet->setCellValueByColumnAndRow(4, $row, $asalPakan ?? '-');
                        $sheet->setCellValueByColumnAndRow(5, $row, $penerima);
                        $sheet->setCellValueByColumnAndRow(6, $row, $mobil->jumlah_karung ?? 0);
                        $sheet->setCellValueByColumnAndRow(7, $row, $mobil->ongkos ?? 0);
                        $sheet->setCellValueByColumnAndRow(8, $row, $totalOa);
                        $row++;
                    }
                }

                // Grand total mobil
                $sheet->setCellValueByColumnAndRow(1, $row, 'TOTAL');
                $sheet->setCellValueByColumnAndRow(7, $row, $grandTotalOa);
                $sheet->getStyle('A'.$row.':G'.$row)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD1FAE5']],
                ]);
                $row++;

                // Status bayar mobil
                $statusMobil = $paymentMobil?->status === LansirPayment::STATUS_SUDAH ? 'Sudah Bayar' : 'Belum Bayar';
                $sheet->setCellValueByColumnAndRow(1, $row, 'Status Bayar Mobil: '.$statusMobil);
                if ($paymentMobil?->tanggal_bayar) {
                    $sheet->setCellValueByColumnAndRow(4, $row, 'Tgl: '.$paymentMobil->tanggal_bayar->format('d/m/Y'));
                    $sheet->setCellValueByColumnAndRow(5, $row, 'Oleh: '.($paymentMobil->dibayar_oleh ?? '-'));
                }
                $sheet->getStyle('A'.$row)->getFont()->setBold(true)->setItalic(true);
                $row++;

                $row++; // blank

                // ── TITLE TIM BONGKAR ─────────────────────────────────
                $timTitleRow = $row;
                $sheet->setCellValue('A'.$row, 'Team Bongkar');
                $sheet->mergeCells('A'.$row.':G'.$row);
                $sheet->getStyle('A'.$row.':G'.$row)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE5E7EB']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);
                $row++;

                $row++; // blank

                // ── TABEL TIM BONGKAR ─────────────────────────────────
                $timHeaders = ['Tanggal', 'Nopol', 'Team Bongkar', 'Asal Pakan', 'Peternak', 'Jumlah (Bag)', 'Upah Bongkar', 'TOTAL OA'];
                foreach ($timHeaders as $col => $header) {
                    $sheet->setCellValueByColumnAndRow($col + 1, $row, $header);
                }
                $this->styleHeaderRow($sheet, $row, count($timHeaders));
                $row++;

                $grandTotalUpah = 0;
                foreach ($events as $event) {
                    $tanggal = $event->selesai_at?->format('d/m/y') ?? '-';
                    $nopol = $event->item->no_polisi ?? '-';
                    $asalPakan = $event->item->tujuan?->nama ?? '-';
                    $penerimaList = $event->item->penerimaList;
                    $penerima = $penerimaList->isNotEmpty()
                        ? $penerimaList->pluck('nama')->join(', ')
                        : ($event->item->nama_penerima ?? '-');
                    $karung = $event->item->jumlah_karung ?? 0;
                    $totalBerat = $event->total_berat;

                    foreach ($event->tims as $tim) {
                        $totalUpah = $totalBerat * ($tim->upah ?? 0);
                        $grandTotalUpah += $totalUpah;

                        $sheet->setCellValueByColumnAndRow(1, $row, $tanggal);
                        $sheet->setCellValueByColumnAndRow(2, $row, $nopol);
                        $sheet->setCellValueByColumnAndRow(3, $row, $tim->nama_tim);
                        $sheet->setCellValueByColumnAndRow(4, $row, $asalPakan);
                        $sheet->setCellValueByColumnAndRow(5, $row, $penerima);
                        $sheet->setCellValueByColumnAndRow(6, $row, $karung);
                        $sheet->setCellValueByColumnAndRow(7, $row, $tim->upah ?? 0);
                        $sheet->setCellValueByColumnAndRow(8, $row, $totalUpah);
                        $row++;
                    }
                }

                // Grand total tim
                $sheet->setCellValueByColumnAndRow(1, $row, 'TOTAL');
                $sheet->setCellValueByColumnAndRow(8, $row, $grandTotalUpah);
                $sheet->getStyle('A'.$row.':H'.$row)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD1FAE5']],
                ]);
                $row++;

                // Status bayar tim
                $statusTim = $paymentTim?->status === LansirPayment::STATUS_SUDAH ? 'Sudah Bayar' : 'Belum Bayar';
                $sheet->setCellValueByColumnAndRow(1, $row, 'Status Bayar Tim: '.$statusTim);
                if ($paymentTim?->tanggal_bayar) {
                    $sheet->setCellValueByColumnAndRow(4, $row, 'Tgl: '.$paymentTim->tanggal_bayar->format('d/m/Y'));
                    $sheet->setCellValueByColumnAndRow(5, $row, 'Oleh: '.($paymentTim->dibayar_oleh ?? '-'));
                }
                $sheet->getStyle('A'.$row)->getFont()->setBold(true)->setItalic(true);
            },
        ];
    }

    private function styleHeaderRow($sheet, int $row, int $colCount): void
    {
        $lastCol = Coordinate::stringFromColumnIndex($colCount);
        $sheet->getStyle('A'.$row.':'.$lastCol.$row)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2563EB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
    }
}
