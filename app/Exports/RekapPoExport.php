<?php

namespace App\Exports;

use App\Models\PurchaseOrder;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RekapPoExport implements WithMultipleSheets
{
    protected PurchaseOrder $po;

    public function __construct(PurchaseOrder $po)
    {
        $this->po = $po;
    }

    public function sheets(): array
    {
        return [
            new RekapPoOaSheet($this->po),
            new RekapPoPtSumSheet($this->po),
        ];
    }
}

class RekapPoOaSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle, WithEvents
{
    protected PurchaseOrder $po;

    public function __construct(PurchaseOrder $po)
    {
        $this->po = $po;
    }

    public function array(): array
    {
        $rows = [['#', 'Kendaraan', 'Nama Penerima', 'Total KG', 'Total OA (Rp)']];

        $grandTotal = 0;
        $no = 1;
        foreach ($this->po->kendaraans as $kendaraan) {
            foreach ($kendaraan->penerimas as $penerima) {
                $totalOa = $penerima->total_oa;
                $grandTotal += $totalOa;
                $rows[] = [
                    $no++,
                    $kendaraan->no_polisi,
                    $penerima->nama_penerima,
                    $penerima->total_kg,
                    $totalOa,
                ];
            }
        }

        $rows[] = ['', '', 'GRAND TOTAL', '', $grandTotal];

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
        return 'Rekap Supplier (OA)';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle("A{$lastRow}:E{$lastRow}")->getFont()->setBold(true);
            },
        ];
    }
}

class RekapPoPtSumSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle, WithEvents
{
    protected PurchaseOrder $po;

    public function __construct(PurchaseOrder $po)
    {
        $this->po = $po;
    }

    public function array(): array
    {
        $rows = [['#', 'Kendaraan', 'Nama Penerima', 'Total KG', 'Total PT SUM (Rp)']];

        $grandTotal = 0;
        $no = 1;
        foreach ($this->po->kendaraans as $kendaraan) {
            foreach ($kendaraan->penerimas as $penerima) {
                $totalPtSum = $penerima->total_pt_sum;
                $grandTotal += $totalPtSum;
                $rows[] = [
                    $no++,
                    $kendaraan->no_polisi,
                    $penerima->nama_penerima,
                    $penerima->total_kg,
                    $totalPtSum,
                ];
            }
        }

        $rows[] = ['', '', 'GRAND TOTAL', '', $grandTotal];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF16A34A']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function title(): string
    {
        return 'Rekap PT SUM';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle("A{$lastRow}:E{$lastRow}")->getFont()->setBold(true);
            },
        ];
    }
}
