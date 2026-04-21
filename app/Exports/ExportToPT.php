<?php

namespace App\Exports;

use App\Models\PurchaseOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExportToPT implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithTitle,
    ShouldAutoSize,
    WithEvents
{
    protected PurchaseOrder $po;

    public function __construct(PurchaseOrder $po)
    {
        $this->po = $po;
    }

    public function collection()
    {
        return $this->po->items()
            ->with(['tujuan', 'supplier'])
            ->orderBy('id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'No. Polisi',
            'Muatan',
            'Tujuan',
            'Nama Supir',
            'HP Supir',
            'Nama Penerima',
            'Supplier',
        ];
    }

    public function map($item): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $item->no_polisi,
            $item->berat ?? 0,
            $item->tujuan?->nama ?? '-',
            $item->nama_supir ?? '-',
            $item->hp_supir ?? '-',
            $item->penerimaList->pluck('nama')->implode(',') ?? '-',
            $item->supplier?->initial ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2563EB']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function title(): string
    {
        return 'PO ' . $this->po->no_po;
    }

    public function registerEvents(): array
    {
        $po = $this->po;

        return [
            AfterSheet::class => function (AfterSheet $event) use ($po) {
                $sheet = $event->sheet->getDelegate();

                // Sisipkan 3 baris info di atas sebelum header
                $sheet->insertNewRowBefore(1, 3);

                $sheet->setCellValue('A1', 'No. PO');
                $sheet->setCellValue('B1', $po->no_po);
                $sheet->setCellValue('A2', 'Tanggal');
                $sheet->setCellValue('B2', $po->tanggal_po->format('d/m/Y'));
                $sheet->setCellValue('A3', 'CV');
                $sheet->setCellValue('B3', $po->cv?->nama_cv ?? '-');

                $sheet->getStyle('A1:A3')->getFont()->setBold(true);
            },
        ];
    }
}
