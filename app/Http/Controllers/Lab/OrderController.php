<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Patient;
use App\Models\ReferringDoctor;
use App\Models\TestDefinition;
use App\Models\User;
use App\Services\AuditRecorder;
use App\Services\CatalogueScope;
use App\Services\OrderService;
use App\Services\ResultService;
use App\Support\LabAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('lab/orders/index', [
            'orders' => Order::query()
                ->with('patient:id,name,patient_number,is_demo', 'items.test:id,name')
                ->latest()
                ->limit(50)
                ->get(),
        ]);
    }

    public function create(Request $request, CatalogueScope $scope): Response
    {
        $user = $request->user();
        abort_if($user === null, 403);

        return Inertia::render('lab/orders/create', [
            'patients' => Patient::query()->orderBy('name')->get(['id', 'name', 'patient_number', 'sex', 'date_of_birth', 'phone', 'is_demo'])
                ->map(fn (Patient $patient): array => [
                    'id' => $patient->id,
                    'name' => $patient->name,
                    'patient_number' => $patient->patient_number,
                    'sex' => $patient->sex,
                    'date_of_birth' => $patient->date_of_birth?->toDateString(),
                    'age' => $patient->ageYears(),
                    'phone' => $patient->phone,
                    'is_demo' => $patient->is_demo,
                ]),
            'doctors' => ReferringDoctor::query()->orderBy('name')->get(['id', 'name', 'qualification']),
            'canEnterResults' => LabAccess::allows($user, 'result.enter'),
            'tests' => $scope->orderable($user)->map(fn (TestDefinition $test): array => [
                'id' => $test->id,
                'name' => $test->name,
                'code' => $test->code,
                'price_paise' => $test->price_paise,
                'is_panel' => $test->is_panel,
                'scope' => $test->owner_user_id === null ? 'main' : ($test->source_test_id === null ? 'personal' : 'copy'),
                'department' => $test->department,
                'specimen_type' => $test->specimenType,
            ])->values(),
        ]);
    }

    public function store(Request $request, OrderService $orders, AuditRecorder $audit): RedirectResponse
    {
        $data = $request->validate([
            'patient_mode' => ['required', 'in:new,existing'],
            'patient_id' => ['required_if:patient_mode,existing', 'nullable', 'exists:patients,id'],
            'patient' => ['required_if:patient_mode,new', 'array'],
            'patient.name' => ['required_if:patient_mode,new', 'nullable', 'string', 'max:120'],
            'patient.sex' => ['required_if:patient_mode,new', 'nullable', 'in:female,male,other'],
            'patient.age' => ['required_if:patient_mode,new', 'nullable', 'integer', 'min:0', 'max:120'],
            'patient.phone' => ['nullable', 'string', 'max:30'],
            'referring_doctor_id' => ['nullable', 'exists:referring_doctors,id'],
            'priority' => ['required', 'in:routine,urgent'],
            'clinical_notes' => ['nullable', 'string', 'max:1000'],
            'test_ids' => ['required', 'array', 'min:1'],
            'test_ids.*' => ['integer', 'exists:test_definitions,id'],
        ]);

        $user = $request->user();
        $branch = $user?->branch;
        abort_if($user === null || $branch === null || $user->lab_id === null, 403);
        if ($data['patient_mode'] === 'new') {
            $patient = $this->registerPatient($user, is_array($data['patient'] ?? null) ? $data['patient'] : [], $audit);
        } else {
            $patientId = $data['patient_id'];
            abort_unless(is_numeric($patientId), 422);
            $patient = Patient::query()->findOrFail((int) $patientId);
        }
        $doctorId = $data['referring_doctor_id'] ?? null;
        $testIds = [];

        foreach ($data['test_ids'] as $testId) {
            abort_unless(is_int($testId) || (is_string($testId) && ctype_digit($testId)), 422);
            $testIds[] = (int) $testId;
        }

        $order = $orders->place(
            $user,
            $patient,
            $branch,
            $testIds,
            $data['priority'],
            is_numeric($doctorId) ? (int) $doctorId : null,
            $data['clinical_notes'] ?? null,
        );
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Order '.$order->order_number.' created. Enter the results.']);

        if (LabAccess::allows($user, 'result.enter')) {
            return redirect()->route('orders.results', $order);
        }

        return redirect()->route('orders.show', $order);
    }

    private function birthDateFromAge(mixed $age): ?string
    {
        if (! is_numeric($age)) {
            return null;
        }

        return now()->subYears((int) $age)->toDateString();
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function registerPatient(User $user, array $details, AuditRecorder $audit): Patient
    {
        $sequence = Patient::query()->count() + 1;
        $patient = Patient::query()->create([
            'lab_id' => $user->lab_id,
            'patient_number' => 'PAT'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT),
            'name' => (string) ($details['name'] ?? ''),
            'sex' => (string) ($details['sex'] ?? 'other'),
            'date_of_birth' => $this->birthDateFromAge($details['age'] ?? null),
            'phone' => $details['phone'] ?? null,
            'is_demo' => false,
        ]);
        $audit->record($user, 'patient.created', $patient);

        return $patient;
    }

    public function show(Order $order): Response
    {
        $order->load([
            'patient',
            'doctor',
            'invoice',
            'specimens.specimenType',
            'specimens.events.user:id,name',
            'items.test.specimenType',
            'items.test.resultDefinitions',
            'items.test.components.resultDefinitions',
            'items.results.definition',
            'items.results.criticalAlert',
            'reports',
        ]);

        return Inertia::render('lab/orders/show', [
            'order' => $order,
        ]);
    }

    public function results(Order $order): Response
    {
        $order->load([
            'patient',
            'items.test.resultDefinitions.referenceIntervals',
            'items.test.resultDefinitions.criticalLimits',
            'items.test.components.resultDefinitions.referenceIntervals',
            'items.test.components.resultDefinitions.criticalLimits',
            'items.results',
        ]);

        return Inertia::render('lab/orders/results', [
            'order' => $order,
        ]);
    }

    public function storeResults(Request $request, Order $order, ResultService $results): RedirectResponse
    {
        $data = $request->validate([
            'results' => ['required', 'array'],
            'results.*.value' => ['nullable', 'string', 'max:2000'],
            'results.*.comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $entries = [];
        foreach ($data['results'] as $id => $entry) {
            $entries[(int) $id] = [
                'value' => $entry['value'] ?? null,
                'comment' => $entry['comment'] ?? null,
            ];
        }

        $user = $request->user();
        abort_if($user === null, 403);
        $results->save($order, $user, $entries);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Results saved.']);

        return redirect()->route('orders.show', $order);
    }

    public function review(Request $request, Order $order, ResultService $results): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);
        $results->reviewTechnically($order, $user);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Technical review recorded.']);

        return back();
    }

    public function validateOrder(Request $request, Order $order, ResultService $results): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);
        $results->validateClinically($order, $user);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Results validated.']);

        return back();
    }
}
