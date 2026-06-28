<?php

namespace App\Exports;

use App\Models\PurchaseOrder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PurchaseOrderSupplierPeriodExport implements FromArray, WithEvents, WithTitle
{
    protected Collection $pos;

    protected int $dataRowCount = 0;

    public function __construct(
        protected string $from,
        protected string $to,
        protected ?int $cvId = null,
        protected ?int $supplierId = null,
        protected ?int $tujuanId = null,
    ) {
        $this->pos = $this->loadPurchaseOrders();
    }

    public function array(): array
    {
        $this->dataRowCount = 0;

        $rows = [
            ['REKAPITULASI PENGIRIMAN PAKAN'],
            ['PT. SURYA UNGGAS MANDIRI'],
            ['UNIT JAMBI'],
            ['Periode : ' . $this->formatPeriode()],
            [],
            [
                'No',
                'Tanggal',
                'No. Mobil',
                'Kode Pakan',
                'No. DO',
                'Tujuan',
                'Cost Center',
                'Jumlah (Kg)',
                'Bag',
                'Ongkos',
                'Total Ongkos',
                'DP',
                'Bayar OA',
                'Sisa',
                'Supplier',
                'Bukti Bayar',
            ],
        ];

        $no = 1;
        $grandTotalKg = 0;
        $grandTotalKarung = 0;
        $grandTotalOngkos = 0;
        $grandTotalDp = 0;
        $grandTotalBayarOa = 0;
        $grandTotalSisa = 0;

        foreach ($this->pos as $po) {
            foreach ($po->kendaraans->sortBy('no_polisi') as $kendaraan) {
                $totalDpKendaraan = (float) $kendaraan->oaPayments
                    ->where('tipe_pembayaran', 'dp_supplier')
                    ->sum('jumlah_bayar');
                $totalBayarOaKendaraan = (float) $kendaraan->oaPayments
                    ->where('tipe_pembayaran', 'oa')
                    ->sum('jumlah_bayar');
                $buktiBayarList = $kendaraan->oaPayments->pluck('bukti_bayar')->filter()->unique()->values();

                $totalOngkosKendaraan = 0;
                foreach ($kendaraan->penerimas as $penerima) {
                    $totalOngkosKendaraan += (float) $penerima->pakans->sum(
                        fn($pakan) => (float) $pakan->jumlah_kg * (float) ($pakan->ongkos_oa ?? 0)
                    );
                }

                $sisaKendaraan = max(0, $totalOngkosKendaraan - $totalDpKendaraan - $totalBayarOaKendaraan);
                $grandTotalDp += $totalDpKendaraan;
                $grandTotalBayarOa += $totalBayarOaKendaraan;
                $grandTotalSisa += $sisaKendaraan;

                $isFirstPenerima = true;
                foreach ($kendaraan->penerimas as $penerima) {
                    $totalKgPenerima = (float) $penerima->pakans->sum('jumlah_kg');
                    $totalKarungPenerima = (int) $penerima->pakans->sum(
                        fn($pakan) => (int) ($pakan->jumlah_karung ?? 0)
                    );
                    $totalOngkosPenerima = (float) $penerima->pakans->sum(
                        fn($pakan) => (float) $pakan->jumlah_kg * (float) ($pakan->ongkos_oa ?? 0)
                    );
                    $ongkosPerKg = $totalKgPenerima > 0 ? $totalOngkosPenerima / $totalKgPenerima : 0;
                    $kodePakanStr = $penerima->pakans
                        ->map(fn($pakan) => $pakan->kodePakan?->kode)
                        ->filter()
                        ->unique()
                        ->values()
                        ->implode(', ');

                    $rows[] = [
                        $isFirstPenerima ? $no++ : '',
                        $isFirstPenerima ? $po->tanggal_po?->translatedFormat('d F Y') : '',
                        $isFirstPenerima ? $kendaraan->no_polisi : '',
                        $kodePakanStr !== '' ? $kodePakanStr : '-',
                        $penerima->no_do ?? '-',
                        strtoupper($penerima->nama_penerima ?? '-'),
                        strtoupper($penerima->tujuan?->nama ?? '-'),
                        $totalKgPenerima > 0 ? $totalKgPenerima : null,
                        $totalKarungPenerima > 0 ? $totalKarungPenerima : null,
                        $ongkosPerKg > 0 ? $ongkosPerKg : null,
                        $totalOngkosPenerima > 0 ? $totalOngkosPenerima : null,
                        $isFirstPenerima && $totalDpKendaraan > 0 ? $totalDpKendaraan : null,
                        $isFirstPenerima && $totalBayarOaKendaraan > 0 ? $totalBayarOaKendaraan : null,
                        $isFirstPenerima ? ($sisaKendaraan > 0 ? $sisaKendaraan : 'Lunas') : '',
                        $isFirstPenerima ? ($kendaraan->supplier?->nama ?? '-') : '',
                        $isFirstPenerima ? ($buktiBayarList->isNotEmpty() ? $buktiBayarList->implode(', ') : '-') : '',
                    ];

                    $this->dataRowCount++;
                    $grandTotalKg += $totalKgPenerima;
                    $grandTotalKarung += $totalKarungPenerima;
                    $grandTotalOngkos += $totalOngkosPenerima;
                    $isFirstPenerima = false;
                }
            }
        }

        $rows[] = [
            'TOTAL',
            '',
            '',
            '',
            '',
            '',
            '',
            $grandTotalKg,
            $grandTotalKarung,
            '',
            $grandTotalOngkos,
            $grandTotalDp > 0 ? $grandTotalDp : null,
            $grandTotalBayarOa > 0 ? $grandTotalBayarOa : null,
            $grandTotalSisa > 0 ? $grandTotalSisa : null,
            '',
            '',
        ];
        $rows[] = [];
       
        return $rows;
    }

    public function title(): string
    {
        return 'Rekap Supplier';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $headerRow = 5;
                $dataStartRow = 6;
                $dataEndRow = $dataStartRow + max($this->dataRowCount - 1, 0);
                $totalRow = $dataStartRow + $this->dataRowCount;

                $sheet->mergeCells('A1:P1');
                $sheet->mergeCells('A2:P2');
                $sheet->mergeCells('A3:P3');
                $sheet->mergeCells('A4:P4');
                $sheet->getStyle('A1:A4')->getFont()->setBold(true)->setSize(13);
                $sheet->getStyle('A1:A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("A{$headerRow}:P{$headerRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF7B7BEF']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                if ($this->dataRowCount > 0) {
                    $sheet->getStyle("A{$dataStartRow}:P{$dataEndRow}")->applyFromArray([
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                            'wrapText' => true,
                        ],
                    ]);
                }

                $sheet->getStyle("A{$headerRow}:P{$totalRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                $sheet->mergeCells("A{$totalRow}:G{$totalRow}");
                $sheet->getStyle("A{$totalRow}:P{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE5E7EB']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle("H{$dataStartRow}:N{$totalRow}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');

                $widths = [
                    'A' => 6,
                    'B' => 18,
                    'C' => 16,
                    'D' => 16,
                    'E' => 16,
                    'F' => 22,
                    'G' => 20,
                    'H' => 14,
                    'I' => 10,
                    'J' => 12,
                    'K' => 16,
                    'L' => 14,
                    'M' => 14,
                    'N' => 14,
                    'O' => 22,
                    'P' => 22,
                ];

                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->freezePane('A7');
            },
        ];
    }

    private function loadPurchaseOrders(): Collection
    {
        $query = PurchaseOrder::with([
            'cv',
            'kendaraans' => function ($q) {
                $q->where('status', '!=', 'batal');
            },
            'kendaraans.supplier',
            'kendaraans.oaPayments',
            'kendaraans.penerimas.pakans.kodePakan',
            'kendaraans.penerimas.tujuan',
        ])->orderBy('tanggal_po', 'asc')->orderBy('no_po', 'asc');

        $query->whereDate('tanggal_po', '>=', $this->from)
            ->whereDate('tanggal_po', '<=', $this->to);

        if ($this->cvId) {
            $query->where('cv_id', $this->cvId);
        }

        if ($this->supplierId) {
            $query->whereHas('kendaraans', fn($q) => $q->where('supplier_id', $this->supplierId));
        }

        if ($this->tujuanId) {
            $query->whereHas('kendaraans.penerimas', fn($q) => $q->where('tujuan_id', $this->tujuanId));
        }

        $pos = $query->get();

        if ($this->supplierId || $this->tujuanId) {
            foreach ($pos as $po) {
                if ($this->supplierId) {
                    $po->setRelation('kendaraans', $po->kendaraans->filter(
                        fn($kendaraan) => (int) $kendaraan->supplier_id === (int) $this->supplierId
                    )->values());
                }

                if ($this->tujuanId) {
                    foreach ($po->kendaraans as $kendaraan) {
                        $kendaraan->setRelation('penerimas', $kendaraan->penerimas->filter(
                            fn($penerima) => (int) $penerima->tujuan_id === (int) $this->tujuanId
                        )->values());
                    }

                    $po->setRelation('kendaraans', $po->kendaraans->filter(
                        fn($kendaraan) => $kendaraan->penerimas->isNotEmpty()
                    )->values());
                }
            }

            $pos = $pos->filter(fn($po) => $po->kendaraans->isNotEmpty())->values();
        }

        return $pos;
    }

    private function formatPeriode(): string
    {
        $from = Carbon::parse($this->from)->locale('id');
        $to = Carbon::parse($this->to)->locale('id');

        if ($from->isSameDay($to)) {
            return $from->translatedFormat('j F Y');
        }

        if ($from->month === $to->month && $from->year === $to->year) {
            return $from->day . ' - ' . $to->day . ' ' . $from->translatedFormat('F Y');
        }

        return $from->translatedFormat('j F Y') . ' - ' . $to->translatedFormat('j F Y');
    }
}
