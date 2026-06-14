<?php

namespace App\Exports;

use App\Models\LansirPayment;
use App\Models\PurchaseOrder;
use Illuminate\Support\Collection;
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

class RekapLansirExport implements WithMultipleSheets
{
    public function __construct(
        private PurchaseOrder $po,
        private Collection $rekap,
    ) {}

    public function sheets(): array
    {
        return [
            new RekapLansirMobilSheet($this->po, $this->rekap),
            new RekapLansirTimSheet($this->po, $this->rekap),
        ];
    }
}

abstract class RekapLansirSheet implements FromArray, ShouldAutoSize, WithEvents, WithStyles, WithTitle
{
    protected int $columnCount;

    public function __construct(
        protected PurchaseOrder $po,
        protected Collection $rekap,
    ) {}

    abstract protected function headings(): array;

    abstract protected function detailRows(): array;

    abstract protected function paymentType(): string;

    public function array(): array
    {
        $this->columnCount = count($this->headings());
        $rows = [
            ['No. PO', $this->po->no_po],
            ['Tanggal PO', $this->po->tanggal_po?->format('d/m/Y') ?? '-'],
            ['CV', $this->po->cv?->nama_cv ?? '-'],
            [],
            $this->headings(),
        ];

        $details = $this->detailRows();
        $rows = array_merge($rows, $details);
        $total = collect($details)->sum(fn (array $row) => (float) end($row));
        $totalRow = array_fill(0, $this->columnCount, '');
        $totalRow[0] = 'GRAND TOTAL';
        $totalRow[$this->columnCount - 1] = $total;
        $rows[] = $totalRow;

        $payment = $this->po->lansirPayments
            ->firstWhere('tipe', $this->paymentType());
        $status = $payment?->status === LansirPayment::STATUS_SUDAH
            ? 'Sudah Bayar'
            : 'Belum Bayar';
        $rows[] = ['Status Pembayaran', $status];

        if ($payment?->tanggal_bayar) {
            $rows[] = [
                'Tanggal Bayar',
                $payment->tanggal_bayar->format('d/m/Y'),
                'Dibayar Oleh',
                $payment->dibayar_oleh ?? '-',
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
            2 => ['font' => ['bold' => true]],
            3 => ['font' => ['bold' => true]],
            5 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF2563EB'],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();
                $lastRow = $sheet->getHighestRow();
                $grandTotalRow = 6 + count($this->detailRows());

                $sheet->setAutoFilter("A5:{$lastColumn}5");
                $sheet->freezePane('A6');
                $sheet->getStyle("A{$grandTotalRow}:{$lastColumn}{$grandTotalRow}")
                    ->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FFD1FAE5'],
                        ],
                    ]);
                $sheet->getStyle("A1:{$lastColumn}{$lastRow}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);
            },
        ];
    }
}

class RekapLansirMobilSheet extends RekapLansirSheet
{
    protected function headings(): array
    {
        return [
            'No',
            'Kendaraan PO',
            'Sopir PO',
            'Penerima',
            'Tujuan',
            'Tanggal Lansir',
            'Mobil Lansir',
            'Sopir Lansir',
            'Berat (kg)',
            'Jumlah Karung',
            'Ongkos (Rp/kg)',
            'Total Ongkos (Rp)',
        ];
    }

    protected function detailRows(): array
    {
        $rows = [];
        $no = 1;

        foreach ($this->rekap as $lansir) {
            foreach ($lansir->mobils as $mobil) {
                $rows[] = [
                    $no++,
                    $lansir->penerima?->kendaraan?->no_polisi ?? '-',
                    $lansir->penerima?->kendaraan?->nama_sopir ?? '-',
                    $lansir->penerima?->nama_penerima ?? '-',
                    $lansir->penerima?->tujuan?->nama ?? '-',
                    $lansir->tanggal_lansir?->format('d/m/Y') ?? '-',
                    $mobil->no_polisi ?? '-',
                    $mobil->nama_sopir ?? '-',
                    (float) ($mobil->berat ?? 0),
                    (int) ($mobil->jumlah_karung ?? 0),
                    (float) ($mobil->ongkos ?? 0),
                    (float) ($mobil->berat ?? 0) * (float) ($mobil->ongkos ?? 0),
                ];
            }
        }

        return $rows;
    }

    protected function paymentType(): string
    {
        return LansirPayment::TIPE_MOBIL;
    }

    public function title(): string
    {
        return 'Mobil Lansir';
    }
}

class RekapLansirTimSheet extends RekapLansirSheet
{
    protected function headings(): array
    {
        return [
            'No',
            'Kendaraan PO',
            'Sopir PO',
            'Penerima',
            'Tujuan',
            'Tanggal Lansir',
            'Nama Tim',
            'Berat (kg)',
            'Jumlah Karung',
            'Upah (Rp/kg)',
            'Total Upah (Rp)',
        ];
    }

    protected function detailRows(): array
    {
        $rows = [];
        $no = 1;

        foreach ($this->rekap as $lansir) {
            foreach ($lansir->tims as $tim) {
                $rows[] = [
                    $no++,
                    $lansir->penerima?->kendaraan?->no_polisi ?? '-',
                    $lansir->penerima?->kendaraan?->nama_sopir ?? '-',
                    $lansir->penerima?->nama_penerima ?? '-',
                    $lansir->penerima?->tujuan?->nama ?? '-',
                    $lansir->tanggal_lansir?->format('d/m/Y') ?? '-',
                    $tim->nama_tim ?? '-',
                    (float) ($tim->berat ?? 0),
                    (int) ($tim->jumlah_karung ?? 0),
                    (float) ($tim->upah ?? 0),
                    (float) ($tim->berat ?? 0) * (float) ($tim->upah ?? 0),
                ];
            }
        }

        return $rows;
    }

    protected function paymentType(): string
    {
        return LansirPayment::TIPE_TIM;
    }

    public function title(): string
    {
        return 'Tim Bongkar';
    }
}
