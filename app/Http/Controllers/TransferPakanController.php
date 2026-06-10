<?php

namespace App\Http\Controllers;

use App\Models\Cv;
use App\Models\KodePakan;
use App\Models\Penerima;
use App\Models\PoPeriodeDokumen;
use App\Models\TransferPakanHeader;
use App\Models\TransferPakanKendaraan;
use App\Models\TransferPakanPakan;
use App\Models\TransferPakanPenerima;
use App\Models\TransferPakanTim;
use App\Models\Tujuan;
use App\Services\Datatables\TransferPakanDatatableService;
use App\Traits\WithUserTujuan;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransferPakanController extends Controller
{
    use WithUserTujuan;

    public function __construct(
        protected TransferPakanDatatableService $datatableService
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->datatableService->getData($request);
        }

        return view('pages.transfer-pakan.index');
    }

    public function create()
    {
        $cvList = Cv::withOmzet();
        $tujuans = $this->getUserTujuan();
        $kodePakans = KodePakan::orderBy('kode')->get();
        $penerimaList = Penerima::with('tujuan')
            ->where('is_aktif', true)
            ->when($tujuans->count() > 0, fn ($q) => $q->whereIn('tujuan_id', $tujuans->pluck('id')))
            ->orderBy('nama')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'nama' => $p->nama,
                'tujuan_id' => $p->tujuan_id,
                'tujuan_nama' => $p->tujuan?->nama ?? '',
                'ongkos_angkut' => (float) $p->ongkos_angkut,
                'ongkos_bongkar' => (float) $p->ongkos_bongkar,
            ]);
        $batasOmzet = Cv::BATAS_OMZET;

        return view('pages.transfer-pakan.create', compact(
            'cvList', 'tujuans', 'kodePakans', 'penerimaList', 'batasOmzet'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'no_transfer' => 'required|string|max:100|unique:transfer_pakan_header,no_transfer',
            'cv_id' => 'required|exists:cv,id',
            'tanggal_transfer' => 'required|date',
            'pengirim_id' => 'required|exists:penerima,id',
            'nama_pengirim' => 'nullable|string|max:255',
            'tujuan_id' => 'nullable|exists:tujuan,id',
            'catatan' => 'nullable|string',
            'kendaraans' => 'required|array|min:1',
            'kendaraans.*.no_polisi' => 'required|string|max:20',
            'kendaraans.*.nama_sopir' => 'nullable|string|max:255',
            'kendaraans.*.penerimas' => 'required|array|min:1',
            'kendaraans.*.penerimas.*.nama_penerima' => 'required|string|max:255',
            'kendaraans.*.penerimas.*.penerima_id' => 'nullable|exists:penerima,id',
            'kendaraans.*.penerimas.*.tujuan_id' => 'nullable|exists:tujuan,id',
            'kendaraans.*.penerimas.*.no_surat_jalan' => 'nullable|string|max:100',
            'kendaraans.*.penerimas.*.pakans' => 'required|array|min:1',
            'kendaraans.*.penerimas.*.pakans.*.kode_pakan_id' => 'required|exists:kode_pakan,id',
            'kendaraans.*.penerimas.*.pakans.*.jumlah_kg' => 'required|numeric|min:0.01',
            'kendaraans.*.penerimas.*.pakans.*.ongkos_oa' => 'nullable|numeric|min:0',
            'kendaraans.*.penerimas.*.pakans.*.harga_pt_sum' => 'nullable|numeric|min:0',
        ], [
            'no_transfer.required' => 'Nomor transfer wajib diisi.',
            'no_transfer.unique' => 'Nomor transfer sudah digunakan.',
            'no_transfer.max' => 'Nomor transfer tidak boleh lebih dari 100 karakter.',
            'cv_id.required' => 'CV wajib dipilih.',
            'cv_id.exists' => 'CV yang dipilih tidak valid.',
            'tanggal_transfer.required' => 'Tanggal transfer wajib diisi.',
            'tanggal_transfer.date' => 'Format tanggal transfer tidak valid.',
            'pengirim_id.required' => 'Pengirim wajib dipilih.',
            'pengirim_id.exists' => 'Pengirim yang dipilih tidak valid.',
            'nama_pengirim.max' => 'Nama pengirim tidak boleh lebih dari 255 karakter.',
            'tujuan_id.exists' => 'Tujuan yang dipilih tidak valid.',
            'kendaraans.required' => 'Data kendaraan wajib diisi.',
            'kendaraans.array' => 'Format data kendaraan tidak valid.',
            'kendaraans.min' => 'Minimal harus ada 1 kendaraan.',
            'kendaraans.*.no_polisi.required' => 'No. polisi kendaraan wajib diisi.',
            'kendaraans.*.no_polisi.max' => 'No. polisi tidak boleh lebih dari 20 karakter.',
            'kendaraans.*.nama_sopir.max' => 'Nama sopir tidak boleh lebih dari 255 karakter.',
            'kendaraans.*.penerimas.required' => 'Data penerima wajib diisi.',
            'kendaraans.*.penerimas.min' => 'Minimal harus ada 1 penerima per kendaraan.',
            'kendaraans.*.penerimas.*.nama_penerima.required' => 'Nama penerima wajib diisi.',
            'kendaraans.*.penerimas.*.nama_penerima.max' => 'Nama penerima tidak boleh lebih dari 255 karakter.',
            'kendaraans.*.penerimas.*.penerima_id.exists' => 'Penerima yang dipilih tidak valid.',
            'kendaraans.*.penerimas.*.tujuan_id.exists' => 'Tujuan yang dipilih tidak valid.',
            'kendaraans.*.penerimas.*.no_surat_jalan.max' => 'No. surat jalan tidak boleh lebih dari 100 karakter.',
            'kendaraans.*.penerimas.*.pakans.required' => 'Data pakan wajib diisi.',
            'kendaraans.*.penerimas.*.pakans.min' => 'Minimal harus ada 1 jenis pakan per penerima.',
            'kendaraans.*.penerimas.*.pakans.*.kode_pakan_id.required' => 'Kode pakan wajib dipilih.',
            'kendaraans.*.penerimas.*.pakans.*.kode_pakan_id.exists' => 'Kode pakan yang dipilih tidak valid.',
            'kendaraans.*.penerimas.*.pakans.*.jumlah_kg.required' => 'Jumlah kg wajib diisi.',
            'kendaraans.*.penerimas.*.pakans.*.jumlah_kg.numeric' => 'Jumlah kg harus berupa angka.',
            'kendaraans.*.penerimas.*.pakans.*.jumlah_kg.min' => 'Jumlah kg minimal 0.01.',
            'kendaraans.*.penerimas.*.pakans.*.ongkos_oa.numeric' => 'Ongkos OA harus berupa angka.',
            'kendaraans.*.penerimas.*.pakans.*.ongkos_oa.min' => 'Ongkos OA tidak boleh kurang dari 0.',
            'kendaraans.*.penerimas.*.pakans.*.harga_pt_sum.numeric' => 'Harga PT Sum harus berupa angka.',
            'kendaraans.*.penerimas.*.pakans.*.harga_pt_sum.min' => 'Harga PT Sum tidak boleh kurang dari 0.',
        ]);

        // Validasi CV tidak melebihi batas omzet
        $cv = Cv::find($request->cv_id);
        if ($cv && $cv->isMelebihiBatas()) {
            return redirect()->back()
                ->with('error', 'CV yang dipilih sudah melebihi batas omzet tahunan dan tidak dapat dipilih.')
                ->withInput();
        }

        DB::beginTransaction();
        try {
            // Ambil nama pengirim dari master data jika tidak dikirim via hidden
            $namaPengirim = $request->nama_pengirim;
            if (empty($namaPengirim) && $request->pengirim_id) {
                $pengirimMaster = Penerima::find($request->pengirim_id);
                $namaPengirim = $pengirimMaster?->nama ?? '';
            }

            $header = TransferPakanHeader::create([
                'no_transfer' => strtoupper($request->no_transfer),
                'cv_id' => $request->cv_id,
                'tanggal_transfer' => $request->tanggal_transfer,
                'tujuan_id' => $request->tujuan_id,
                'pengirim_id' => $request->pengirim_id,
                'nama_pengirim' => $namaPengirim,
                'catatan' => $request->catatan,
                'created_by' => Auth::id(),
            ]);

            foreach ($request->kendaraans as $kendaraanData) {
                if (empty(trim($kendaraanData['no_polisi'] ?? ''))) {
                    continue;
                }

                $kendaraan = TransferPakanKendaraan::create([
                    'header_id' => $header->id,
                    'no_polisi' => strtoupper(trim($kendaraanData['no_polisi'])),
                    'nama_sopir' => $kendaraanData['nama_sopir'] ?? null,
                ]);

                $totalKg = 0;
                $totalKarung = 0;

                foreach ($kendaraanData['penerimas'] ?? [] as $penerimaData) {
                    if (empty(trim($penerimaData['nama_penerima'] ?? ''))) {
                        continue;
                    }

                    $penerima = TransferPakanPenerima::create([
                        'kendaraan_id' => $kendaraan->id,
                        'penerima_id' => $penerimaData['penerima_id'] ?? null,
                        'nama_penerima' => $penerimaData['nama_penerima'],
                        'no_surat_jalan' => $penerimaData['no_surat_jalan'] ?? null,
                        'tujuan_id' => $penerimaData['tujuan_id'] ?? null,
                        'status' => 'pending',
                    ]);

                    foreach ($penerimaData['pakans'] ?? [] as $pakanData) {
                        if (empty($pakanData['kode_pakan_id']) || empty($pakanData['jumlah_kg'])) {
                            continue;
                        }

                        $jumlahKg = (float) $pakanData['jumlah_kg'];
                        $jumlahKarung = $pakanData['jumlah_karung'] ?? (int) ceil($jumlahKg / 50);

                        TransferPakanPakan::create([
                            'penerima_id' => $penerima->id,
                            'kode_pakan_id' => $pakanData['kode_pakan_id'],
                            'jumlah_kg' => $jumlahKg,
                            'jumlah_karung' => $jumlahKarung,
                            'ongkos_oa' => $pakanData['ongkos_oa'] ?? 0,
                            'harga_pt_sum' => $pakanData['harga_pt_sum'] ?? 0,
                            'keterangan' => $pakanData['keterangan'] ?? null,
                        ]);

                        $totalKg += $jumlahKg;
                        $totalKarung += $jumlahKarung;
                    }

                    foreach ($penerimaData['tims'] ?? [] as $timData) {
                        if (empty(trim($timData['nama_tim'] ?? ''))) {
                            continue;
                        }
                        TransferPakanTim::create([
                            'penerima_id' => $penerima->id,
                            'nama_tim' => trim($timData['nama_tim']),
                            'jumlah_kg' => $timData['jumlah_kg'] ?? 0,
                            'jumlah_karung' => $timData['jumlah_karung'] ?? 0,
                            'upah_per_kg' => $timData['upah_per_kg'] ?? null,
                            'keterangan' => $timData['keterangan'] ?? null,
                        ]);
                    }
                }

                $kendaraan->update(['total_kg' => $totalKg, 'total_karung' => $totalKarung]);
            }

            DB::commit();

            return redirect()->route('transfer-pakan.show', encrypt($header->id))
                ->with('success', 'Transfer pakan berhasil disimpan. No: '.$header->no_transfer);
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Gagal menyimpan: '.$e->getMessage())
                ->withInput();
        }
    }

    public function show(string $id)
    {
        try {
            $header = TransferPakanHeader::with([
                'cv', 'tujuan', 'pengirim',
                'kendaraans.penerimas.tujuan',
                'kendaraans.penerimas.pakans.kodePakan',
                'kendaraans.penerimas.tims',
                'creator',
            ])->findOrFail(decrypt($id));

            $kodePakanList = $header->kendaraans
                ->flatMap->penerimas
                ->flatMap->pakans
                ->map->kodePakan
                ->filter()
                ->unique('id')
                ->sortBy('kode')
                ->values();

            return view('pages.transfer-pakan.show', compact('header', 'kodePakanList'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Data tidak ditemukan.');
        }
    }

    public function penerimaUpdateStatus(Request $request, string $id)
    {
        try {
            $penerima = TransferPakanPenerima::findOrFail(decrypt($id));

            $request->validate([
                'status' => 'required|in:tiba,selesai',
                'tiba_at' => 'nullable|date',
                'nama_validator' => 'nullable|string|max:255',
                'bukti_tiba' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            if ($request->status === 'tiba') {
                $buktiPath = null;
                if ($request->hasFile('bukti_tiba')) {
                    $file = $request->file('bukti_tiba');
                    $filename = 'bukti_tiba_tp_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                    $buktiPath = $file->storeAs('bukti_tiba', $filename, 'public');
                }

                $penerima->update([
                    'status' => 'tiba',
                    'bukti_tiba' => $buktiPath,
                    'tiba_at' => $request->tiba_at ?? now(),
                    'validasi_oleh' => $request->nama_validator,
                ]);

                return redirect()->back()->with('success', 'Status berhasil diubah menjadi Tiba.');
            }

            if ($request->status === 'selesai') {
                if ($penerima->status !== 'tiba') {
                    return redirect()->back()->with('error', 'Penerima harus berstatus Tiba terlebih dahulu.');
                }
                $penerima->update(['status' => 'selesai']);

                return redirect()->back()->with('success', 'Status berhasil diubah menjadi Selesai.');
            }

            return redirect()->back()->with('error', 'Status tidak valid.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal update status: '.$e->getMessage());
        }
    }

    public function exportRekap(Request $request)
    {
        $from  = $request->from;
        $to    = $request->to;
        $cvId  = $request->cv_id ?? session('active_cv');

        $export   = new \App\Exports\TransferPakanExport($from, $to, $cvId ? (int) $cvId : null);
        $filename = 'Transfer-Pakan-'.now()->format('Ymd').'.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download($export, $filename);
    }

    public function exportPdfPtSumConfirm(Request $request)
    {

        $tujuans = $this->getUserTujuan();

        $cvId = $request->cv_id ?? session('active_cv');
        $from = $request->from;
        $to = $request->to;
        $tujuanIds = $request->tujuan_ids
            ? array_filter(array_map('intval', explode(',', $request->tujuan_ids)))
            : [];
        $selectedKendaraanIds = $request->kendaraan_ids ? array_filter(explode(',', $request->kendaraan_ids)) : [];
        $transferCount = null;
        $cvNama = null;
        $dokumen = null;
        $noSuratSuggest = null;
        $kendaraanList = collect();

        if ($cvId && $from && $to) {
            $query = TransferPakanHeader::where('cv_id', $cvId)
                ->whereDate('tanggal_transfer', '>=', $from)
                ->whereDate('tanggal_transfer', '<=', $to);

            if (! empty($tujuanIds)) {
                $query->whereHas('kendaraans.penerimas', fn ($q) => $q->whereIn('tujuan_id', $tujuanIds));
            }

            $transferCount = $query->count();

            // Ambil daftar kendaraan untuk filter plat mobil
            $kendaraanList = \App\Models\TransferPakanKendaraan::whereHas('header', function ($q) use ($cvId, $from, $to) {
                $q->where('cv_id', $cvId)
                    ->whereDate('tanggal_transfer', '>=', $from)
                    ->whereDate('tanggal_transfer', '<=', $to);
            })
                ->when(! empty($tujuanIds), fn ($q) => $q->whereHas('penerimas', fn ($q2) => $q2->whereIn('tujuan_id', $tujuanIds)))
                ->with('header')
                ->orderBy('no_polisi')
                ->get(['id', 'no_polisi', 'header_id']);

            $cv = Cv::find($cvId);
            $cvNama = $cv?->nama_cv;

            // Cek apakah ada dokumen periode yang sudah ada (mirip PO)
            $dokumen = \App\Models\PoPeriodeDokumen::where('cv_id', $cvId)
                ->where('dari', $from)
                ->where('sampai', $to)
                ->where('tipe', 'transfer-ptsum')
                ->first();

            if (! $dokumen && $cv) {
                $generated = \App\Models\PoPeriodeDokumen::generateNoSurat($cv, 'transfer-ptsum', $from);
                $noSuratSuggest = $generated['no_surat'];
            }
        }

        return view('pages.transfer-pakan.export-ptsum-confirm', compact(
            'tujuans', 'cvId', 'from', 'to', 'tujuanIds',
            'transferCount', 'cvNama', 'dokumen', 'noSuratSuggest', 'kendaraanList', 'selectedKendaraanIds'
        ));
    }

    public function exportPdfPtSum(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'tujuan_ids' => 'required|string',
        ], [
            'from.required' => 'Tanggal awal periode wajib diisi.',
            'to.required' => 'Tanggal akhir periode wajib diisi.',
            'to.after_or_equal' => 'Tanggal akhir harus sama atau setelah tanggal awal.',
            'tujuan_ids.required' => 'Pilih tujuan terlebih dahulu.',
        ]);

        $cvId = $request->cv_id ?? session('active_cv');
        $from = $request->from;
        $to = $request->to;
        $tujuanIds = array_filter(array_map('intval', explode(',', $request->tujuan_ids)));
        $noSuratInput = $request->no_surat;
        $cpi = $request->cpi;
        $kendaraanIds = $request->kendaraan_ids
            ? array_filter(array_map('intval', explode(',', $request->kendaraan_ids)))
            : [];

        if (! $cvId) {
            return redirect()->route('transfer-pakan.export-ptsum-confirm')
                ->with('error', 'Pilih CV terlebih dahulu.');
        }

        $cv = Cv::find($cvId);
        $noSurat = null;

        // Simpan atau update dokumen periode (mirip PO)
        if ($noSuratInput) {
            $dokumen = PoPeriodeDokumen::where('cv_id', $cvId)
                ->where('dari', $from)
                ->where('sampai', $to)
                ->where('tipe', 'transper_pakan')
                ->first();

            if ($dokumen) {
                $dokumen->update([
                    'no_surat' => $noSuratInput,
                    'cpi' => $cpi,
                ]);
            } else {
                $dokumen = PoPeriodeDokumen::create([
                    'cv_id' => $cvId,
                    'dari' => $from,
                    'sampai' => $to,
                    'tipe' => 'transper_pakan',
                    'no_surat' => $noSuratInput,
                    'cpi' => $cpi,
                ]);
            }

            $noSurat = $dokumen->no_surat;
        }

        // Ambil data transfer pakan dengan filter
        $headers = TransferPakanHeader::with([
            'cv', 'tujuan', 'pengirim',
            'kendaraans.penerimas.pakans.kodePakan',
            'kendaraans.penerimas.tujuan',
        ])->where('cv_id', $cvId)
            ->whereDate('tanggal_transfer', '>=', $from)
            ->whereDate('tanggal_transfer', '<=', $to)
            ->when(! empty($tujuanIds), fn ($q) => $q->whereHas('kendaraans.penerimas', fn ($q2) => $q2->whereIn('tujuan_id', $tujuanIds)))
            ->orderBy('tanggal_transfer')
            ->get();

        // Filter kendaraan yang dipilih
        if (! empty($kendaraanIds)) {
            foreach ($headers as $header) {
                $header->setRelation('kendaraans', $header->kendaraans->filter(
                    fn ($k) => in_array($k->id, $kendaraanIds)
                )->values());
            }
        }

        // Hapus header yang tidak punya kendaraan
        $headers = $headers->filter(fn ($h) => $h->kendaraans->isNotEmpty())->values();

        // Nama tujuan untuk dokumen
        $tujuanNamaList = Tujuan::whereIn('id', $tujuanIds)->pluck('nama')->join(' & ');
        $tujuanNama = $cpi ?? $tujuanNamaList;
        // dd($tujuanNama);

        $pdf = Pdf::loadView('pdf.transfer-pakan-ptsum', compact('headers', 'from', 'to', 'noSurat', 'tujuanNama', 'cv'))
            ->setPaper('legal', 'landscape')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)
            ->setOption('margin-right', 10);

        $cvNama = $cv?->nama_cv ?? 'CV';
        $filename = 'Transfer-Pakan-PTSum-'.str_replace(' ', '-', $cvNama).'-'.now()->format('Ymd').'.pdf';

        return $pdf->download($filename);
    }

    public function edit(string $id)
    {
        try {
            $header = TransferPakanHeader::with([
                'kendaraans.penerimas.pakans',
                'kendaraans.penerimas.tims',
                'kendaraans.penerimas.tujuan',
            ])->findOrFail(decrypt($id));

            $cvList = Cv::withOmzet();
            $tujuans = $this->getUserTujuan();
            $kodePakans = KodePakan::orderBy('kode')->get();
            $penerimaList = Penerima::with('tujuan')
                ->where('is_aktif', true)
                ->when($tujuans->count() > 0, fn ($q) => $q->whereIn('tujuan_id', $tujuans->pluck('id')))
                ->orderBy('nama')
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'nama' => $p->nama,
                    'tujuan_id' => $p->tujuan_id,
                    'tujuan_nama' => $p->tujuan?->nama ?? '',
                    'ongkos_angkut' => (float) $p->ongkos_angkut,
                    'ongkos_bongkar' => (float) $p->ongkos_bongkar,
                ]);
            $batasOmzet = Cv::BATAS_OMZET;

            return view('pages.transfer-pakan.edit', compact(
                'header', 'cvList', 'tujuans', 'kodePakans', 'penerimaList', 'batasOmzet'
            ));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Data tidak ditemukan.');
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            $header = TransferPakanHeader::findOrFail(decrypt($id));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Data tidak ditemukan.');
        }

        $request->validate([
            'no_transfer' => 'required|string|max:100|unique:transfer_pakan_header,no_transfer,'.$header->id,
            'cv_id' => 'required|exists:cv,id',
            'tanggal_transfer' => 'required|date',
            'pengirim_id' => 'required|exists:penerima,id',
            'nama_pengirim' => 'nullable|string|max:255',
            'tujuan_id' => 'nullable|exists:tujuan,id',
            'catatan' => 'nullable|string',
            'kendaraans' => 'required|array|min:1',
            'kendaraans.*.no_polisi' => 'required|string|max:20',
            'kendaraans.*.nama_sopir' => 'nullable|string|max:255',
            'kendaraans.*.penerimas' => 'required|array|min:1',
            'kendaraans.*.penerimas.*.nama_penerima' => 'required|string|max:255',
            'kendaraans.*.penerimas.*.penerima_id' => 'nullable|exists:penerima,id',
            'kendaraans.*.penerimas.*.tujuan_id' => 'nullable|exists:tujuan,id',
            'kendaraans.*.penerimas.*.no_surat_jalan' => 'nullable|string|max:100',
            'kendaraans.*.penerimas.*.pakans' => 'required|array|min:1',
            'kendaraans.*.penerimas.*.pakans.*.kode_pakan_id' => 'required|exists:kode_pakan,id',
            'kendaraans.*.penerimas.*.pakans.*.jumlah_kg' => 'required|numeric|min:0.01',
            'kendaraans.*.penerimas.*.pakans.*.ongkos_oa' => 'nullable|numeric|min:0',
            'kendaraans.*.penerimas.*.pakans.*.harga_pt_sum' => 'nullable|numeric|min:0',
        ], [
            'no_transfer.required' => 'Nomor transfer wajib diisi.',
            'no_transfer.unique' => 'Nomor transfer sudah digunakan.',
            'no_transfer.max' => 'Nomor transfer tidak boleh lebih dari 100 karakter.',
            'cv_id.required' => 'CV wajib dipilih.',
            'cv_id.exists' => 'CV yang dipilih tidak valid.',
            'tanggal_transfer.required' => 'Tanggal transfer wajib diisi.',
            'tanggal_transfer.date' => 'Format tanggal transfer tidak valid.',
            'pengirim_id.required' => 'Pengirim wajib dipilih.',
            'pengirim_id.exists' => 'Pengirim yang dipilih tidak valid.',
            'nama_pengirim.max' => 'Nama pengirim tidak boleh lebih dari 255 karakter.',
            'tujuan_id.exists' => 'Tujuan yang dipilih tidak valid.',
            'kendaraans.required' => 'Data kendaraan wajib diisi.',
            'kendaraans.array' => 'Format data kendaraan tidak valid.',
            'kendaraans.min' => 'Minimal harus ada 1 kendaraan.',
            'kendaraans.*.no_polisi.required' => 'No. polisi kendaraan wajib diisi.',
            'kendaraans.*.no_polisi.max' => 'No. polisi tidak boleh lebih dari 20 karakter.',
            'kendaraans.*.nama_sopir.max' => 'Nama sopir tidak boleh lebih dari 255 karakter.',
            'kendaraans.*.penerimas.required' => 'Data penerima wajib diisi.',
            'kendaraans.*.penerimas.min' => 'Minimal harus ada 1 penerima per kendaraan.',
            'kendaraans.*.penerimas.*.nama_penerima.required' => 'Nama penerima wajib diisi.',
            'kendaraans.*.penerimas.*.nama_penerima.max' => 'Nama penerima tidak boleh lebih dari 255 karakter.',
            'kendaraans.*.penerimas.*.penerima_id.exists' => 'Penerima yang dipilih tidak valid.',
            'kendaraans.*.penerimas.*.tujuan_id.exists' => 'Tujuan yang dipilih tidak valid.',
            'kendaraans.*.penerimas.*.no_surat_jalan.max' => 'No. surat jalan tidak boleh lebih dari 100 karakter.',
            'kendaraans.*.penerimas.*.pakans.required' => 'Data pakan wajib diisi.',
            'kendaraans.*.penerimas.*.pakans.min' => 'Minimal harus ada 1 jenis pakan per penerima.',
            'kendaraans.*.penerimas.*.pakans.*.kode_pakan_id.required' => 'Kode pakan wajib dipilih.',
            'kendaraans.*.penerimas.*.pakans.*.kode_pakan_id.exists' => 'Kode pakan yang dipilih tidak valid.',
            'kendaraans.*.penerimas.*.pakans.*.jumlah_kg.required' => 'Jumlah kg wajib diisi.',
            'kendaraans.*.penerimas.*.pakans.*.jumlah_kg.numeric' => 'Jumlah kg harus berupa angka.',
            'kendaraans.*.penerimas.*.pakans.*.jumlah_kg.min' => 'Jumlah kg minimal 0.01.',
            'kendaraans.*.penerimas.*.pakans.*.ongkos_oa.numeric' => 'Ongkos OA harus berupa angka.',
            'kendaraans.*.penerimas.*.pakans.*.ongkos_oa.min' => 'Ongkos OA tidak boleh kurang dari 0.',
            'kendaraans.*.penerimas.*.pakans.*.harga_pt_sum.numeric' => 'Harga PT Sum harus berupa angka.',
            'kendaraans.*.penerimas.*.pakans.*.harga_pt_sum.min' => 'Harga PT Sum tidak boleh kurang dari 0.',
        ]);

        // Validasi CV tidak melebihi batas omzet jika berubah
        if ($request->cv_id != $header->cv_id) {
            $cv = Cv::find($request->cv_id);
            if ($cv && $cv->isMelebihiBatas()) {
                return redirect()->back()
                    ->with('error', 'CV yang dipilih sudah melebihi batas omzet tahunan dan tidak dapat dipilih.')
                    ->withInput();
            }
        }

        DB::beginTransaction();
        try {
            $namaPengirim = $request->nama_pengirim;
            if (empty($namaPengirim) && $request->pengirim_id) {
                $pengirimMaster = Penerima::find($request->pengirim_id);
                $namaPengirim = $pengirimMaster?->nama ?? '';
            }

            // Update header
            $header->update([
                'no_transfer' => strtoupper($request->no_transfer),
                'cv_id' => $request->cv_id,
                'tanggal_transfer' => $request->tanggal_transfer,
                'tujuan_id' => $request->tujuan_id,
                'pengirim_id' => $request->pengirim_id,
                'nama_pengirim' => $namaPengirim,
                'catatan' => $request->catatan,
            ]);

            // Hapus semua kendaraan lama (cascade ke penerima, pakan, tim)
            foreach ($header->kendaraans()->with('penerimas.pakans', 'penerimas.tims')->get() as $kendaraanLama) {
                foreach ($kendaraanLama->penerimas as $penerimaLama) {
                    $penerimaLama->pakans()->delete();
                    $penerimaLama->tims()->delete();
                }
                $kendaraanLama->penerimas()->delete();
            }
            $header->kendaraans()->delete();

            // Insert kendaraan baru
            foreach ($request->kendaraans as $kendaraanData) {
                if (empty(trim($kendaraanData['no_polisi'] ?? ''))) {
                    continue;
                }

                $kendaraan = TransferPakanKendaraan::create([
                    'header_id' => $header->id,
                    'no_polisi' => strtoupper(trim($kendaraanData['no_polisi'])),
                    'nama_sopir' => $kendaraanData['nama_sopir'] ?? null,
                ]);

                $totalKg = 0;
                $totalKarung = 0;

                foreach ($kendaraanData['penerimas'] ?? [] as $penerimaData) {
                    if (empty(trim($penerimaData['nama_penerima'] ?? ''))) {
                        continue;
                    }

                    $penerima = TransferPakanPenerima::create([
                        'kendaraan_id' => $kendaraan->id,
                        'penerima_id' => $penerimaData['penerima_id'] ?? null,
                        'nama_penerima' => $penerimaData['nama_penerima'],
                        'no_surat_jalan' => $penerimaData['no_surat_jalan'] ?? null,
                        'tujuan_id' => $penerimaData['tujuan_id'] ?? null,
                        'status' => 'pending',
                    ]);

                    foreach ($penerimaData['pakans'] ?? [] as $pakanData) {
                        if (empty($pakanData['kode_pakan_id']) || empty($pakanData['jumlah_kg'])) {
                            continue;
                        }

                        $jumlahKg = (float) $pakanData['jumlah_kg'];
                        $jumlahKarung = $pakanData['jumlah_karung'] ?? (int) ceil($jumlahKg / 50);

                        TransferPakanPakan::create([
                            'penerima_id' => $penerima->id,
                            'kode_pakan_id' => $pakanData['kode_pakan_id'],
                            'jumlah_kg' => $jumlahKg,
                            'jumlah_karung' => $jumlahKarung,
                            'ongkos_oa' => $pakanData['ongkos_oa'] ?? 0,
                            'harga_pt_sum' => $pakanData['harga_pt_sum'] ?? 0,
                            'keterangan' => $pakanData['keterangan'] ?? null,
                        ]);

                        $totalKg += $jumlahKg;
                        $totalKarung += $jumlahKarung;
                    }

                    foreach ($penerimaData['tims'] ?? [] as $timData) {
                        if (empty(trim($timData['nama_tim'] ?? ''))) {
                            continue;
                        }
                        TransferPakanTim::create([
                            'penerima_id' => $penerima->id,
                            'nama_tim' => trim($timData['nama_tim']),
                            'jumlah_kg' => $timData['jumlah_kg'] ?? 0,
                            'jumlah_karung' => $timData['jumlah_karung'] ?? 0,
                            'upah_per_kg' => $timData['upah_per_kg'] ?? null,
                            'keterangan' => $timData['keterangan'] ?? null,
                        ]);
                    }
                }

                $kendaraan->update(['total_kg' => $totalKg, 'total_karung' => $totalKarung]);
            }

            DB::commit();

            return redirect()->route('transfer-pakan.show', encrypt($header->id))
                ->with('success', 'Transfer pakan berhasil diperbarui.');
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Gagal menyimpan: '.$e->getMessage())
                ->withInput();
        }
    }

    public function timDestroy(string $id)
    {
        try {
            $id = decrypt($id);
            $tim = TransferPakanTim::findOrFail($id);

            $tim->delete();

            return redirect()->back()->with('success', 'Tim bongkar berhasil dihapus!');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus tim bongkar: '.$e->getMessage());
        }
    }

    public function destroy(string $id)
    {
        try {
            $header = TransferPakanHeader::findOrFail(decrypt($id));
            $header->delete();

            return redirect()->route('transfer-pakan.index')->with('success', 'Transfer pakan berhasil dihapus.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus transfer pakan: '.$e->getMessage());
        }
    }
}
