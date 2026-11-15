<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Memastikan user database baca-saja "spk_reader" (dipakai SPK Skrining Anak
 * untuk membaca database SILKABK) ada dan password-nya = SPK_READER_PASSWORD.
 * Dijalankan otomatis setiap container backend start (docker/production/entrypoint.sh),
 * sehingga mengubah SPK_READER_PASSWORD cukup dengan redeploy.
 */
Artisan::command('spk:ensure-reader', function () {
    $password = (string) config('services.spk_skrining.reader_password');

    if ($password === '') {
        $this->warn('SPK_READER_PASSWORD kosong, user spk_reader tidak diatur.');

        return 0;
    }

    if (DB::connection()->getDriverName() !== 'pgsql') {
        $this->warn('Bukan PostgreSQL, dilewati.');

        return 0;
    }

    $quoted   = DB::connection()->getPdo()->quote($password);
    $database = str_replace('"', '""', DB::connection()->getDatabaseName());
    $exists   = DB::selectOne("SELECT 1 AS ada FROM pg_roles WHERE rolname = 'spk_reader'");

    DB::statement($exists
        ? "ALTER ROLE spk_reader WITH LOGIN PASSWORD {$quoted}"
        : "CREATE ROLE spk_reader LOGIN PASSWORD {$quoted}");

    DB::statement("GRANT CONNECT ON DATABASE \"{$database}\" TO spk_reader");
    DB::statement('GRANT USAGE ON SCHEMA public TO spk_reader');
    DB::statement('GRANT SELECT ON ALL TABLES IN SCHEMA public TO spk_reader');
    DB::statement('ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT ON TABLES TO spk_reader');

    $this->info('User spk_reader siap ('.($exists ? 'password diperbarui' : 'baru dibuat').').');

    return 0;
})->purpose('Pastikan user database baca-saja spk_reader untuk SPK Skrining Anak');
