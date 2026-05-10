<?php

namespace App\Http\Controllers;

use App\Models\Cv;
use App\Models\GudangLansirHeader;
use App\Models\PoPenerima;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanPembayaranController extends Controller
{
    public function index(Request $request)
    {
        $activeCvId = session('active_cv');

        $dari = $request->dari ?? now()->startOfYear()->format('Y-m-d');
        $sampai = $request->sampai ?? now()->format('Y-m-d');
        $cvId = $request->cv_id ?? $activeCvId;

        // ── 1. OA Payment (dari PO) ───────────────────────────────────
        // Total tagihan = sum dari po_penerima_pakan (jumlah_kg × ongkos_oa)
        // untuk semua penerima yang selesai/batal dalam periode ini
        $poPenerimaQuery = PoPenerima::with(['pakans', 'oaPayment'])
            ->whereIn('status', ['selesai', 'batal'])
            ->whereHas('kendaraan.po', function ($q) use ($cvId, $dari, $sampai) {
                $q->whereBetween('tanggal_po', [$dari, $sampai]);
                if ($cvId) {
                    $q->where('cv_id', $cvId);
                }
            });

        $poPenerimas = $poPenerimaQuery->get();

        // Total tagihan = dari po_penerima_pakan (sumber kebenaran)
        $oaTotalTagihan = $poPenerimas->sum('total_oa');
        // Total sudah dibayar = dari oa_payments yang sudah ada
        $oaTotalBayar = $poPenerimas->sum(fn ($p) => $p->oaPayment?->jumlah_bayar ?? 0);
        $oaTotalSisa = max(0, $oaTotalTagihan - $oaTotalBayar);
        $oaLunas = $poPenerimas->filter(fn ($p) => $p->oaPayment?->status === 'lunas')->count();
        $oaBelum = $poPenerimas->filter(fn ($p) => ! $p->oaPayment || $p->oaPayment->status !== 'lunas')->count();

        // ── 2. Lansir Payment (dari PO) ───────────────────────────────
        $lansirPoQuery = PurchaseOrder::with([
            'cv',
            'lansirPaymentMobil',
            'lansirPaymentTim',
            'kendaraans.penerimas.pakans',
        ])
            ->whereBetween('tanggal_po', [$dari, $sampai]);

        if ($cvId) {
            $lansirPoQuery->where('cv_id', $cvId);
        }

        $lansirPos = $lansirPoQuery->get();

        // Hitung total tagihan lansir dari PO (OA per pakan × kg)
        $lansirPoTotalOa = $lansirPos->sum(fn ($po) => $po->kendaraans->sum(fn ($k) => $k->penerimas->sum('total_oa'))
        );
        $lansirPoSudahBayarMobil = $lansirPos->filter(fn ($po) => $po->lansirPaymentMobil?->isSudahBayar()
        )->count();
        $lansirPoSudahBayarTim = $lansirPos->filter(fn ($po) => $po->lansirPaymentTim?->isSudahBayar()
        )->count();

        // ── 3. Lansir Gudang (informatif) ─────────────────────────────
        $gudangQuery = GudangLansirHeader::with([
            'cv',
            'gudang',
            'kendaraans.penerimas.pakans',
            'kendaraans.penerimas.tims',
        ])
            ->whereBetween('tanggal_lansir', [$dari, $sampai]);

        if ($cvId) {
            $gudangQuery->where('cv_id', $cvId);
        }

        $gudangHeaders = $gudangQuery->get();

        $gudangTotalOa = $gudangHeaders->sum(fn ($h) => $h->kendaraans->sum(fn ($k) => $k->penerimas->sum(fn ($p) => $p->pakans->sum(fn ($pk) => $pk->jumlah_kg * ($pk->ongkos_oa ?? 0))
        )
        )
        );
        $gudangTotalAngkut = $gudangHeaders->sum(fn ($h) => $h->kendaraans->sum(fn ($k) => $k->penerimas->sum(fn ($p) => $p->tims->sum('total_upah')
        )
        )
        );

        // ── 4. Grafik: total tagihan per bulan (OA PO + Lansir Gudang) ─
        $tahun = $request->tahun ?? now()->year;

        $chartOaPerBulan = DB::table('po_penerima_pakan as pk')
            ->join('po_penerima as p', 'p.id', '=', 'pk.po_penerima_id')
            ->join('po_kendaraan as k', 'k.id', '=', 'p.po_kendaraan_id')
            ->join('purchase_orders as po', 'po.id', '=', 'k.po_id')
            ->selectRaw('MONTH(po.tanggal_po) as bulan, SUM(pk.jumlah_kg * pk.ongkos_oa) as total')
            ->whereYear('po.tanggal_po', $tahun)
            ->whereIn('p.status', ['selesai', 'batal'])
            ->when($cvId, fn ($q) => $q->where('po.cv_id', $cvId))
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $chartGudangPerBulan = DB::table('gudang_lansir_header as h')
            ->join('gudang_lansir_kendaraan as k', 'k.lansir_header_id', '=', 'h.id')
            ->join('gudang_lansir_penerima as p', 'p.kendaraan_id', '=', 'k.id')
            ->join('gudang_lansir_pakan as pk', 'pk.penerima_id', '=', 'p.id')
            ->selectRaw('MONTH(h.tanggal_lansir) as bulan, SUM(pk.jumlah_kg * pk.ongkos_oa) as total')
            ->whereYear('h.tanggal_lansir', $tahun)
            ->when($cvId, fn ($q) => $q->where('h.cv_id', $cvId))
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $namaBulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $chartLabels = $namaBulan;
        $chartOa = [];
        $chartGudang = [];
        for ($m = 1; $m <= 12; $m++) {
            $chartOa[] = (float) ($chartOaPerBulan[$m] ?? 0);
            $chartGudang[] = (float) ($chartGudangPerBulan[$m] ?? 0);
        }

        // ── Dropdown filter ───────────────────────────────────────────
        $cvList = Cv::where('is_aktif', true)->orderBy('nama_cv')->get();
        $tahunList = PurchaseOrder::selectRaw('YEAR(tanggal_po) as tahun')
            ->groupBy('tahun')->orderBy('tahun', 'desc')->pluck('tahun');

        return view('pages.laporan.pembayaran', compact(
            'dari', 'sampai', 'cvId', 'tahun',
            // Summary OA
            'oaTotalTagihan', 'oaTotalBayar', 'oaTotalSisa', 'oaLunas', 'oaBelum',
            // Summary Lansir PO
            'lansirPoTotalOa', 'lansirPoSudahBayarMobil', 'lansirPoSudahBayarTim',
            'lansirPos',
            // Summary Lansir Gudang
            'gudangTotalOa', 'gudangTotalAngkut', 'gudangHeaders',
            // Grafik
            'chartLabels', 'chartOa', 'chartGudang',
            // Filter
            'cvList', 'tahunList'
        ));
    }
}
