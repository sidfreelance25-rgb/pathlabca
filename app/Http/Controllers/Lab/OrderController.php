<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Patient;
use App\Models\ReferringDoctor;
use App\Models\TestDefinition;
use App\Services\OrderService;
use App\Services\ResultService;
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

    public function create(): Response
    {
        return Inertia::render('lab/orders/create', [
            'patients' => Patient::query()->orderBy('name')->get(['id', 'name', 'patient_number', 'is_demo']),
            'doctors' => ReferringDoctor::query()->orderBy('name')->get(['id', 'name', 'qualification']),
            'tests' => TestDefinition::query()
                ->with('department:id,name', 'specimenType:id,name')
                ->where('active', true)
                ->orderBy('display_order')
                ->get(['id', 'name', 'code', 'department_id', 'specimen_type_id', 'price_paise', 'is_panel', 'tat_minutes']),
        ]);
    }

    public function store(Request $request, OrderService $orders): RedirectResponse
    {
        $data = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'referring_doctor_id' => ['nullable', 'exists:referring_doctors,id'],
            'priority' => ['required', 'in:routine,urgent'],
            'clinical_notes' => ['nullable', 'string', 'max:1000'],
            'test_ids' => ['required', 'array', 'min:1'],
            'test_ids.*' => ['integer', 'exists:test_definitions,id'],
        ]);

        $user = $request->user();
        $branch = $user?->branch;
        abort_if($user === null || $branch === null, 403);
        $patientId = $data['patient_id'];
        abort_unless(is_int($patientId) || (is_string($patientId) && ctype_digit($patientId)), 422);
        $patient = Patient::query()->findOrFail($patientId);
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
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Order '.$order->order_number.' created.']);

        return redirect()->route('orders.show', $order);
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
