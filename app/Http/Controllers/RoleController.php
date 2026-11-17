<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(): Response
    {
        // Baca otomatis: role bawaan + role yang dipakai akun ikut terdaftar
        Role::syncAvailable();

        $roles = Role::orderBy('id')
            ->get()
            ->map(fn ($role) => [
                'id'          => $role->id,
                'name'        => $role->name,
                'slug'        => $role->slug,
                'users_count' => User::where('role', $role->slug)->count(),
            ]);

        return Inertia::render('Roles/Index', [
            'roles'   => $roles,
            'summary' => [
                'total_roles' => $roles->count(),
                'total_users' => User::count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('roles', 'name')],
            'slug' => ['required', 'string', 'max:50', 'regex:/^[a-z_]+$/', Rule::unique('roles', 'slug')],
        ], [
            'name.required' => 'Nama role wajib diisi.',
            'name.unique'   => 'Nama role sudah ada.',
            'slug.required' => 'Slug role wajib diisi.',
            'slug.unique'   => 'Slug role sudah ada.',
            'slug.regex'    => 'Slug hanya boleh huruf kecil dan underscore.',
        ]);

        Role::create($validated);

        return back()->with('success', "Role \"{$validated['name']}\" berhasil ditambahkan.");
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('roles', 'name')->ignore($role)],
        ], [
            'name.required' => 'Nama role wajib diisi.',
            'name.unique'   => 'Nama role sudah ada.',
        ]);

        $role->update(['name' => $validated['name']]);

        return back()->with('success', 'Nama role berhasil diperbarui.');
    }

    public function destroy(Role $role)
    {
        $systemSlugs = ['super_admin', 'admin', 'orang_tua', 'terapis', 'dokter', 'psikolog'];

        if (in_array($role->slug, $systemSlugs)) {
            return back()->withErrors(['error' => 'Role sistem tidak dapat dihapus.']);
        }

        if (User::where('role', $role->slug)->exists()) {
            return back()->withErrors(['error' => "Tidak dapat menghapus role \"{$role->name}\" karena masih ada pengguna yang menggunakannya."]);
        }

        $role->delete();

        return back()->with('success', "Role \"{$role->name}\" berhasil dihapus.");
    }
}
