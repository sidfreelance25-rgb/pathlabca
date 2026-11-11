<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\SpecimenType;
use App\Models\TestDefinition;
use App\Services\CatalogueScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogueController extends Controller
{
    public function index(Request $request, CatalogueScope $scope): Response
    {
        $user = $request->user();
        abort_if($user === null, 403);
        $discipline = (string) $request->string('discipline');

        return Inertia::render('lab/catalogue/index', [
            'departments' => Department::query()->orderBy('name')->get(['id', 'code', 'name']),
            'specimens' => SpecimenType::query()->orderBy('name')->get(['id', 'name']),
            'tests' => $scope->rows($user, $discipline),
            'filters' => ['discipline' => $discipline],
            'canManageMain' => $user->isLabAdmin(),
        ]);
    }

    public function show(Request $request, TestDefinition $test, CatalogueScope $scope): Response
    {
        $user = $request->user();
        abort_if($user === null, 403);
        $scope->visibleTo($user, $test);
        $test->load([
            'department',
            'specimenType',
            'owner:id,name',
            'components.specimenType',
            'components.resultDefinitions.referenceIntervals',
            'components.resultDefinitions.criticalLimits',
            'resultDefinitions.referenceIntervals',
            'resultDefinitions.criticalLimits',
        ]);

        return Inertia::render('lab/catalogue/show', [
            'test' => $test,
            'scope' => $scope->scope($test),
            'ownerName' => $test->owner?->name,
            'canPromote' => $user->isLabAdmin() && $test->owner_user_id !== null,
            'canManageMain' => $user->isLabAdmin(),
        ]);
    }

    public function update(Request $request, TestDefinition $test, CatalogueScope $scope): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'price_rupees' => ['required', 'numeric', 'min:0'],
            'active' => ['sometimes', 'boolean'],
        ]);
        $saved = $scope->update(
            $user,
            $test,
            $data['name'],
            (int) round(((float) $data['price_rupees']) * 100),
            (bool) ($data['active'] ?? true),
        );
        $message = $saved->is($test)
            ? ($saved->owner_user_id === null ? 'Updated the main test for everyone.' : 'Saved your test.')
            : 'Saved your copy. The main test is unchanged.';
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return $saved->is($test) ? back() : redirect()->route('catalogue.show', $saved);
    }

    public function store(Request $request, CatalogueScope $scope): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'code' => ['required', 'string', 'max:40'],
            'department_id' => ['required', 'exists:departments,id'],
            'specimen_type_id' => ['required', 'exists:specimen_types,id'],
            'price_rupees' => ['required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:40'],
        ]);
        $user = $request->user();
        abort_if($user === null, 403);
        $code = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $data['code']));
        abort_if($code === '', 422);
        $departmentId = $data['department_id'];
        $specimenId = $data['specimen_type_id'];
        abort_unless(is_numeric($departmentId) && is_numeric($specimenId), 422);

        $test = $scope->create($user, [
            'name' => $data['name'],
            'code' => $code,
            'department_id' => (int) $departmentId,
            'specimen_type_id' => (int) $specimenId,
            'price_paise' => (int) round(((float) $data['price_rupees']) * 100),
            'unit' => is_string($data['unit'] ?? null) ? $data['unit'] : null,
        ]);
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $test->owner_user_id === null ? 'Added to the main catalogue.' : 'Added to your catalogue.',
        ]);

        return redirect()->route('catalogue.show', $test);
    }

    public function promote(Request $request, TestDefinition $test, CatalogueScope $scope): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);
        $promoted = $scope->promote($user, $test);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Promoted to the main catalogue.']);

        return redirect()->route('catalogue.show', $promoted);
    }
}
