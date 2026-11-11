<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\TestDefinition;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogueController extends Controller
{
    public function index(Request $request): Response
    {
        $discipline = (string) $request->string('discipline');

        return Inertia::render('lab/catalogue/index', [
            'departments' => Department::query()->orderBy('name')->get(['id', 'code', 'name']),
            'tests' => TestDefinition::query()
                ->with('department:id,name', 'specimenType:id,name,container')
                ->when($discipline !== '', fn ($query) => $query->where('department_id', $discipline))
                ->orderBy('report_section')
                ->orderBy('display_order')
                ->get(),
            'filters' => ['discipline' => $discipline],
        ]);
    }

    public function show(TestDefinition $test): Response
    {
        $test->load([
            'department',
            'specimenType',
            'components.specimenType',
            'components.resultDefinitions.referenceIntervals',
            'components.resultDefinitions.criticalLimits',
            'resultDefinitions.referenceIntervals',
            'resultDefinitions.criticalLimits',
        ]);

        return Inertia::render('lab/catalogue/show', [
            'test' => $test,
        ]);
    }
}
