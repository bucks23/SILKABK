<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Role extends Model
{
    protected $fillable = ['name', 'slug'];

    /** Role bawaan sistem (dipakai route & middleware). */
    public const SYSTEM_ROLES = [
        'super_admin' => 'Super Admin',
        'admin'       => 'Admin',
        'orang_tua'   => 'Orang Tua',
        'terapis'     => 'Terapis',
        'dokter'      => 'Dokter',
        'psikolog'    => 'Psikolog',
    ];

    /**
     * Pastikan tabel roles berisi semua role bawaan sistem dan semua role
     * yang sedang dipakai akun pengguna. Role yang belum terdaftar
     * (mis. psikolog pada database lama) ditambahkan otomatis.
     */
    public static function syncAvailable(): void
    {
        $terdaftar = static::pluck('slug')->all();

        $dipakai = User::query()
            ->whereNotNull('role')
            ->distinct()
            ->pluck('role')
            ->all();

        $slugs = array_unique(array_merge(array_keys(self::SYSTEM_ROLES), $dipakai));

        foreach (array_diff($slugs, $terdaftar) as $slug) {
            static::create([
                'slug' => $slug,
                'name' => self::SYSTEM_ROLES[$slug] ?? Str::headline($slug),
            ]);
        }
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // Helper: cek apakah role ini adalah slug tertentu
    public function hasSlug(string $slug): bool
    {
        return $this->slug === $slug;
    }
}