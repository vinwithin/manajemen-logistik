<?php

namespace App\Exports;

use App\Models\Cv;
use App\Models\PurchaseOrder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PurchaseOrderPtSumRekapSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    protected int $dataRowCount = 0;

    public function __construct(
        protected Collection $pos,
        protected string $from,
        protected string $to,
        protected ?Cv $cv,
        protected ?string $noSurat,
        protected ?string $tanggalSurat,
    ) {}

    public function array(): array
    {
        $this->dataRowCount = 0;

        $rows = [
            ['REKAPITULASI PENGIRIMAN PAKAN'],
            ['PT. SURYA UNGGAS MANDIRI'],
            ['UNIT JAMBI'],
            ['Periode', $this->formatPeriode()],
            ['No.', $this->noKwitansi()],
            [],
            ['No', 'Tanggal', 'Kode Pakan', 'No. DO', 'No. Mobil', 'Tanggal Bongkar', 'Tujuan Bongkar', 'Jumlah (Kg)', 'Jumlah (Bag)', 'Ongkos', 'Total Ongkos'],
        ];

        $no = 1;
        $grandTotalKg = 0;
        $grandTotalKarung = 0;
        $grandTotalHarga = 0;

        foreach ($this->pos as $po) {
            foreach ($po->kendaraans->sortBy('no_polisi') as $kendaraan) {
                foreach ($kendaraan->penerimas as $penerima) {
                    foreach ($penerima->pakans as $pakan) {
                        $totalKg = (float) $pakan->jumlah_kg;
                        $totalKarung = (int) ($pakan->jumlah_karung ?? 0);
                        $hargaPtSum = (float) ($pakan->harga_pt_sum ?? 0);
                        $totalHarga = $totalKg * $hargaPtSum;

                        $rows[] = [
                            $no++,
                            $po->tanggal_po?->translatedFormat('d F Y'),
                            $pakan->kodePakan?->kode ?? '-',
                            $penerima->no_do,
                            $kendaraan->no_polisi,
                            $penerima->tiba_at?->translatedFormat('d F Y') ?? '-',
                            $this->namaTujuan($penerima),
                            $totalKg > 0 ? $totalKg : null,
                            $totalKarung > 0 ? $totalKarung : null,
                            $hargaPtSum > 0 ? $hargaPtSum : null,
                            $totalHarga > 0 ? $totalHarga : null,
                        ];

                        $this->dataRowCount++;
                        $grandTotalKg += $totalKg;
                        $grandTotalKarung += $totalKarung;
                        $grandTotalHarga += $totalHarga;
                    }
                }
            }
        }

        $rows[] = ['TOTAL', '', '', '', '', '', '', $grandTotalKg, $grandTotalKarung, '', $grandTotalHarga];
        $rows[] = [];
        $rows[] = [];
        $rows[] = ['Diverifikasi Oleh,', '', '', 'Diketahui Oleh,', '', '', $this->tanggalSurat ?: 'Jambi, ' . now()->translatedFormat('d F Y')];
        $rows[] = ['Finance Accounting', '', '', 'Branch Manager'];
        $rows[] = [];
        $rows[] = [];
        $rows[] = [];
        $rows[] = ['________________', '', '', '________________', '', '', '________________'];

        return $rows;
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
                $headerRow = 6;
                $dataStartRow = 7;
                $dataEndRow = $dataStartRow + max($this->dataRowCount - 1, 0);
                $totalRow = $dataStartRow + $this->dataRowCount;
                $signatureStartRow = $totalRow + 2;

                $sheet->mergeCells('A1:K1');
                $sheet->mergeCells('A2:K2');
                $sheet->mergeCells('A3:K3');
                $sheet->getStyle('A1:A3')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1:A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A4:A5')->getFont()->setBold(true);
                $sheet->getStyle('B4:K5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                $sheet->getStyle("A{$headerRow}:K{$headerRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF7B7BEF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                if ($this->dataRowCount > 0) {
                    $sheet->getStyle("A{$dataStartRow}:K{$dataEndRow}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFFFF']],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                            'wrapText' => true,
                        ],
                    ]);
                }

                $sheet->getStyle("A{$headerRow}:K{$totalRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                $sheet->mergeCells("A{$totalRow}:G{$totalRow}");
                $sheet->getStyle("A{$totalRow}:K{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE5E7EB']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle("H{$dataStartRow}:K{$totalRow}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');

                $sheet->getStyle("A{$headerRow}:K{$totalRow}")
                    ->getAlignment()
                    ->setWrapText(true);

                $sheet->getStyle("A{$signatureStartRow}:K" . ($signatureStartRow + 5))
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_TOP);

                $sheet->getColumnDimension('A')->setWidth(6);
                $sheet->getColumnDimension('B')->setWidth(18);
                $sheet->getColumnDimension('C')->setWidth(14);
                $sheet->getColumnDimension('D')->setWidth(16);
                $sheet->getColumnDimension('E')->setWidth(16);
                $sheet->getColumnDimension('F')->setWidth(18);
                $sheet->getColumnDimension('G')->setWidth(20);
                $sheet->getColumnDimension('H')->setWidth(14);
                $sheet->getColumnDimension('I')->setWidth(14);
                $sheet->getColumnDimension('J')->setWidth(12);
                $sheet->getColumnDimension('K')->setWidth(16);

                $sheet->freezePane('A8');
            },
        ];
    }

    private function namaTujuan($penerima): string
    {
        if (in_array($penerima->tujuan?->type, ['direct', 'tr_kerinci'])) {
            return strtoupper($penerima->nama_penerima ?? '-');
        }

        return strtoupper($penerima->tujuan?->nama ?? '-');
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

    private function noKwitansi(): string
    {
        $prefix = $this->cv?->no_dokumen_prefix ?? '';

        return $this->noSurat ?: ($prefix ? $prefix . '/' . now()->format('III/Y') : now()->format('Y/m'));
    }
}

class PurchaseOrderPtSumPeriodExport extends PurchaseOrderPtSumRekapSheet
{
    public function __construct(
        string $from,
        string $to,
        ?int $cvId = null,
        ?int $supplierId = null,
        array $tujuanIds = [],
        array $kendaraanIds = [],
        ?string $noSurat = null,
        ?string $tujuanNama = null,
        ?string $tanggalSurat = null,
    ) {
        $pos = $this->loadPurchaseOrders($from, $to, $cvId, $supplierId, $tujuanIds, $kendaraanIds);
        $cv = $pos->first()?->cv ?? ($cvId ? Cv::find($cvId) : null);

        parent::__construct($pos, $from, $to, $cv, $noSurat, $tanggalSurat);
    }

    private function loadPurchaseOrders(
        string $from,
        string $to,
        ?int $cvId,
        ?int $supplierId,
        array $tujuanIds,
        array $kendaraanIds,
    ): Collection {
        $query = PurchaseOrder::with([
            'cv',
            'kendaraans' => function ($q) {
                $q->where('status', '!=', 'batal');
            },
            'kendaraans.supplier',
            'kendaraans.penerimas.pakans.kodePakan',
            'kendaraans.penerimas.tujuan',
        ])->where('status', '!=', 'batal')
            ->whereDate('tanggal_po', '>=', $from)
            ->whereDate('tanggal_po', '<=', $to)
            ->orderBy('tanggal_po', 'asc')
            ->orderBy('no_po', 'asc');

        if ($cvId) {
            $query->where('cv_id', $cvId);
        }

        if ($supplierId) {
            $query->whereHas('kendaraans', fn($q) => $q->where('supplier_id', $supplierId));
        }

        if (! empty($tujuanIds)) {
            $query->whereHas('kendaraans.penerimas', fn($q) => $q->whereIn('tujuan_id', $tujuanIds));
        }

        if (! empty($kendaraanIds)) {
            $query->whereHas('kendaraans', fn($q) => $q->whereIn('id', $kendaraanIds));
        }

        $pos = $query->get();

        if (! empty($tujuanIds)) {
            foreach ($pos as $po) {
                foreach ($po->kendaraans as $kendaraan) {
                    $kendaraan->setRelation('penerimas', $kendaraan->penerimas->filter(
                        fn($penerima) => in_array($penerima->tujuan_id, $tujuanIds)
                    )->values());
                }

                $po->setRelation('kendaraans', $po->kendaraans->filter(
                    fn($kendaraan) => $kendaraan->penerimas->isNotEmpty()
                )->values());
            }

            $pos = $pos->filter(fn($po) => $po->kendaraans->isNotEmpty())->values();
        }

        if (! empty($kendaraanIds)) {
            foreach ($pos as $po) {
                $po->setRelation('kendaraans', $po->kendaraans->filter(
                    fn($kendaraan) => in_array($kendaraan->id, $kendaraanIds)
                )->values());
            }

            $pos = $pos->filter(fn($po) => $po->kendaraans->isNotEmpty())->values();
        }

        return $pos;
    }
}
