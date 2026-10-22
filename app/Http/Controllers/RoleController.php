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
        $modules = Module::orderBy('name')->get();
        $moduleGroups = collect([
            ['name' => 'Dashboard', 'icon' => 'fa-gauge-high', 'keys' => ['dashboard']],
            ['name' => 'Invoicing & PO', 'icon' => 'fa-file-invoice-dollar', 'keys' => ['invoices', 'customers', 'invoice_margin', 'projects']],
            ['name' => 'Buku Kas & Transaksi', 'icon' => 'fa-money-bill-transfer', 'keys' => ['expenses', 'categories']],
            ['name' => 'Laporan Keuangan', 'icon' => 'fa-chart-line', 'keys' => ['financial_reports', 'profit_loss', 'project_margin']],
            ['name' => 'User Management', 'icon' => 'fa-user-gear', 'keys' => ['user_management', 'activity_logs']],
            ['name' => 'Pengaturan', 'icon' => 'fa-sliders', 'keys' => ['settings']],
        ])->map(function (array $group) use ($modules) {
            $group['modules'] = $modules
                ->whereIn('key', $group['keys'])
                ->sortBy(fn (Module $module) => array_search($module->key, $group['keys'], true))
                ->values();

            return $group;
        })->filter(fn (array $group) => $group['modules']->isNotEmpty())
            ->values();

        $groupedKeys = $moduleGroups->flatMap(fn (array $group) => $group['keys']);
        $ungroupedModules = $modules->whereNotIn('key', $groupedKeys)->values();
        if ($ungroupedModules->isNotEmpty()) {
            $moduleGroups->push([
                'name' => 'Modul Lainnya',
                'icon' => 'fa-cubes',
                'modules' => $ungroupedModules,
            ]);
        }

        return view('management.roles.index', compact('roles', 'modules', 'moduleGroups'));
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
        if ($role->hasFullAccess()) {
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