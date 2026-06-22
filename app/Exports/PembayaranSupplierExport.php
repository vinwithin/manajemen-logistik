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

class PembayaranSupplierExport implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    public function __construct(
        private Collection $kendaraans,
        private string $from,
        private string $to
    ) {}

    public function array(): array
    {
        $rows = [[
            'No', 'Tanggal PO', 'No. PO', 'No. Mobil', 'Supplier', 'Tujuan',
            'Jumlah (Kg)', 'Total Tagihan', 'DP Supplier', 'Bayar OA', 'Total Bayar', 'Sisa', 'Status',
        ]];

        foreach ($this->kendaraans as $index => $kendaraan) {
            $dp = (float) $kendaraan->oaPayments->where('tipe_pembayaran', 'dp_supplier')->sum('jumlah_bayar');
            $oa = (float) $kendaraan->oaPayments->where('tipe_pembayaran', 'oa')->sum('jumlah_bayar');
            $tagihan = (float) $kendaraan->total_tagihan_supplier;
            $totalBayar = $dp + $oa;
            $tujuan = $kendaraan->penerimas->map(fn($p) => $p->tujuan?->nama)->filter()->unique()->implode(', ');

            $rows[] = [
                $index + 1,
                optional($kendaraan->po?->tanggal_po)->format('d/m/Y'),
                $kendaraan->po?->no_po,
                $kendaraan->no_polisi,
                $kendaraan->supplier?->nama,
                $tujuan,
                (float) $kendaraan->penerimas->sum(fn($p) => $p->pakans->sum('jumlah_kg')),
                $tagihan,
                $dp,
                $oa,
                $totalBayar,
                max(0, $tagihan - $totalBayar),
                $totalBayar <= 0 ? 'Belum Bayar' : ($totalBayar >= $tagihan ? 'Lunas' : 'Bayar Sebagian'),
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Pembayaran Supplier';
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event) {
            $lastRow = max(1, $this->kendaraans->count() + 1);
            $sheet = $event->sheet->getDelegate();
            $sheet->freezePane('A2')->setAutoFilter("A1:M{$lastRow}");
            $sheet->getStyle('A1:M1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle('A1:M1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF7B7BEF');
            $sheet->getStyle("A1:M{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("A1:M{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("G2:L{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
        }];
    }
}
