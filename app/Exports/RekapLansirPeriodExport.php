<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RekapLansirPeriodExport implements FromArray, ShouldAutoSize, WithEvents, WithStyles, WithTitle
{
    private const HEADINGS = [
        'Tanggal',
        'No. PO',
        'Kendaraan PO',
        'Penerima',
        'Pelaksana',
        'Berat (kg)',
        'Jumlah Karung',
        'Tarif (Rp/kg)',
        'Total (Rp)',
        'Status Bayar',
    ];

    private int $mobilHeaderRow = 4;

    private int $timHeaderRow;

    private int $mobilTotalRow;

    private int $timTotalRow;

    public function __construct(
        private Collection $mobilRows,
        private Collection $timRows,
        private string $from,
        private string $to,
    ) {}

    public function array(): array
    {
        $rows = [
            ['REKAP LANSIR'],
            ['Periode', "{$this->from} s.d. {$this->to}"],
            [''],
            self::HEADINGS,
        ];

        foreach ($this->mobilRows as $row) {
            $rows[] = $this->mapRow($row);
        }

        $this->mobilTotalRow = count($rows) + 1;
        $rows[] = $this->totalRow($this->mobilRows);
        $rows[] = [''];
        $rows[] = ['TIM BONGKAR'];
        $this->timHeaderRow = count($rows) + 1;
        $rows[] = self::HEADINGS;

        foreach ($this->timRows as $row) {
            $rows[] = $this->mapRow($row);
        }

        $this->timTotalRow = count($rows) + 1;
        $rows[] = $this->totalRow($this->timRows);

        return $rows;
    }

    private function mapRow(array $row): array
    {
        return [
            $row['tanggal'],
            $row['no_po'],
            $row['kendaraan_po'],
            $row['penerima'],
            $row['pelaksana'],
            $row['berat'],
            $row['karung'],
            $row['tarif'],
            $row['total'],
            $row['status_bayar'],
        ];
    }

    private function totalRow(Collection $rows): array
    {
        return [
            'GRAND TOTAL',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            $rows->sum('total'),
            '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->freezePane('A5');
                $sheet->getStyle("A{$this->mobilHeaderRow}:J{$this->mobilHeaderRow}")
                    ->applyFromArray($this->headerStyle());
                $sheet->getStyle("A{$this->timHeaderRow}:J{$this->timHeaderRow}")
                    ->applyFromArray($this->headerStyle());
                $sheet->getStyle("A{$this->mobilTotalRow}:J{$this->mobilTotalRow}")
                    ->applyFromArray($this->totalStyle());
                $sheet->getStyle("A{$this->timTotalRow}:J{$this->timTotalRow}")
                    ->applyFromArray($this->totalStyle());
                $sheet->getStyle('A'.($this->timHeaderRow - 1).':J'.($this->timHeaderRow - 1))
                    ->applyFromArray([
                        'font' => ['bold' => true, 'size' => 12],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FFE5E7EB'],
                        ],
                    ]);
            },
        ];
    }

    private function headerStyle(): array
    {
        return [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF2563EB'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
    }

    private function totalStyle(): array
    {
        return [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFD1FAE5'],
            ],
        ];
    }

    public function title(): string
    {
        return 'Rekap Lansir';
    }
}
