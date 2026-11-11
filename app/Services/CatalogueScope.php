<?php

namespace App\Services;

use App\Models\Department;
use App\Models\OrderItem;
use App\Models\ResultDefinition;
use App\Models\TestDefinition;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CatalogueScope
{
    public function __construct(private TestCloner $cloner) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function rows(User $user, string $discipline): array
    {
        $tests = TestDefinition::query()
            ->with('department:id,name', 'specimenType:id,name,container', 'owner:id,name')
            ->where('lab_id', $user->lab_id)
            ->when($discipline !== '', fn ($query) => $query->where('department_id', $discipline))
            ->orderBy('report_section')
            ->orderBy('display_order')
            ->get();

        if (! $user->isLabAdmin()) {
            $cloned = $tests
                ->where('owner_user_id', $user->id)
                ->pluck('source_test_id')
                ->filter()
                ->all();
            $tests = $tests->filter(function (TestDefinition $test) use ($user, $cloned): bool {
                if ($test->owner_user_id === $user->id) {
                    return true;
                }

                return $test->owner_user_id === null && ! in_array($test->id, $cloned, true);
            })->values();
        }

        $rows = $tests->map(function (TestDefinition $test) use ($user): array {
            $scope = $this->scope($test);

            return [
                'id' => $test->id,
                'code' => $test->code,
                'name' => $test->name,
                'is_panel' => $test->is_panel,
                'price_paise' => $test->price_paise,
                'tat_minutes' => $test->tat_minutes,
                'active' => $test->active,
                'scope' => $scope,
                'owner_name' => $test->owner?->name,
                'can_edit' => $test->owner_user_id === null || $test->owner_user_id === $user->id,
                'can_promote' => $user->isLabAdmin() && $test->owner_user_id !== null,
                'department' => $test->department,
                'specimen_type' => $test->specimenType,
            ];
        })->all();

        return array_values($rows);
    }

    /**
     * @param  list<int|string>  $testIds
     * @return Collection<int, TestDefinition>
     */
    public function resolveForOrder(User $user, array $testIds): Collection
    {
        $ids = array_map(intval(...), $testIds);
        $clones = TestDefinition::query()
            ->where('owner_user_id', $user->id)
            ->whereIn('source_test_id', $ids)
            ->where('active', true)
            ->get()
            ->keyBy('source_test_id');
        $resolved = collect($ids)
            ->map(function (int $id) use ($clones): int {
                $clone = $clones->get($id);

                return $clone instanceof TestDefinition ? $clone->id : $id;
            })
            ->unique()
            ->values();

        return TestDefinition::query()
            ->where('lab_id', $user->lab_id)
            ->where('active', true)
            ->whereIn('id', $resolved)
            ->where(function ($query) use ($user): void {
                $query->whereNull('owner_user_id')->orWhere('owner_user_id', $user->id);
            })
            ->get();
    }

    /**
     * @return Collection<int, TestDefinition>
     */
    public function orderable(User $user): Collection
    {
        $owned = TestDefinition::query()
            ->with('department:id,name', 'specimenType:id,name')
            ->where('lab_id', $user->lab_id)
            ->where('owner_user_id', $user->id)
            ->where('active', true)
            ->get();
        $clonedFrom = $owned->pluck('source_test_id')->filter()->all();
        $globals = TestDefinition::query()
            ->with('department:id,name', 'specimenType:id,name')
            ->where('lab_id', $user->lab_id)
            ->whereNull('owner_user_id')
            ->where('active', true)
            ->when($clonedFrom !== [], fn ($query) => $query->whereNotIn('id', $clonedFrom))
            ->orderBy('display_order')
            ->get();

        return $globals->concat($owned)->sortBy('display_order')->values();
    }

    public function update(User $user, TestDefinition $test, string $name, int $pricePaise, bool $active): TestDefinition
    {
        $this->visibleTo($user, $test);

        if ($test->owner_user_id === null && ! $user->isLabAdmin()) {
            $clone = $this->cloner->cloneFor($user, $test);
            $clone->update([
                'name' => $name,
                'price_paise' => $pricePaise,
                'active' => $active,
            ]);

            return $clone;
        }

        abort_unless($test->owner_user_id === null || $test->owner_user_id === $user->id, 403);
        $test->update([
            'name' => $name,
            'price_paise' => $pricePaise,
            'active' => $active,
        ]);

        return $test;
    }

    /**
     * @param  array{name: string, code: string, department_id: int, specimen_type_id: int, price_paise: int, unit: ?string}  $data
     */
    public function create(User $user, array $data): TestDefinition
    {
        abort_if($user->lab_id === null, 403);
        $ownerId = $user->isLabAdmin() ? null : $user->id;
        $exists = TestDefinition::query()
            ->where('lab_id', $user->lab_id)
            ->where('code', $data['code'])
            ->when(
                $ownerId === null,
                fn ($query) => $query->whereNull('owner_user_id'),
                fn ($query) => $query->where('owner_user_id', $ownerId),
            )
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'code' => 'A test with this code already exists in that catalogue.',
            ]);
        }

        $test = TestDefinition::query()->create([
            'lab_id' => $user->lab_id,
            'owner_user_id' => $ownerId,
            'department_id' => $data['department_id'],
            'specimen_type_id' => $data['specimen_type_id'],
            'code' => $data['code'],
            'name' => $data['name'],
            'is_panel' => false,
            'price_paise' => $data['price_paise'],
            'tat_minutes' => 240,
            'report_section' => Department::query()->whereKey($data['department_id'])->firstOrFail()->name,
            'display_order' => 9999,
            'active' => true,
            'collection_notes' => $ownerId === null
                ? 'Added to the main catalogue by '.$user->name.'.'
                : 'Added by '.$user->name.' for their own catalogue.',
        ]);
        ResultDefinition::query()->create([
            'test_definition_id' => $test->id,
            'code' => $data['code'],
            'label' => $data['name'],
            'data_type' => 'numeric',
            'unit' => $data['unit'] ?: null,
            'decimals' => 1,
            'display_order' => 1,
        ]);

        return $test;
    }

    public function promote(User $admin, TestDefinition $test): TestDefinition
    {
        abort_unless($admin->isLabAdmin(), 403);
        abort_if($test->lab_id !== $admin->lab_id, 403);
        abort_if($test->owner_user_id === null, 422, 'This test is already in the main catalogue.');

        return DB::transaction(function () use ($test): TestDefinition {
            if ($test->source_test_id !== null) {
                $global = TestDefinition::query()->whereKey($test->source_test_id)->firstOrFail();
                $global->update([
                    'name' => $test->name,
                    'price_paise' => $test->price_paise,
                    'active' => $test->active,
                    'tat_minutes' => $test->tat_minutes,
                    'collection_notes' => $test->collection_notes,
                ]);
                $this->discardClone($test);

                return $global;
            }

            $conflict = TestDefinition::query()
                ->where('lab_id', $test->lab_id)
                ->whereNull('owner_user_id')
                ->where('code', $test->code)
                ->exists();

            if ($conflict) {
                throw ValidationException::withMessages([
                    'code' => 'A main test already uses this code.',
                ]);
            }

            $test->update(['owner_user_id' => null]);

            return $test;
        });
    }

    public function scope(TestDefinition $test): string
    {
        if ($test->owner_user_id === null) {
            return 'main';
        }

        return $test->source_test_id === null ? 'personal' : 'copy';
    }

    public function visibleTo(User $user, TestDefinition $test): void
    {
        abort_unless($test->lab_id === $user->lab_id, 403);

        if ($user->isLabAdmin() || $test->owner_user_id === null || $test->owner_user_id === $user->id) {
            return;
        }

        abort(403);
    }

    private function discardClone(TestDefinition $test): void
    {
        if (OrderItem::query()->where('test_definition_id', $test->id)->exists()) {
            return;
        }

        $test->delete();
    }
}
