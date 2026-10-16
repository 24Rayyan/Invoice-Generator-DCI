<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Module;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('modules')->withCount('users')->orderBy('id')->get();
        $modules = Module::all();
        return view('management.roles.index', compact('roles', 'modules'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'unique:roles,name'],
            'display_name' => ['required', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        Role::create([
            'name' => strtolower(str_replace(' ', '_', $request->name)),
            'display_name' => $request->display_name,
            'description' => $request->description,
        ]);

        return back()->with('success', 'Role baru berhasil ditambahkan.');
    }

    public function destroy(Role $role)
    {
        if (in_array($role->name, ['super_admin', 'admin', 'finance'], true)) {
            return back()->with('error', 'Role bawaan sistem tidak dapat dihapus.');
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'Role tidak dapat dihapus karena masih digunakan oleh user. Pindahkan user ke role lain terlebih dahulu.');
        }

        $displayName = $role->display_name;
        $role->delete();

        return back()->with('success', "Role {$displayName} berhasil dihapus.");
    }

    public function grantModules(Request $request, Role $role)
    {
        $request->validate([
            'modules' => ['nullable', 'array'],
            'modules.*' => ['exists:modules,id'],
        ]);

        // Sync modul yang di-grant ke role
        $role->modules()->sync($request->input('modules', []));

        // Sinkronisasi pivot tidak memicu model event, jadi dicatat manual.
        ActivityLogger::log(
            'UPDATE',
            'User Management',
            "User " . (auth()->user()->username ?? 'Sistem') . " memperbarui hak akses modul untuk role \"{$role->display_name}\".",
            ['role_id' => $role->id, 'modules' => $role->modules()->pluck('key')->all()]
        );

        return back()->with('success', 'Hak akses modul untuk role ' . $role->display_name . ' berhasil diperbarui.');
    }
}