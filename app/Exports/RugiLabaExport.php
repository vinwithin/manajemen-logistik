<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class RugiLabaExport implements FromArray, WithEvents, WithTitle
{
    protected array $data;
    protected string $cvNama;
    protected string $periode;

    public function __construct(array $data, string $cvNama, string $periode)
    {
        $this->data    = $data;
        $this->cvNama  = $cvNama;
        $this->periode = $periode;
    }

    public function array(): array
    {
        $d = $this->data;
        $fmt = fn($v) => (float) $v;

        $rows = [];

        // Header
        $rows[] = ['LAPORAN RUGI LABA'];
        $rows[] = [$this->cvNama];
        $rows[] = ['Periode: ' . $this->periode];
        $rows[] = [];

        // A. PEMBELIAN
        $rows[] = ['A.', 'Pembelian', ''];
        $rows[] = ['', '- Gudang',       $fmt($d['pembelian']['gudang'])];
        $rows[] = ['', '- Direct PIR',   $fmt($d['pembelian']['direct'])];
        $rows[] = ['', '- Co Farm',      $fmt($d['pembelian']['co_farm'])];
        $rows[] = ['', '- Rent Farm',    $fmt($d['pembelian']['rent_farm'])];
        $rows[] = ['', '- Tr Kerinci',    $fmt($d['pembelian']['tr_kerinci'])];
        // $rows[] = ['', '- Cab. Bungo',   0];
        $rows[] = ['', '- Transper Pakan', 0];
        $rows[] = ['', 'TOTAL',          $fmt($d['totalPembelian'])];
        $rows[] = [];

        // B. PENJUALAN
        $rows[] = ['B.', 'Penjualan', ''];
        $rows[] = ['', '- Gudang',       $fmt($d['penjualan']['gudang'])];
        $rows[] = ['', '- Direct PIR',   $fmt($d['penjualan']['direct'])];
        $rows[] = ['', '- Co Farm',      $fmt($d['penjualan']['co_farm'])];
        $rows[] = ['', '- Rent Farm',    $fmt($d['penjualan']['rent_farm'])];
        $rows[] = ['', '- Tr Kerinci',    $fmt($d['penjualan']['tr_kerinci'])];
        // $rows[] = ['', '- Cab. Bungo',   0];
        $rows[] = ['', '- Transper Pakan', $fmt($d['penjualan']['transper_pakan'])];
        $rows[] = ['', 'TOTAL',          $fmt($d['totalPenjualan'])];
        $rows[] = [];

        // C. BIAYA OPERASIONAL
        $rows[] = ['C.', 'Biaya Operasional', ''];
        $rows[] = ['', 'GAJI',                      $fmt($d['rl']->gaji)];
        $rows[] = ['', 'ATK',                       $fmt($d['rl']->atk)];
        $rows[] = ['', 'PEMBAYARAN MOBIL LOKAL',     $fmt($d['rl']->pembayaran_mobil_lokal) + $fmt($d['mobilLokalOtomatis'])];
        $rows[] = ['', 'SHARING FEE',               $fmt($d['rl']->sharing_fee)];
        $rows[] = ['', 'SHARING PROFIT',            $fmt($d['rl']->sharing_profit)];
        $rows[] = ['', 'PERJALANAN DINAS',           $fmt($d['rl']->perjalanan_dinas)];
        $rows[] = ['', 'ENTERTAIN',                 $fmt($d['rl']->entertain)];
        $rows[] = ['', 'ADM BANK',                  $fmt($d['rl']->adm_bank)];
        $rows[] = ['', 'UPAH BONGKAR UPAH MUAT',    $fmt($d['rl']->upah_bongkar) + $fmt($d['rl']->upah_muat) + $fmt($d['upahBongkarOtomatis'])];
        $rows[] = ['', 'BIAYA LAIN LAIN',           $fmt($d['rl']->biaya_lain_lain)];
        $rows[] = ['', 'BBM',                       $fmt($d['rl']->bbm)];
        $rows[] = ['', 'LISTRIK',                   $fmt($d['rl']->listrik)];
        $rows[] = ['', 'PDAM',                      $fmt($d['rl']->pdam)];
        $rows[] = ['', 'POTONGAN VOUCHER',          $fmt($d['rl']->potongan_voucher)];
        $rows[] = ['', 'LINGKUNGAN',                $fmt($d['rl']->lingkungan)];
        $rows[] = ['', 'TOTAL',                     $fmt($d['totalBiayaOperasional'])];
        $rows[] = [];

        // D-G
        $rows[] = ['D.', 'LABA KOTOR (B - A)',              $fmt($d['labaKotor'])];
        $rows[] = ['E.', 'Pph 21 (LABA KOTOR X 0.5%)',     $fmt($d['pph21'])];
        $rows[] = ['F.', 'Potongan Voucher',                $fmt($d['pph21'])];
        $rows[] = [];
        $rows[] = ['G.', 'LABA BERSIH (D - C - E - F)',    $fmt($d['labaBersih'])];

        return $rows;
    }

    public function title(): string
    {
        return substr('RL ' . $this->periode, 0, 31);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                // Title styling
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                ]);
                $sheet->getStyle('A2:A3')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                ]);

                // Column widths
                $sheet->getColumnDimension('A')->setWidth(5);
                $sheet->getColumnDimension('B')->setWidth(35);
                $sheet->getColumnDimension('C')->setWidth(20);

                // Format number column C
                $sheet->getStyle("C1:C{$lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');

                // Style section headers (A., B., C., D., E., F., G.)
                $sectionRows = [];
                for ($r = 1; $r <= $lastRow; $r++) {
                    $val = $sheet->getCell("A{$r}")->getValue();
                    if (in_array($val, ['A.', 'B.', 'C.', 'D.', 'E.', 'F.', 'G.'])) {
                        $sectionRows[] = $r;
                        $sheet->getStyle("A{$r}:C{$r}")->applyFromArray([
                            'font' => ['bold' => true],
                        ]);
                    }
                }

                // Style TOTAL rows
                for ($r = 1; $r <= $lastRow; $r++) {
                    $val = $sheet->getCell("B{$r}")->getValue();
                    if ($val === 'TOTAL') {
                        $sheet->getStyle("A{$r}:C{$r}")->applyFromArray([
                            'font' => ['bold' => true],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE5E7EB']],
                            'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN]],
                        ]);
                    }
                }

                // Style LABA BERSIH row
                for ($r = 1; $r <= $lastRow; $r++) {
                    $val = $sheet->getCell("B{$r}")->getValue();
                    if (str_contains((string)$val, 'LABA BERSIH')) {
                        $sheet->getStyle("A{$r}:C{$r}")->applyFromArray([
                            'font' => ['bold' => true, 'size' => 12],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F3864']],
                            'font' => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FFFFFFFF']],
                        ]);
                    }
                    if (str_contains((string)$val, 'LABA KOTOR')) {
                        $sheet->getStyle("A{$r}:C{$r}")->applyFromArray([
                            'font' => ['bold' => true],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFEF3C7']],
                        ]);
                    }
                }

                // Right-align column C
                $sheet->getStyle("C1:C{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Border around data area
                $sheet->getStyle("A5:C{$lastRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }
}
