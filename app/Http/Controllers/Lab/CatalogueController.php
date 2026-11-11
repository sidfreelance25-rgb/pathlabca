<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\ResultDefinition;
use App\Models\SpecimenType;
use App\Models\TestDefinition;
use App\Models\UserTestSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogueController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_if($user === null, 403);
        $discipline = (string) $request->string('discipline');
        $settings = UserTestSetting::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('test_definition_id');

        $tests = TestDefinition::query()
            ->with('department:id,name', 'specimenType:id,name,container')
            ->where(function ($query) use ($user): void {
                $query->whereNull('owner_user_id')->orWhere('owner_user_id', $user->id);
            })
            ->when($discipline !== '', fn ($query) => $query->where('department_id', $discipline))
            ->orderBy('report_section')
            ->orderBy('display_order')
            ->get()
            ->map(function (TestDefinition $test) use ($settings): ?array {
                $setting = $settings->get($test->id);

                if ($setting instanceof UserTestSetting && ! $setting->active) {
                    return null;
                }

                return [
                    'id' => $test->id,
                    'code' => $test->code,
                    'name' => ($setting instanceof UserTestSetting && $setting->name) ? $setting->name : $test->name,
                    'is_panel' => $test->is_panel,
                    'price_paise' => ($setting instanceof UserTestSetting && $setting->price_paise !== null) ? $setting->price_paise : $test->price_paise,
                    'catalogue_price_paise' => $test->price_paise,
                    'tat_minutes' => $test->tat_minutes,
                    'personal' => $test->owner_user_id !== null || $setting instanceof UserTestSetting,
                    'custom' => $test->owner_user_id !== null,
                    'department' => $test->department,
                    'specimen_type' => $test->specimenType,
                ];
            })
            ->filter()
            ->values();

        return Inertia::render('lab/catalogue/index', [
            'departments' => Department::query()->orderBy('name')->get(['id', 'code', 'name']),
            'specimens' => SpecimenType::query()->orderBy('name')->get(['id', 'name']),
            'tests' => $tests,
            'filters' => ['discipline' => $discipline],
        ]);
    }

    public function show(Request $request, TestDefinition $test): Response
    {
        $this->visibleTo($request, $test);
        $test->load([
            'department',
            'specimenType',
            'components.specimenType',
            'components.resultDefinitions.referenceIntervals',
            'components.resultDefinitions.criticalLimits',
            'resultDefinitions.referenceIntervals',
            'resultDefinitions.criticalLimits',
        ]);
        $setting = UserTestSetting::query()
            ->where('user_id', $request->user()?->id)
            ->where('test_definition_id', $test->id)
            ->first();

        return Inertia::render('lab/catalogue/show', [
            'test' => $test,
            'personal' => [
                'name' => $setting->name ?? $test->name,
                'price_paise' => $setting->price_paise ?? $test->price_paise,
                'active' => $setting->active ?? true,
                'custom' => $test->owner_user_id !== null,
            ],
        ]);
    }

    public function update(Request $request, TestDefinition $test): RedirectResponse
    {
        $this->visibleTo($request, $test);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'price_rupees' => ['required', 'numeric', 'min:0'],
            'active' => ['sometimes', 'boolean'],
        ]);
        $user = $request->user();
        abort_if($user === null, 403);

        if ($test->owner_user_id === $user->id) {
            $test->update([
                'name' => $data['name'],
                'price_paise' => (int) round(((float) $data['price_rupees']) * 100),
                'active' => (bool) ($data['active'] ?? true),
            ]);
        } else {
            UserTestSetting::query()->updateOrCreate(
                ['user_id' => $user->id, 'test_definition_id' => $test->id],
                [
                    'name' => $data['name'],
                    'price_paise' => (int) round(((float) $data['price_rupees']) * 100),
                    'active' => (bool) ($data['active'] ?? true),
                ],
            );
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Saved for your account only.']);

        return back();
    }

    public function store(Request $request): RedirectResponse
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
        abort_if($user === null || $user->lab_id === null, 403);
        $code = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $data['code']));
        abort_if($code === '', 422);

        $test = TestDefinition::query()->create([
            'lab_id' => $user->lab_id,
            'owner_user_id' => $user->id,
            'department_id' => $data['department_id'],
            'specimen_type_id' => $data['specimen_type_id'],
            'code' => $code,
            'name' => $data['name'],
            'is_panel' => false,
            'price_paise' => (int) round(((float) $data['price_rupees']) * 100),
            'tat_minutes' => 240,
            'report_section' => Department::query()->whereKey($data['department_id'])->firstOrFail()->name,
            'display_order' => 9999,
            'active' => true,
            'collection_notes' => 'Added by '.$user->name.' for their own catalogue.',
        ]);
        ResultDefinition::query()->create([
            'test_definition_id' => $test->id,
            'code' => $code,
            'label' => $data['name'],
            'data_type' => 'numeric',
            'unit' => $data['unit'] ?: null,
            'decimals' => 1,
            'display_order' => 1,
        ]);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Test added to your catalogue.']);

        return redirect()->route('catalogue.show', $test);
    }

    private function visibleTo(Request $request, TestDefinition $test): void
    {
        $userId = $request->user()?->id;
        abort_unless($test->owner_user_id === null || $test->owner_user_id === $userId, 403);
    }
}
