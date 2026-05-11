<?php

namespace App\Http\Controllers;

use App\Services\Datatables\RoleService;
use Exception;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    protected $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Role::with('permissions')->select(['id', 'name', 'guard_name']);

            return $this->roleService->getData($query);
        }

        return view('pages.role.index');
    }

    public function create()
    {
        $permissions = Permission::orderBy('menu_id')->orderBy('name')->get()->groupBy('menu_id');

        return view('pages.role.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        try {
            $role = Role::create(['name' => $request->name, 'guard_name' => 'web']);

            // Konversi ID permission menjadi nama permission
            if ($request->permissions) {
                $validPermissions = Permission::whereIn('id', $request->permissions)->pluck('name')->toArray();
                $role->syncPermissions($validPermissions);
            } else {
                $role->syncPermissions([]);
            }

            return redirect()->route('role.index')->with('success', 'Role berhasil ditambahkan.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan: '.$e->getMessage())->withInput();
        }
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        try {
            $id = decrypt($id);
            $role = Role::with('permissions')->findOrFail($id);
            $permissions = Permission::orderBy('menu_id')->orderBy('name')->get()->groupBy('menu_id');
            $rolePerms = $role->permissions->pluck('id')->toArray();

            return view('pages.role.edit', compact('role', 'permissions', 'rolePerms'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal memuat halaman!');
        }
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,'.$id,
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        try {
            $role = Role::findOrFail($id);
            $role->update(['name' => $request->name]);

            // Konversi ID permission menjadi nama permission
            if ($request->permissions) {
                $validPermissions = Permission::whereIn('id', $request->permissions)->pluck('name')->toArray();
                $role->syncPermissions($validPermissions);
            } else {
                $role->syncPermissions([]);
            }

            return redirect()->route('role.index')->with('success', 'Role berhasil diperbarui.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui: '.$e->getMessage())->withInput();
        }
    }

    public function destroy(string $id)
    {
        try {
            Role::findOrFail($id)->delete();

            return response()->json(['success' => true, 'message' => 'Role berhasil dihapus.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus.'], 500);
        }
    }
}
