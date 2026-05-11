<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Cv;
use App\Models\User;
use App\Models\UserCv;
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

        return view('pages.user.create', [
            'data' => $data,
            'roles' => Role::all(),
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
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'level' => $request->level,
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
            $cv = Cv::all();
            foreach ($data->roles as $role) {
                $user_roles[] = $role->id;
            }

            foreach ($data->userCv as $v) {
                $user_cv[] = $v->cv_id;
            }

            return view('pages.user.edit', [
                'user' => $data,
                'user_roles' => $user_roles,
                'roles' => $roles,
                'user_cv' => $user_cv,
                'cv' => $cv,
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
        ]);

        DB::beginTransaction();
        try {
            $user = User::findOrFail($id);

            $user->update([
                'name' => $request->name,
                'level' => $request->level,
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
