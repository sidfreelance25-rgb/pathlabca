<?php

namespace App\Services;

use App\Models\TestDefinition;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TestCloner
{
    public function cloneFor(User $user, TestDefinition $source): TestDefinition
    {
        $existing = TestDefinition::query()
            ->where('owner_user_id', $user->id)
            ->where('source_test_id', $source->id)
            ->first();

        if ($existing instanceof TestDefinition) {
            return $existing;
        }

        return DB::transaction(function () use ($user, $source): TestDefinition {
            $source->loadMissing([
                'resultDefinitions.referenceIntervals',
                'resultDefinitions.criticalLimits',
                'components',
            ]);

            if ($user->id < 1 || $source->id < 1) {
                throw new RuntimeException('Cannot copy a test without a saved user and source.');
            }

            $clone = $source->replicate();
            $clone->owner_user_id = $user->id;
            $clone->source_test_id = $source->id;
            $clone->code = $this->codeFor($user, $source);
            $clone->save();

            foreach ($source->resultDefinitions as $definition) {
                $copy = $definition->replicate();
                $copy->test_definition_id = $clone->id;
                $copy->save();

                foreach ($definition->referenceIntervals as $interval) {
                    $intervalCopy = $interval->replicate();
                    $intervalCopy->result_definition_id = $copy->id;
                    $intervalCopy->save();
                }

                foreach ($definition->criticalLimits as $limit) {
                    $limitCopy = $limit->replicate();
                    $limitCopy->result_definition_id = $copy->id;
                    $limitCopy->save();
                }
            }

            $links = DB::table('test_panel_components')->where('panel_id', $source->id)->get();
            foreach ($links as $link) {
                $componentId = data_get($link, 'component_id');
                if (! is_numeric($componentId)) {
                    continue;
                }
                $clone->components()->attach((int) $componentId, [
                    'display_order' => (int) data_get($link, 'display_order', 0),
                    'required' => (bool) data_get($link, 'required', true),
                ]);
            }

            return $clone;
        });
    }

    private function codeFor(User $user, TestDefinition $source): string
    {
        $taken = TestDefinition::query()
            ->where('lab_id', $source->lab_id)
            ->where('owner_user_id', $user->id)
            ->where('code', $source->code)
            ->exists();

        if (! $taken) {
            return $source->code;
        }

        return substr($source->code, 0, 32).'-'.$user->id;
    }
}
