<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\TherapyResultController;
use App\Http\Controllers\DiagnosisController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\SdqScreeningController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TherapyTypeController;
use App\Http\Controllers\SpkSkriningController;
use Illuminate\Support\Facades\Route;

// ─── Guest ────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('throttle:5,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});

// ─── Authenticated ────────────────────────────────────────────
Route::middleware(['auth', 'check.active'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profil — semua role
    Route::get('/profile',  [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile',  [ProfileController::class, 'update'])->name('profile.update');

    // Redirect ke sistem SPK Skrining Anak (aplikasi terpisah) — semua role
    Route::get('/spk-skrining', SpkSkriningController::class)->name('spk-skrining');

    // ── Super Admin: kelola akun pengguna (US-03) ─────────────
    Route::middleware('role:super_admin')->group(function () {
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/',                       [UserController::class, 'index'])->name('index');
            Route::get('/create',                 [UserController::class, 'create'])->name('create');
            Route::post('/',                      [UserController::class, 'store'])->name('store');
            Route::get('/{user}/edit',            [UserController::class, 'edit'])->name('edit');
            Route::put('/{user}',                 [UserController::class, 'update'])->name('update');
            Route::patch('/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('toggleActive');
        });

        // Kelola Role
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/',           [RoleController::class, 'index'])->name('index');
            Route::post('/',          [RoleController::class, 'store'])->name('store');
            Route::put('/{role}',     [RoleController::class, 'update'])->name('update');
            Route::delete('/{role}',  [RoleController::class, 'destroy'])->name('destroy');
        });
    });

    // ── Admin & Super Admin: manajemen pasien + laporan ───────
    Route::middleware('role:admin,super_admin')->group(function () {

        // Pasien — CRUD (US-04, US-06)
        Route::prefix('patients')->name('patients.')->group(function () {
            Route::get('/',               [PatientController::class, 'index'])->name('index');
            Route::get('/create',         [PatientController::class, 'create'])->name('create');
            Route::post('/',              [PatientController::class, 'store'])->name('store');
            Route::get('/{patient}',      [PatientController::class, 'show'])->name('show');
            Route::get('/{patient}/edit', [PatientController::class, 'edit'])->name('edit');
            Route::put('/{patient}',      [PatientController::class, 'update'])->name('update');
        });

        // Kelola Biaya / Jenis Layanan
        Route::prefix('therapy-types')->name('therapy-types.')->group(function () {
            Route::get('/',                     [TherapyTypeController::class, 'index'])->name('index');
            Route::get('/create',               [TherapyTypeController::class, 'create'])->name('create');
            Route::post('/',                    [TherapyTypeController::class, 'store'])->name('store');
            Route::get('/{therapyType}/edit',   [TherapyTypeController::class, 'edit'])->name('edit');
            Route::put('/{therapyType}',        [TherapyTypeController::class, 'update'])->name('update');
            Route::delete('/{therapyType}',     [TherapyTypeController::class, 'destroy'])->name('destroy');
        });

        // Registrasi layanan — admin create + approve/reject (US-05)
        Route::prefix('registrations')->name('registrations.')->group(function () {
            Route::get('/',                         [RegistrationController::class, 'index'])->name('index');
            Route::get('/create',                   [RegistrationController::class, 'create'])->name('create');
            Route::post('/',                        [RegistrationController::class, 'store'])->name('store');
            Route::get('/{registration}',           [RegistrationController::class, 'show'])->name('show');
            Route::patch('/{registration}/approve', [RegistrationController::class, 'approve'])->name('approve');
            Route::patch('/{registration}/reject',  [RegistrationController::class, 'reject'])->name('reject');
        });

        // Pembayaran — laporan & pencatatan (US-07, US-08)
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/',               [PaymentController::class, 'index'])->name('index');
            Route::get('/create',         [PaymentController::class, 'create'])->name('create');
            Route::post('/',              [PaymentController::class, 'store'])->name('store');
            Route::get('/{payment}',      [PaymentController::class, 'show'])->name('show');
            Route::get('/{payment}/edit', [PaymentController::class, 'edit'])->name('edit');
            Route::put('/{payment}',      [PaymentController::class, 'update'])->name('update');
        });

        // Jadwal terapi — kelola (US-10)
        Route::prefix('schedules')->name('schedules.')->group(function () {
            Route::get('/',                    [ScheduleController::class, 'index'])->name('index');
            Route::get('/create',              [ScheduleController::class, 'create'])->name('create');
            Route::post('/generate',           [ScheduleController::class, 'generateWeekSlots'])->name('generate');
            Route::post('/',                   [ScheduleController::class, 'store'])->name('store');
            Route::get('/{schedule}',          [ScheduleController::class, 'show'])->name('show');
            Route::get('/{schedule}/edit',     [ScheduleController::class, 'edit'])->name('edit');
            Route::put('/{schedule}',          [ScheduleController::class, 'update'])->name('update');
            Route::patch('/{schedule}/cancel', [ScheduleController::class, 'cancel'])->name('cancel');
        });

        // Laporan
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

        // Export Excel (CSV)
        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/patients',      [ExportController::class, 'patients'])->name('patients');
            Route::get('/registrations', [ExportController::class, 'registrations'])->name('registrations');
            Route::get('/payments',      [ExportController::class, 'payments'])->name('payments');
            Route::get('/schedules',     [ExportController::class, 'schedules'])->name('schedules');
            Route::get('/report',        [ExportController::class, 'report'])->name('report');
        });
    });

    // ── Orang Tua: akses data anak sendiri saja ───────────────
    // Constraint ownership ditangani di dalam controller via Policy
    Route::middleware('role:orang_tua,super_admin')->prefix('parent')->name('parent.')->group(function () {

        // Tambah anak (pasien baru milik orang tua ini)
        Route::get('/children/create', [PatientController::class, 'createByParent'])->name('children.create');
        Route::post('/children',       [PatientController::class, 'storeByParent'])->name('children.store');

        // Daftar layanan untuk anak sendiri (US-05)
        Route::get('/register',           [RegistrationController::class, 'createByParent'])->name('register');
        Route::post('/register',          [RegistrationController::class, 'storeByParent'])->name('register.store');
        Route::get('/registrations',      [RegistrationController::class, 'indexByParent'])->name('registrations');

        // Lihat jadwal anak sendiri — view only, tidak bisa edit/hapus (US-11)
        Route::get('/schedules',          [ScheduleController::class, 'indexByParent'])->name('schedules');
        Route::get('/schedules/{schedule}', [ScheduleController::class, 'showByParent'])->name('schedules.show');

        // Riwayat & bukti pembayaran anak sendiri (US-09)
        Route::get('/payments',           [PaymentController::class, 'indexByParent'])->name('payments');
        Route::get('/payments/{payment}', [PaymentController::class, 'showByParent'])->name('payments.show');

        // Riwayat diagnosa anak sendiri (read-only)
        Route::get('/diagnoses',              [DiagnosisController::class, 'indexByParent'])->name('diagnoses');
        Route::get('/diagnoses/{diagnosis}',  [DiagnosisController::class, 'showByParent'])->name('diagnoses.show');

        // Kuesioner SDQ — diisi orang tua (US-15 tambahan)
        Route::get('/sdq',               [SdqScreeningController::class, 'index'])->name('sdq.index');
        Route::get('/sdq/create',        [SdqScreeningController::class, 'create'])->name('sdq.create');
        Route::post('/sdq',              [SdqScreeningController::class, 'store'])->name('sdq.store');
        Route::get('/sdq/{screening}',   [SdqScreeningController::class, 'show'])->name('sdq.show');

    });

    // ── Terapis: jadwal sendiri + input data pasien (US-12, US-13) ──
    Route::middleware('role:terapis,super_admin')->prefix('therapist')->name('therapist.')->group(function () {

        // Lihat jadwal sendiri (US-12)
        Route::get('/schedules',             [ScheduleController::class, 'indexByTherapist'])->name('schedules');
        Route::get('/schedules/{schedule}',  [ScheduleController::class, 'showByTherapist'])->name('schedules.show');

        // Input data / hasil terapi pasien per sesi (US-13)
        Route::get('/results/create/{schedule}', [TherapyResultController::class, 'create'])->name('results.create');
        Route::post('/results',                  [TherapyResultController::class, 'store'])->name('results.store');
        Route::get('/results/{result}/edit',     [TherapyResultController::class, 'edit'])->name('results.edit');
        Route::put('/results/{result}',          [TherapyResultController::class, 'update'])->name('results.update');
    });

    // ── Dokter: screening KPSP & rekam medis (US-14) ──────────
    Route::middleware('role:dokter,super_admin')->prefix('doctor')->name('doctor.')->group(function () {

        // Rekam medis elektronik pasien
        Route::get('/medical-records',               [MedicalRecordController::class, 'index'])->name('medical-records');
        Route::get('/medical-records/create',        [MedicalRecordController::class, 'create'])->name('medical-records.create');
        Route::post('/medical-records',              [MedicalRecordController::class, 'store'])->name('medical-records.store');
        Route::get('/medical-records/{record}',      [MedicalRecordController::class, 'show'])->name('medical-records.show');
        Route::get('/medical-records/{record}/edit', [MedicalRecordController::class, 'edit'])->name('medical-records.edit');
        Route::put('/medical-records/{record}',      [MedicalRecordController::class, 'update'])->name('medical-records.update');

        // Simpan hasil screening KPSP ke rekam medis
        Route::post('/medical-records/{record}/kpsp', [MedicalRecordController::class, 'saveKpsp'])->name('medical-records.kpsp');

        // Lihat SDQ pasien
        Route::get('/sdq/{screening}',          [SdqScreeningController::class, 'showForProfessional'])->name('sdq.show');
        Route::get('/sdq/patient/{patient}',    [SdqScreeningController::class, 'indexForPatient'])->name('sdq.patient');

        // Diagnosa penyakit
        Route::get('/diagnoses',                    [DiagnosisController::class, 'index'])->name('diagnoses.index');
        Route::get('/diagnoses/create',             [DiagnosisController::class, 'create'])->name('diagnoses.create');
        Route::post('/diagnoses',                   [DiagnosisController::class, 'store'])->name('diagnoses.store');
        Route::get('/diagnoses/{diagnosis}',        [DiagnosisController::class, 'show'])->name('diagnoses.show');
        Route::get('/diagnoses/{diagnosis}/edit',   [DiagnosisController::class, 'edit'])->name('diagnoses.edit');
        Route::put('/diagnoses/{diagnosis}',        [DiagnosisController::class, 'update'])->name('diagnoses.update');
        Route::delete('/diagnoses/{diagnosis}',     [DiagnosisController::class, 'destroy'])->name('diagnoses.destroy');
    });

    // ── Psikolog: asesmen psikologi & screening KPSP (US-13, US-14) ──────────
    // Super admin dan dokter juga dapat mengelola asesmen.
    Route::middleware('role:psikolog,dokter,super_admin')->prefix('psychologist')->name('psychologist.')->group(function () {

        // Rekam medis & asesmen psikologi
        Route::get('/medical-records',               [MedicalRecordController::class, 'indexByPsychologist'])->name('medical-records');
        Route::get('/medical-records/create',        [MedicalRecordController::class, 'createByPsychologist'])->name('medical-records.create');
        Route::post('/medical-records',              [MedicalRecordController::class, 'storeByPsychologist'])->name('medical-records.store');
        Route::get('/medical-records/{record}',      [MedicalRecordController::class, 'showByPsychologist'])->name('medical-records.show');
        Route::get('/medical-records/{record}/edit', [MedicalRecordController::class, 'editByPsychologist'])->name('medical-records.edit');
        Route::put('/medical-records/{record}',      [MedicalRecordController::class, 'updateByPsychologist'])->name('medical-records.update');

        // Simpan hasil screening KPSP ke rekam medis
        Route::post('/medical-records/{record}/kpsp', [MedicalRecordController::class, 'saveKpsp'])->name('medical-records.kpsp');

        // Lihat SDQ pasien
        Route::get('/sdq/{screening}',          [SdqScreeningController::class, 'showForProfessional'])->name('sdq.show');
        Route::get('/sdq/patient/{patient}',    [SdqScreeningController::class, 'indexForPatient'])->name('sdq.patient');
    });

});

Route::get('/', fn () => redirect()->route('dashboard'));