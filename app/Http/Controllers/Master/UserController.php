<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Cv;
use App\Models\Tujuan;
use App\Models\User;
use App\Models\UserCv;
use App\Models\UserTujuan;
use App\Services\Datatables\UserService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = User::with(['roles:id,name', 'userCV'])->select([
                'id',
                'name',
                'email',
                'aktif',
                'level',
            ]);

            return $this->userService->getData($query);
        }

        return view('pages.user.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data = Cv::all();
        $tujuan = Tujuan::where('is_aktif', true)->orderBy('nama')->get();

        return view('pages.user.create', [
            'data' => $data,
            'roles' => Role::all(),
            'tujuan' => $tujuan,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'level' => 'required',
            'roles' => 'array',
            'roles.*' => 'exists:roles,id',
            'id_cv' => 'array',
            'id_tujuan' => 'array',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'level' => $request->level,
                'level_tujuan' => $request->level_tujuan,
                'aktif' => $request->has('aktif') ? 1 : 0,
            ]);

            // Konversi ID role menjadi nama role
            if ($request->filled('roles')) {
                $validRoles = Role::whereIn('id', $request->roles)->pluck('name')->toArray();
                $user->syncRoles($validRoles);
            }

            // Simpan CV berdasarkan level
            if ($request->level == 1) {
                // Level 1 = Pusat, create semua CV
                $allCv = Cv::all();
                foreach ($allCv as $cv) {
                    UserCv::create(['user_id' => $user->id, 'cv_id' => $cv->id, 'role' => '']);
                }
            } elseif ($request->level == 2 && $request->filled('id_cv')) {
                // Level 2 = Per CV, create CV yang dipilih saja
                foreach ($request->id_cv as $cvId) {
                    UserCv::create(['user_id' => $user->id, 'cv_id' => $cvId, 'role' => '']);
                }
            }

            // Simpan Tujuan berdasarkan level_tujuan
            if ($request->level_tujuan == 1) {
                // Level Tujuan 1 = Pusat, create semua Tujuan
                $allTujuan = Tujuan::all();
                foreach ($allTujuan as $tujuan) {
                    UserTujuan::create(['user_id' => $user->id, 'tujuan_id' => $tujuan->id, 'role' => '']);
                }
            } elseif ($request->level_tujuan == 2 && $request->filled('id_tujuan')) {
                // Level Tujuan 2 = Per Tujuan, create Tujuan yang dipilih saja
                foreach ($request->id_tujuan as $tujuanId) {
                    UserTujuan::create(['user_id' => $user->id, 'tujuan_id' => $tujuanId, 'role' => '']);
                }
            }

            DB::commit();

            return redirect()->route('user.index')->with('success', 'User berhasil ditambahkan.');
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal menyimpan user: '.$e->getMessage())->withInput();
        }
    }



    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        try {
            $id = decrypt($id);
            $data = User::findOrFail($id);
            $user_roles = [];
            $roles = Role::all();
            $user_cv = [];
            $user_tujuan = [];
            $cv = Cv::all();
            foreach ($data->roles as $role) {
                $user_roles[] = $role->id;
            }

            foreach ($data->userCv as $v) {
                $user_cv[] = $v->cv_id;
            }

            foreach ($data->userTujuan as $v) {
                $user_tujuan[] = $v->tujuan_id;
            }

            // Dapatkan semua tujuan aktif
            $tujuan = Tujuan::where('is_aktif', true)
                ->orderBy('nama')
                ->get(['id', 'nama', 'type']);

            return view('pages.user.edit', [
                'user' => $data,
                'user_roles' => $user_roles,
                'roles' => $roles,
                'user_cv' => $user_cv,
                'user_tujuan' => $user_tujuan,
                'cv' => $cv,
                'tujuan' => $tujuan,
            ]);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal memuat halaman!');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'level' => 'required',
            'roles' => 'array',
            'roles.*' => 'exists:roles,id',
            'id_cv' => 'array',
            'id_tujuan' => 'array',
        ]);

        DB::beginTransaction();
        try {
            $user = User::findOrFail($id);

            $user->update([
                'name' => $request->name,
                'level' => $request->level,
                'level_tujuan' => $request->level_tujuan,
                'aktif' => $request->has('aktif') ? 1 : 0,
            ]);

            if ($request->filled('password')) {
                $user->update(['password' => Hash::make($request->password)]);
            }

            // Konversi ID role menjadi nama role
            if ($request->filled('roles')) {
                $validRoles = Role::whereIn('id', $request->roles)->pluck('name')->toArray();
                $user->syncRoles($validRoles);
            } else {
                $user->syncRoles([]);
            }

            UserCv::where('user_id', $user->id)->delete();
            UserTujuan::where('user_id', $user->id)->delete();

            // Simpan CV berdasarkan level
            if ($request->level == 1) {
                $allCv = Cv::all();
                foreach ($allCv as $cv) {
                    UserCv::create(['user_id' => $user->id, 'cv_id' => $cv->id, 'role' => '']);
                }
            } elseif ($request->level == 2 && $request->filled('id_cv')) {
                // Level 2 = Per CV, create CV yang dipilih saja
                foreach ($request->id_cv as $cvId) {
                    UserCv::create(['user_id' => $user->id, 'cv_id' => $cvId, 'role' => '']);
                }
            }

            if ($request->level_tujuan == 1) {
                $allTujuan = Tujuan::all();
                foreach ($allTujuan as $tujuan) {
                    UserTujuan::create(['user_id' => $user->id, 'tujuan_id' => $tujuan->id, 'role' => '']);
                }
            } elseif ($request->level_tujuan == 2 && $request->filled('id_tujuan')) {
                foreach ($request->id_tujuan as $tujuanId) {
                    UserTujuan::create(['user_id' => $user->id, 'tujuan_id' => $tujuanId, 'role' => '']);
                }
            }

            DB::commit();

            return redirect()->route('user.index')->with('success', 'User berhasil diperbarui.');
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal memperbarui user: '.$e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $user = User::findOrFail($id);
            UserCv::where('user_id', $user->id)->delete();
            UserTujuan::where('user_id', $user->id)->delete();
            $user->syncRoles([]);
            $user->delete();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'User berhasil dihapus.']);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Gagal menghapus user.'], 500);
        }
    }
}
