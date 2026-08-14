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

class RekapPtSumLansirExport implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    public function __construct(
        private Collection $rows,
        private string $from,
        private string $to,
    ) {}

    public function array(): array
    {
        $rows = [
            ['REKAP PT SUM LANSIR'],
            ['Periode', date('d/m/Y', strtotime($this->from)) . ' - ' . date('d/m/Y', strtotime($this->to))],
            [],
            ['No', 'Tipe', 'No. Referensi', 'Tanggal', 'CV', 'Tujuan', 'Kendaraan', 'Total KG', 'Total PT Sum', 'Status'],
        ];

        $no = 1;
        $totalKg = 0;
        $totalPtSum = 0;

        foreach ($this->rows as $row) {
            $totalKg += $row['total_kg'];
            $totalPtSum += $row['total_pt_sum'];

            $rows[] = [
                $no++,
                $row['tipe'],
                $row['no_referensi'],
                $row['tanggal'],
                $row['cv_name'],
                $row['tujuan'],
                $row['jumlah_kendaraan'],
                $row['total_kg'],
                $row['total_pt_sum'],
                $row['status_label'],
            ];
        }

        $rows[] = ['TOTAL', '', '', '', '', '', '', $totalKg, $totalPtSum, ''];

        return $rows;
    }

    public function title(): string
    {
        return 'Rekap PT Sum Lansir';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                $sheet->mergeCells('A1:J1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A2:B2')->getFont()->setBold(true);
                $sheet->getStyle('A3:J3')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2563EB']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle("A4:J{$lastRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("H5:I{$lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');
                $sheet->getStyle("A{$lastRow}:J{$lastRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE5E7EB']],
                ]);
                $sheet->getStyle("A4:J{$lastRow}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);
            },
        ];
    }
}
