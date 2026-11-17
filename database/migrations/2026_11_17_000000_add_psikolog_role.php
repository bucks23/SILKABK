<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menambahkan role "Psikolog" ke tabel roles.
 *
 * RoleSeeder sudah memuat role ini, tetapi database yang dipakai
 * (lokal dan server, dari file dump) dibuat sebelum role psikolog ada,
 * sehingga psikolog tidak muncul pada pilihan role saat membuat akun.
 * Migration ini berjalan otomatis saat deploy dan aman diulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('roles')->where('slug', 'psikolog')->exists()) {
            return;
        }

        DB::table('roles')->insert([
            'name'       => 'Psikolog',
            'slug'       => 'psikolog',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Tidak menghapus role yang mungkin sudah dipakai akun psikolog.
    }
};
