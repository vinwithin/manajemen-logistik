<?php

namespace App\Exports;

use App\Models\PurchaseOrder;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchaseOrderExport implements FromArray, ShouldAutoSize, WithEvents, WithStyles, WithTitle
{
    protected PurchaseOrder $po;
    protected array $kodePakanList;

    public function __construct(PurchaseOrder $po)
    {
        $this->po = $po;

        // Kumpulkan kode pakan unik dari semua kendaraan → penerima → pakan
        $this->kodePakanList = $po->kendaraans
            ->flatMap(fn($k) => $k->penerimas)
            ->flatMap(fn($p) => $p->pakans)
            ->map(fn($pk) => $pk->kodePakan)
            ->filter()
            ->unique('id')
            ->sortBy('kode')
            ->values()
            ->all();
    }

    public function array(): array
    {
        $rows = [];

        // Header: NO | KENDARAAN | PETERNAK | [kode pakan...] | TUJUAN | TOTAL KG
        $header = ['NO', 'KENDARAAN', 'PETERNAK'];
        foreach ($this->kodePakanList as $kp) {
            $header[] = $kp->kode;
        }
        $header[] = 'TUJUAN';
        $header[] = 'TOTAL KG';
        $rows[] = $header;

        $no = 1;
        $subtotals = array_fill(0, count($this->kodePakanList), 0);
        $grandKg = 0;

        foreach ($this->po->kendaraans->sortBy('no_polisi') as $kendaraan) {
            foreach ($kendaraan->penerimas as $penerima) {
                $row = [$no++, $kendaraan->no_polisi, $penerima->nama_penerima];

                foreach ($this->kodePakanList as $i => $kp) {
                    $pakan = $penerima->pakans->firstWhere('kode_pakan_id', $kp->id);
                    $karung = $pakan ? $pakan->jumlah_karung : 0;
                    $row[] = $karung;
                    $subtotals[$i] += $karung;
                }

                $row[] = $penerima->tujuan?->nama ?? '-';
                $row[] = $penerima->total_kg;
                $grandKg += $penerima->total_kg;
                $rows[] = $row;
            }
        }

        // Baris total
        $totalRow = ['', 'TOTAL', ''];
        foreach ($subtotals as $s) {
            $totalRow[] = $s;
        }
        $totalRow[] = '';
        $totalRow[] = $grandKg;
        $rows[] = $totalRow;

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2563EB']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function title(): string
    {
        return 'PO '.$this->po->no_po;
    }

    public function registerEvents(): array
    {
        $po = $this->po;

        return [
            AfterSheet::class => function (AfterSheet $event) use ($po) {
                $sheet = $event->sheet->getDelegate();

                // Insert 3 info rows at top
                $sheet->insertNewRowBefore(1, 3);

                $sheet->setCellValue('A1', 'No. PO');
                $sheet->setCellValue('B1', $po->no_po);
                $sheet->setCellValue('A2', 'Tanggal');
                $sheet->setCellValue('B2', $po->tanggal_po->format('d/m/Y'));
                $sheet->setCellValue('A3', 'CV');
                $sheet->setCellValue('B3', $po->cv?->nama_cv ?? '-');

                $sheet->getStyle('A1:A3')->getFont()->setBold(true);

                // Bold the last row (subtotal)
                $lastRow = $sheet->getHighestRow();
                $lastCol = $sheet->getHighestColumn();
                $sheet->getStyle("A{$lastRow}:{$lastCol}{$lastRow}")->getFont()->setBold(true);
            },
        ];
    }
}
