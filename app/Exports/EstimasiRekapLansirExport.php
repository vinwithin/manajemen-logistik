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

class EstimasiRekapLansirExport implements FromArray, ShouldAutoSize, WithEvents, WithStyles, WithTitle
{
    private const MOBIL_HEADINGS = [
        'Tanggal PO',
        'Estimasi Tiba',
        'No. PO',
        'Kendaraan PO',
        'Tujuan',
        'Penerima',
        'Berat (kg)',
        'Jumlah Karung',
        'Tarif (Rp/kg)',
        'Total (Rp)',
    ];

    private const TIM_HEADINGS = [
        'Tanggal PO',
        'Estimasi Tiba',
        'No. PO',
        'Kendaraan PO',
        'Tujuan',
        'Penerima',
        'Berat (kg)',
        'Jumlah Karung',
        'Tarif (Rp/kg)',
        'Total (Rp)',
    ];

    private int $mobilHeaderRow = 4;

    private int $timHeaderRow;

    private int $mobilTotalRow;

    private int $timTotalRow;

    public function __construct(
        private Collection $penerimas,
        private string $from,
        private string $to,
    ) {}

    public function array(): array
    {
        $mobilRows = $this->penerimas->map(fn($penerima) => $this->mapPenerima($penerima, 'mobil'));
        $timRows = $this->penerimas->map(fn($penerima) => $this->mapPenerima($penerima, 'tim'));

        $rows = [
            ['ESTIMASI LANSIR & BONGKAR'],
            ['Periode', "{$this->from} s.d. {$this->to}"],
            [''],
            self::MOBIL_HEADINGS,
        ];

        foreach ($mobilRows as $row) {
            $rows[] = $this->mapRow($row);
        }

        $this->mobilTotalRow = count($rows) + 1;
        $rows[] = $this->totalRow($mobilRows);
        $rows[] = [''];
        $rows[] = ['TIM BONGKAR'];
        $this->timHeaderRow = count($rows) + 1;
        $rows[] = self::TIM_HEADINGS;

        foreach ($timRows as $row) {
            $rows[] = $this->mapRow($row);
        }

        $this->timTotalRow = count($rows) + 1;
        $rows[] = $this->totalRow($timRows);

        return $rows;
    }

    private function mapPenerima($penerima, string $tipe): array
    {
        $berat = (float) $penerima->pakans->sum('jumlah_kg');
        $karung = (int) $penerima->pakans->sum('jumlah_karung');
        $masterPenerima = $penerima->penerima;
        $tarif = $tipe === 'tim'
            ? (float) ($masterPenerima?->ongkos_bongkar ?? 0)
            : (float) ($masterPenerima?->ongkos_angkut ?? 0);

        return [
            'tanggal' => $penerima->estimasi_tiba?->format('d/m/Y') ?? '-',
            'tanggal_po' => $penerima->kendaraan?->po?->tanggal_po?->format('d/m/Y') ?? '-',
            'no_po' => $penerima->kendaraan?->po?->no_po ?? '-',
            'kendaraan_po' => $penerima->kendaraan?->no_polisi ?? '-',
            'tujuan' => $penerima->tujuan?->nama
                ?? $masterPenerima?->tujuan?->nama
                ?? $penerima->kendaraan?->tujuan?->nama
                ?? '-',
            'penerima' => $penerima->nama_penerima ?? '-',
            'berat' => $berat,
            'karung' => $karung,
            'tarif' => $tarif,
            'total' => $berat * $tarif,
        ];
    }

    private function mapRow(array $row): array
    {
        return [
            $row['tanggal_po'],
            $row['tanggal'],
            $row['no_po'],
            $row['kendaraan_po'],
            $row['tujuan'],
            $row['penerima'],
            $row['berat'],
            $row['karung'],
            $row['tarif'],
            $row['total'],
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
            $rows->sum('berat'),
            $rows->sum('karung'),
            '',
            $rows->sum('total'),
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
        return 'Estimasi Lansir';
    }
}
