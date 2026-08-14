<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class RekapPtSumExport implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    public function __construct(
        private Collection $kendaraans,
        private string $from,
        private string $to,
    ) {}

    public function array(): array
    {
        $rows = [
            ['REKAP PT SUM'],
            ['Periode', date('d/m/Y', strtotime($this->from)) . ' - ' . date('d/m/Y', strtotime($this->to))],
            [],
            ['No', 'No. PO', 'Tanggal', 'CV', 'No. Polisi', 'Penerima', 'Supplier', 'Total KG', 'Harga Rata-rata', 'Total PT Sum', 'Status'],
        ];

        $no = 1;
        $totalKg = 0;
        $totalPtSum = 0;

        foreach ($this->kendaraans as $kendaraan) {
            $kg = $kendaraan->total_kg;
            $ptSum = $kendaraan->total_pt_sum;
            $totalKg += $kg;
            $totalPtSum += $ptSum;

            $rows[] = [
                $no++,
                $kendaraan->po?->no_po ?? '-',
                $kendaraan->po?->tanggal_po?->format('d/m/Y') ?? '-',
                $kendaraan->po?->cv?->nama_cv ?? '-',
                $kendaraan->no_polisi,
                $kendaraan->penerimas->pluck('nama_penerima')->join(', '),
                $kendaraan->supplier?->nama ?? '-',
                $kg,
                $kg > 0 ? $ptSum / $kg : 0,
                $ptSum,
                $kendaraan->ptSumPaymentOnly?->status === 'lunas' ? 'Sudah Dibayar' : 'Belum Dibayar',
            ];
        }

        $rows[] = ['TOTAL', '', '', '', '', '', '', $totalKg, '', $totalPtSum, ''];

        return $rows;
    }

    public function title(): string
    {
        return 'Rekap PT Sum';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                $sheet->mergeCells('A1:K1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A2:B2')->getFont()->setBold(true);
                $sheet->getStyle('A3:K3')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2563EB']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle("A4:K{$lastRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("H5:J{$lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');
                $sheet->getStyle("A{$lastRow}:K{$lastRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE5E7EB']],
                ]);
                $sheet->getStyle("A4:K{$lastRow}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);
            },
        ];
    }
}
