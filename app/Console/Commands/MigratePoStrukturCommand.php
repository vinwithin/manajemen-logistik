<?php

namespace App\Console\Commands;

use App\Models\OaPayment;
use App\Models\PoPenerima;
use App\Models\PoPenerimaPakan;
use App\Models\PurchaseOrderItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigratePoStrukturCommand extends Command
{
    protected $signature = 'po:migrate-struktur {--dry-run : Tampilkan ringkasan tanpa eksekusi}';

    protected $description = 'Migrasi data dari purchase_order_items ke po_penerima + po_penerima_pakan';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->info($isDryRun ? '[DRY RUN] Simulasi migrasi...' : 'Memulai migrasi data PO...');

        $items = PurchaseOrderItem::all();
        $totalItems = $items->count();

        $this->info("Total purchase_order_items: {$totalItems}");

        if ($totalItems === 0) {
            $this->warn('Tidak ada data untuk dimigrasikan.');
            return 0;
        }

        $createdPenerima = 0;
        $createdPakan = 0;
        $skipped = 0;
        $totalBeratLama = 0;
        $totalKgBaru = 0;

        // Map: po_item_id => new po_penerima_id
        $itemToPenerimaMap = [];

        if (! $isDryRun) {
            DB::beginTransaction();
        }

        try {
            foreach ($items as $item) {
                // Create po_penerima from purchase_order_items
                $penerimaData = [
                    'po_id'        => $item->po_id,
                    'nama_penerima' => $item->nama_penerima ?? 'Penerima '.$item->id,
                    'tujuan_id'    => $item->tujuan_id,
                    'supplier_id'  => $item->supplier_id,
                    'no_polisi'    => $item->no_polisi,
                    'nama_sopir'   => $item->nama_supir,
                    'ongkos'       => $item->ongkos ?? 0,
                    'harga_pt_sum' => 0,
                    'status'       => in_array($item->status, PoPenerima::STATUSES) ? $item->status : 'pending',
                ];

                if ($isDryRun) {
                    $this->line("  [PENERIMA] po_id={$item->po_id}, nama={$penerimaData['nama_penerima']}, polisi={$item->no_polisi}");
                    $createdPenerima++;
                } else {
                    $penerima = PoPenerima::create($penerimaData);
                    $itemToPenerimaMap[$item->id] = $penerima->id;
                    $createdPenerima++;
                }

                // Create po_penerima_pakan if kode_pakan_id and berat are set
                if ($item->kode_pakan_id && $item->berat !== null) {
                    $totalBeratLama += $item->berat;

                    if ($isDryRun) {
                        $this->line("    [PAKAN] kode_pakan_id={$item->kode_pakan_id}, jumlah_kg={$item->berat}");
                        $totalKgBaru += $item->berat;
                        $createdPakan++;
                    } else {
                        PoPenerimaPakan::create([
                            'po_penerima_id' => $itemToPenerimaMap[$item->id],
                            'kode_pakan_id'  => $item->kode_pakan_id,
                            'jumlah_kg'      => $item->berat,
                        ]);
                        $totalKgBaru += $item->berat;
                        $createdPakan++;
                    }
                } else {
                    $skipped++;
                }
            }

            // Update oa_payments.po_penerima_id
            if (! $isDryRun) {
                $oaUpdated = 0;
                $oaPayments = OaPayment::whereNotNull('po_item_id')->get();
                foreach ($oaPayments as $oa) {
                    if (isset($itemToPenerimaMap[$oa->po_item_id])) {
                        $oa->update(['po_penerima_id' => $itemToPenerimaMap[$oa->po_item_id]]);
                        $oaUpdated++;
                    }
                }
                $this->info("OA Payments diperbarui: {$oaUpdated}");
            }

            if (! $isDryRun) {
                DB::commit();
            }

            // Verification summary
            $this->newLine();
            $this->info('=== Ringkasan Migrasi ===');
            $this->table(
                ['Metrik', 'Nilai'],
                [
                    ['Total items diproses', $totalItems],
                    ['PoPenerima dibuat', $createdPenerima],
                    ['PoPenerimaPakan dibuat', $createdPakan],
                    ['Items tanpa kode pakan/berat (skip pakan)', $skipped],
                    ['Total berat lama (kg)', number_format($totalBeratLama, 2)],
                    ['Total kg baru', number_format($totalKgBaru, 2)],
                    ['Verifikasi total kg', $totalBeratLama == $totalKgBaru ? '✅ MATCH' : '❌ MISMATCH'],
                ]
            );

            if ($totalBeratLama != $totalKgBaru) {
                $msg = "PERINGATAN: Total kg tidak cocok! Lama={$totalBeratLama}, Baru={$totalKgBaru}";
                $this->warn($msg);
                Log::warning($msg);
            } else {
                $msg = "Verifikasi total kg: MATCH ({$totalKgBaru} kg)";
                $this->info($msg);
                Log::info($msg);
            }

            if ($isDryRun) {
                $this->warn('[DRY RUN] Tidak ada perubahan yang disimpan ke database.');
            } else {
                Log::info("po:migrate-struktur selesai. Penerima={$createdPenerima}, Pakan={$createdPakan}");
                $this->info('Migrasi selesai!');
            }

            return 0;
        } catch (\Exception $e) {
            if (! $isDryRun) {
                DB::rollBack();
            }
            $this->error('Migrasi gagal: '.$e->getMessage());
            Log::error('po:migrate-struktur gagal: '.$e->getMessage());

            return 1;
        }
    }
}
