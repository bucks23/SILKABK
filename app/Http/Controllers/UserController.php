<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\ParentGuardian;
use App\Models\Psychologist;
use App\Models\Role;
use App\Models\Therapist;
use App\Models\TherapyType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    // ─── US-03: Kelola Akun Pengguna ──────────────────────────

    public function index(Request $request): Response
    {
        $users = User::query()
            ->when($request->search, fn ($q) => $q->where(fn ($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
            ))
            ->when($request->role, fn ($q) => $q->where('role', $request->role))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->status === 'active'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'users'   => $users,
            'roles'   => $this->roleOptions(),
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Users/Create', [
            'roles'        => $this->roleOptions(),
            'therapyTypes' => TherapyType::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateUser($request);

        if ($validated['role'] === 'terapis') {
            $request->validate([
                'therapy_type_id' => ['required', 'exists:therapy_types,id'],
            ], [
                'therapy_type_id.required' => 'Jenis terapi harus dipilih.',
                'therapy_type_id.exists'   => 'Jenis terapi tidak valid.',
            ]);
        }

        DB::transaction(function () use ($validated, $request) {
            $user = User::create([
                ...$validated,
                'password'  => Hash::make($validated['password']),
                'is_active' => true,
            ]);

            $this->syncProfile($user, $validated['role'], $request);
        });

        return redirect()->route('users.index')
            ->with('success', 'Akun pengguna berhasil dibuat.');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Users/Edit', [
            'user'         => $user->load(['therapist', 'doctor', 'psychologist', 'parentGuardian']),
            'roles'        => $this->roleOptions(),
            'therapyTypes' => TherapyType::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $this->validateUser($request, $user);

        if ($validated['role'] === 'terapis') {
            $request->validate([
                'therapy_type_id' => ['required', 'exists:therapy_types,id'],
            ], [
                'therapy_type_id.required' => 'Jenis terapi harus dipilih.',
                'therapy_type_id.exists'   => 'Jenis terapi tidak valid.',
            ]);
        }

        if (filled($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        DB::transaction(function () use ($validated, $request, $user) {
            $user->update($validated);
            $user->refresh();

            $this->syncProfile($user, $validated['role'], $request);
        });

        return redirect()->route('users.index')
            ->with('success', 'Akun pengguna berhasil diperbarui.');
    }

    public function toggleActive(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->withErrors(['error' => 'Tidak dapat mengubah status akun sendiri.']);
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', "Akun {$user->name} berhasil " . ($user->is_active ? 'diaktifkan' : 'dinonaktifkan') . ".");
    }

    // ─── Helper Methods ───────────────────────────────────────

    private function syncProfile(User $user, string $role, Request $request): void
    {
        match ($role) {
            'terapis' => $this->syncTherapist($user, $request),
            'dokter'  => $this->syncDoctor($user),
            'psikolog' => $this->syncPsychologist($user),
            'orang_tua' => $this->syncParentGuardian($user),
            default   => null,
        };
    }

    private function syncTherapist(User $user, Request $request): void
    {
        $therapyType = TherapyType::findOrFail($request->therapy_type_id);

        if ($user->therapist) {
            $user->therapist->update([
                'name'            => $user->name,
                'specialization'  => $therapyType->name,
                'therapy_type_id' => $therapyType->id,
            ]);
        } else {
            Therapist::create([
                'user_id'         => $user->id,
                'name'            => $user->name,
                'specialization'  => $therapyType->name,
                'therapy_type_id' => $therapyType->id,
                'is_active'       => true,
            ]);
        }
    }

    private function syncDoctor(User $user): void
    {
        if ($user->doctor) {
            $user->doctor->update(['name' => $user->name]);
        } else {
            Doctor::create([
                'user_id' => $user->id,
                'name'    => $user->name,
            ]);
        }
    }

    private function syncPsychologist(User $user): void
    {
        if ($user->psychologist) {
            $user->psychologist->update(['nama' => $user->name]);
        } else {
            Psychologist::create([
                'user_id' => $user->id,
                'nama'    => $user->name,
            ]);
        }
    }

    private function syncParentGuardian(User $user): void
    {
        if ($user->parentGuardian) {
            $user->parentGuardian->update(['name' => $user->name]);
        } else {
            ParentGuardian::create([
                'user_id' => $user->id,
                'name'    => $user->name,
            ]);
        }
    }

    private function roleOptions(): array
    {
        Role::syncAvailable();

        return Role::orderBy('id')
            ->get()
            ->map(fn ($r) => ['value' => $r->slug, 'label' => $r->name])
            ->all();
    }

    private function roleSlugs(): string
    {
        return Role::pluck('slug')->implode(',');
    }

    private function validateUser(Request $request, ?User $user = null): array
    {
        Role::syncAvailable();
        $validSlugs = Role::pluck('slug')->all();
        $rules = [
            'role'     => ['required', Rule::in($validSlugs)],
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'max:100', Rule::unique('users')->ignore($user)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ];

        if ($user) {
            $rules['is_active'] = ['required', 'boolean'];
        }

        return $request->validate($rules, [
            'role.required'      => 'Role wajib dipilih.',
            'role.in'            => 'Role tidak valid.',
            'name.required'      => 'Nama wajib diisi.',
            'email.required'     => 'Email wajib diisi.',
            'email.unique'       => 'Email sudah digunakan.',
            'password.required'  => 'Password wajib diisi.',
            'password.min'       => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);
    }
}
