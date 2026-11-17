<?php

namespace App\Http\Controllers;

use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Psychologist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MedicalRecordController extends Controller
{
    // ─── Dokter: Kelola Rekam Medis (US-14) ──────────────────

    public function index(Request $request): Response
    {
        [, $profile, $idField, $view] = $this->resolveProfile('dokter');
        abort_unless($profile || Auth::user()->role === 'super_admin', 403, 'Profil dokter tidak ditemukan.');

        return Inertia::render($view . 'Index', [
            'records' => $this->baseQuery($idField, $profile, $request->search)->paginate(15)->withQueryString(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Doctor/MedicalRecords/Create', [
            'patients' => Patient::where('is_active', true)->get(['id', 'name', 'medical_record_number', 'birth_date']),
        ]);
    }

    public function store(Request $request)
    {
        $doctor = Auth::user()->doctor;

        abort_unless($doctor, 403, 'Profil dokter tidak ditemukan.');

        $validated = $request->validate([
            'patient_id'           => ['required', 'exists:patients,id'],
            'visit_date'           => ['required', 'date'],
            'chief_complaint'      => ['required', 'string'],
            'physical_examination' => ['nullable', 'string'],
            'diagnosis'            => ['required', 'string'],
            'icd_code'             => ['nullable', 'string', 'max:20'],
            'treatment_plan'       => ['nullable', 'string'],
            'kpsp_result'          => ['nullable', 'array'],
            'notes'                => ['nullable', 'string'],
        ], [
            'patient_id.required'      => 'Pasien harus dipilih.',
            'visit_date.required'      => 'Tanggal kunjungan harus diisi.',
            'chief_complaint.required' => 'Keluhan utama harus diisi.',
            'diagnosis.required'       => 'Diagnosis harus diisi.',
        ]);

        MedicalRecord::create([
            ...$validated,
            'doctor_id' => $doctor->id,
        ]);

        return redirect()->route('doctor.medical-records')
            ->with('success', 'Rekam medis berhasil disimpan.');
    }

    public function show(MedicalRecord $record): Response
    {
        $this->authorize('view', $record);

        return Inertia::render('Doctor/MedicalRecords/Show', [
            'record' => $record->load(['patient', 'doctor']),
        ]);
    }

    public function edit(MedicalRecord $record): Response
    {
        $this->authorize('view', $record);

        return Inertia::render('Doctor/MedicalRecords/Edit', [
            'record'   => $record->load(['patient', 'doctor']),
            'patients' => Patient::where('is_active', true)->get(['id', 'name', 'medical_record_number']),
        ]);
    }

    public function update(Request $request, MedicalRecord $record)
    {
        $this->authorize('update', $record);

        $validated = $request->validate([
            'patient_id'           => ['required', 'exists:patients,id'],
            'visit_date'           => ['required', 'date'],
            'chief_complaint'      => ['required', 'string'],
            'physical_examination' => ['nullable', 'string'],
            'diagnosis'            => ['required', 'string'],
            'icd_code'             => ['nullable', 'string', 'max:20'],
            'treatment_plan'       => ['nullable', 'string'],
            'kpsp_result'          => ['nullable', 'array'],
            'notes'                => ['nullable', 'string'],
        ]);

        $record->update($validated);

        return redirect()->route('doctor.medical-records')
            ->with('success', 'Rekam medis berhasil diperbarui.');
    }

    public function saveKpsp(Request $request, MedicalRecord $record)
    {
        $this->authorize('update', $record);

        $validated = $request->validate([
            'kpsp_result' => ['required', 'array'],
        ]);

        $record->update(['kpsp_result' => $validated['kpsp_result']]);

        return back()->with('success', 'Data KPSP berhasil disimpan.');
    }

    // ─── Psikolog: Asesmen & KPSP ─────────────────────────────

    public function indexByPsychologist(Request $request): Response
    {
        [, $profile, $idField, $view] = $this->resolveProfile('psikolog');
        // Psikolog melihat asesmen miliknya; super admin & dokter melihat semua asesmen
        abort_if(Auth::user()->role === 'psikolog' && ! $profile, 403, 'Profil psikolog tidak ditemukan.');

        return Inertia::render($view . 'Index', [
            'records' => $this->baseQuery($idField, $profile, $request->search)->paginate(15)->withQueryString(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function createByPsychologist(): Response
    {
        return Inertia::render('Psychologist/MedicalRecords/Create', [
            'patients'      => Patient::where('is_active', true)->get(['id', 'name', 'medical_record_number', 'birth_date']),
            'psychologists' => $this->psychologistOptions(),
        ]);
    }

    public function storeByPsychologist(Request $request)
    {
        $validated = $request->validate([
            'psychologist_id' => $this->psychologistRule(),
            'patient_id'      => ['required', 'exists:patients,id'],
            'visit_date'      => ['required', 'date'],
            'chief_complaint' => ['required', 'string'],
            'diagnosis'       => ['required', 'string'],
            'treatment_plan'  => ['nullable', 'string'],
            'kpsp_result'     => ['nullable', 'array'],
            'notes'           => ['nullable', 'string'],
        ], [
            'patient_id.required'      => 'Pasien harus dipilih.',
            'visit_date.required'      => 'Tanggal kunjungan harus diisi.',
            'chief_complaint.required' => 'Keluhan utama harus diisi.',
            'diagnosis.required'       => 'Hasil asesmen harus diisi.',
            'psychologist_id.required' => 'Psikolog penanggung jawab harus dipilih.',
        ]);

        MedicalRecord::create([
            ...$validated,
            'psychologist_id' => $this->resolvePsychologistId($validated),
        ]);

        return redirect()->route('psychologist.medical-records')
            ->with('success', 'Rekam medis psikologi berhasil disimpan.');
    }

    public function showByPsychologist(MedicalRecord $record): Response
    {
        $this->authorize('view', $record);

        return Inertia::render('Psychologist/MedicalRecords/Show', [
            'record' => $record->load(['patient', 'psychologist']),
        ]);
    }

    public function editByPsychologist(MedicalRecord $record): Response
    {
        $this->authorize('view', $record);

        return Inertia::render('Psychologist/MedicalRecords/Edit', [
            'record'        => $record->load(['patient', 'psychologist']),
            'patients'      => Patient::where('is_active', true)->get(['id', 'name', 'medical_record_number']),
            'psychologists' => $this->psychologistOptions(),
        ]);
    }

    public function updateByPsychologist(Request $request, MedicalRecord $record)
    {
        $this->authorize('update', $record);

        $validated = $request->validate([
            'psychologist_id' => $this->psychologistRule(),
            'patient_id'      => ['required', 'exists:patients,id'],
            'visit_date'      => ['required', 'date'],
            'chief_complaint' => ['required', 'string'],
            'diagnosis'       => ['required', 'string'],
            'treatment_plan'  => ['nullable', 'string'],
            'kpsp_result'     => ['nullable', 'array'],
            'notes'           => ['nullable', 'string'],
        ], [
            'psychologist_id.required' => 'Psikolog penanggung jawab harus dipilih.',
        ]);

        $record->update([
            ...$validated,
            'psychologist_id' => $this->resolvePsychologistId($validated, $record->psychologist_id),
        ]);

        return redirect()->route('psychologist.medical-records')
            ->with('success', 'Rekam medis psikologi berhasil diperbarui.');
    }

    // ─── Orang Tua: Lihat Rekam Medis Anak ───────────────────

    public function indexByParent(Request $request): Response
    {
        $guardian = Auth::user()->parentGuardian;

        abort_unless($guardian, 403, 'Profil orang tua tidak ditemukan.');

        $records = MedicalRecord::with(['patient', 'doctor', 'psychologist'])
            ->whereHas('patient', fn ($q) => $q->where('guardian_id', $guardian->id))
            ->when($request->patient_id, fn ($q) => $q->where('patient_id', $request->patient_id))
            ->orderBy('visit_date', 'desc')
            ->paginate(10)
            ->withQueryString();

        $patients = Patient::where('guardian_id', $guardian->id)
            ->where('is_active', true)
            ->get(['id', 'name']);

        return Inertia::render('Parent/MedicalRecords', [
            'records'  => $records,
            'patients' => $patients,
            'filters'  => $request->only(['patient_id']),
        ]);
    }

    public function showByParent(MedicalRecord $record): Response
    {
        $guardian = Auth::user()->parentGuardian;

        abort_unless($guardian, 403);

        $patient = $record->patient;
        abort_unless($patient && $patient->guardian_id === $guardian->id, 403, 'Anda tidak memiliki akses ke rekam medis ini.');

        return Inertia::render('Parent/MedicalRecordDetail', [
            'record' => $record->load(['patient', 'doctor', 'psychologist']),
        ]);
    }

    // ─── Helpers ─────────────────────────────────────────────────

    /**
     * Daftar psikolog untuk dipilih super admin / dokter.
     * Psikolog sendiri tidak perlu memilih (otomatis dirinya).
     */
    private function psychologistOptions(): array
    {
        if (Auth::user()->psychologist) {
            return [];
        }

        return Psychologist::orderBy('nama')->get(['id', 'nama'])->all();
    }

    private function psychologistRule(): array
    {
        return Auth::user()->psychologist
            ? ['nullable']
            : ['required', 'exists:psychologists,id'];
    }

    /** Psikolog → dirinya sendiri; super admin / dokter → psikolog yang dipilih. */
    private function resolvePsychologistId(array $validated, ?int $current = null): ?int
    {
        return Auth::user()->psychologist?->id
            ?? (int) ($validated['psychologist_id'] ?? $current);
    }

    private function resolveProfile(string $role): array
    {
        $user = Auth::user();
        return match ($role) {
            'dokter'   => ['doctor',       $user->doctor,       'doctor_id',       'Doctor/MedicalRecords/'],
            'psikolog' => ['psychologist', $user->psychologist, 'psychologist_id', 'Psychologist/MedicalRecords/'],
        };
    }

    private function baseQuery(string $idField, mixed $profile, ?string $search): Builder
    {
        $rel = str_replace('_id', '', $idField);
        return MedicalRecord::with(['patient', $rel])
            ->whereNotNull($idField)
            ->when($profile, fn ($q) => $q->where($idField, $profile->id))
            ->when($search, fn ($q) => $q->whereHas('patient', fn ($sq) => $sq->search($search)))
            ->orderBy('visit_date', 'desc');
    }
}
