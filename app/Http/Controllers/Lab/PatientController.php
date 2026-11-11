<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Services\AuditRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    public function index(Request $request): Response
    {
        $search = (string) $request->string('q');

        return Inertia::render('lab/patients/index', [
            'patients' => Patient::query()
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($inner) use ($search): void {
                        $inner->where('name', 'like', "%{$search}%")
                            ->orWhere('patient_number', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
                })
                ->latest()
                ->limit(50)
                ->get(),
            'filters' => ['q' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('lab/patients/form', ['patient' => null]);
    }

    public function store(Request $request, AuditRecorder $audit): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'sex' => ['required', 'in:female,male,other'],
            'date_of_birth' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_demo' => ['sometimes', 'boolean'],
        ]);

        $labId = $request->user()?->lab_id;
        abort_if($labId === null, 403);

        $sequence = Patient::query()->count() + 1;
        $patient = Patient::query()->create([
            ...$data,
            'is_demo' => (bool) ($data['is_demo'] ?? false),
            'lab_id' => $labId,
            'patient_number' => 'PAT'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT),
        ]);
        $audit->record($request->user(), 'patient.created', $patient);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Patient registered.']);

        return redirect()->route('patients.show', $patient);
    }

    public function show(Patient $patient): Response
    {
        return Inertia::render('lab/patients/show', [
            'patient' => $patient->load(['orders' => fn ($query) => $query->latest()]),
        ]);
    }
}
