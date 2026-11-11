<?php

namespace App\Services;

use App\Models\CriticalAlert;
use App\Models\CriticalLimit;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Patient;
use App\Models\ReferenceInterval;
use App\Models\Result;
use App\Models\ResultDefinition;
use App\Models\Specimen;
use App\Models\TestDefinition;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResultService
{
    public function __construct(private AuditRecorder $audit) {}

    /**
     * @param  array<int, array{value?: string|null, comment?: string|null}>  $entries
     */
    public function save(Order $order, User $actor, array $entries): void
    {
        DB::transaction(function () use ($order, $actor, $entries): void {
            $order->load([
                'patient',
                'specimens',
                'items.test.resultDefinitions.referenceIntervals',
                'items.test.resultDefinitions.criticalLimits',
                'items.test.components.resultDefinitions.referenceIntervals',
                'items.test.components.resultDefinitions.criticalLimits',
            ]);

            foreach ($order->items as $item) {
                foreach ($this->definitionsFor($item->test) as $definition) {
                    $entry = $entries[$definition->id] ?? null;
                    $value = trim((string) ($entry['value'] ?? ''));

                    if ($value === '') {
                        continue;
                    }

                    $this->upsert($order, $item, $definition, $value, trim((string) ($entry['comment'] ?? '')), $actor);
                }
            }

            $this->applyFormulas($order, $actor);
            $order->update(['status' => 'resulted']);
            $this->audit->record($actor, 'result.entered', $order);
        });
    }

    public function reviewTechnically(Order $order, User $actor): void
    {
        DB::transaction(function () use ($order, $actor): void {
            $this->assertComplete($order);
            Result::query()
                ->whereIn('order_item_id', $order->items()->pluck('id'))
                ->where('status', 'entered')
                ->update(['status' => 'technically_reviewed']);
            Result::query()
                ->whereIn('order_item_id', $order->items()->pluck('id'))
                ->where('status', 'technically_reviewed')
                ->each(function (Result $result) use ($actor): void {
                    $result->reviews()->create([
                        'user_id' => $actor->id,
                        'decision' => 'technical_review',
                    ]);
                });
            $this->audit->record($actor, 'result.technically_reviewed', $order);
        });
    }

    public function validateClinically(Order $order, User $actor): void
    {
        DB::transaction(function () use ($order, $actor): void {
            $this->assertComplete($order);
            $openCritical = CriticalAlert::query()
                ->where('status', 'open')
                ->whereIn('result_id', Result::query()->whereIn('order_item_id', $order->items()->pluck('id'))->pluck('id'))
                ->exists();

            if ($openCritical) {
                throw ValidationException::withMessages([
                    'results' => 'Acknowledge critical results before clinical validation.',
                ]);
            }

            $pending = Result::query()
                ->whereIn('order_item_id', $order->items()->pluck('id'))
                ->whereNotIn('status', ['technically_reviewed', 'validated'])
                ->exists();

            if ($pending) {
                throw ValidationException::withMessages([
                    'results' => 'Every result needs technical review before validation.',
                ]);
            }

            Result::query()
                ->whereIn('order_item_id', $order->items()->pluck('id'))
                ->update(['status' => 'validated']);
            $order->items()->update(['status' => 'validated']);
            $order->update(['status' => 'validated']);
            $this->audit->record($actor, 'result.validated', $order);
        });
    }

    public function acknowledgeCritical(CriticalAlert $alert, User $actor, string $note): void
    {
        $alert->update([
            'status' => 'acknowledged',
            'acknowledged_by' => $actor->id,
            'acknowledged_at' => now(),
            'note' => $note,
        ]);
        $this->audit->record($actor, 'critical.acknowledged', $alert, $note);
    }

    /**
     * @return Collection<int, ResultDefinition>
     */
    public function definitionsFor(TestDefinition $test): Collection
    {
        if (! $test->is_panel) {
            return $test->resultDefinitions;
        }

        return $test->components
            ->flatMap(fn (TestDefinition $component): Collection => $component->resultDefinitions)
            ->values();
    }

    private function upsert(Order $order, OrderItem $item, ResultDefinition $definition, string $value, string $comment, User $actor): void
    {
        $existing = Result::query()
            ->where('order_item_id', $item->id)
            ->where('result_definition_id', $definition->id)
            ->first();

        if ($existing !== null && $existing->status === 'validated') {
            return;
        }

        $patient = $order->patient;
        $numeric = is_numeric($value) ? (float) $value : null;
        $interval = $this->matchInterval($definition, $patient);
        $critical = $this->matchCritical($definition, $patient);
        $flag = $this->flag($patient, $definition, $numeric, $interval, $critical);
        $specimen = $order->specimens->firstWhere('specimen_type_id', $item->test->specimen_type_id);

        $result = Result::query()->updateOrCreate(
            [
                'order_item_id' => $item->id,
                'result_definition_id' => $definition->id,
            ],
            [
                'specimen_id' => $specimen instanceof Specimen ? $specimen->id : null,
                'value' => $value,
                'numeric_value' => $numeric,
                'unit' => $definition->unit,
                'flag' => $flag,
                'comment' => $comment !== '' ? $comment : null,
                'reference_snapshot' => $interval === null ? null : [
                    'display' => $interval->display(),
                    'lower' => $interval->lower_bound,
                    'upper' => $interval->upper_bound,
                    'unit' => $interval->unit,
                    'sex' => $interval->sex,
                    'source' => $interval->source,
                    'source_url' => $interval->source_url,
                    'note' => $interval->population_note,
                ],
                'status' => 'entered',
                'entered_by' => $actor->id,
                'entered_at' => now(),
            ],
        );

        $this->syncCriticalAlert($result, $flag);
    }

    private function applyFormulas(Order $order, User $actor): void
    {
        $order->load(['items.results.definition', 'items.test.components.resultDefinitions', 'patient', 'specimens']);
        $byCode = [];

        foreach ($order->items as $item) {
            foreach ($item->results as $result) {
                $byCode[$result->definition->code] = $result;
            }
        }

        foreach ($order->items as $item) {
            foreach ($this->definitionsFor($item->test) as $definition) {
                if ($definition->formula === null) {
                    continue;
                }

                $value = $this->calculate($definition->formula, $byCode);

                if ($value === null) {
                    continue;
                }

                $this->upsert($order, $item, $definition, $value, '', $actor);
            }
        }
    }

    /**
     * @param  array<string, Result>  $byCode
     */
    private function calculate(string $formula, array $byCode): ?string
    {
        $number = function (string $code) use ($byCode): ?float {
            $result = $byCode[$code] ?? null;

            return $result?->numeric_value === null ? null : (float) $result->numeric_value;
        };

        if ($formula === 'protein-albumin') {
            $protein = $number('TP');
            $albumin = $number('ALB');

            return ($protein === null || $albumin === null) ? null : (string) round($protein - $albumin, 1);
        }

        if ($formula === 'friedewald') {
            $cholesterol = $number('CHOL');
            $hdl = $number('HDL');
            $triglycerides = $number('TG');

            if ($cholesterol === null || $hdl === null || $triglycerides === null || $triglycerides >= 400) {
                return null;
            }

            return (string) round($cholesterol - $hdl - ($triglycerides / 5), 0);
        }

        if ($formula === 'transferrin-saturation') {
            $iron = $number('IRON');
            $tibc = $number('TIBC');

            if ($iron === null || $tibc === null || $tibc == 0.0) {
                return null;
            }

            return (string) round(($iron / $tibc) * 100, 0);
        }

        return null;
    }

    private function assertComplete(Order $order): void
    {
        $order->load([
            'items.test.resultDefinitions',
            'items.test.components.resultDefinitions',
            'items.results',
        ]);

        foreach ($order->items as $item) {
            $recorded = $item->results->keyBy('result_definition_id');

            foreach ($this->definitionsFor($item->test) as $definition) {
                if ($definition->formula !== null) {
                    continue;
                }

                $result = $recorded->get($definition->id);

                if ($result === null || $result->value === null || $result->value === '') {
                    throw ValidationException::withMessages([
                        'results' => 'Enter every required component before review. Missing '.$definition->label.'.',
                    ]);
                }
            }
        }
    }

    private function matchInterval(ResultDefinition $definition, Patient $patient): ?ReferenceInterval
    {
        $intervals = $definition->referenceIntervals->where('approval_status', 'approved');
        $sex = $patient->sex;

        return $intervals->first(fn (ReferenceInterval $interval): bool => $interval->sex === $sex)
            ?? $intervals->first(fn (ReferenceInterval $interval): bool => $interval->sex === 'any');
    }

    private function matchCritical(ResultDefinition $definition, Patient $patient): ?CriticalLimit
    {
        $limits = $definition->criticalLimits;
        $sex = $patient->sex;

        return $limits->first(fn (CriticalLimit $limit): bool => $limit->sex === $sex)
            ?? $limits->first(fn (CriticalLimit $limit): bool => $limit->sex === 'any');
    }

    private function flag(Patient $patient, ResultDefinition $definition, ?float $numeric, ?ReferenceInterval $interval, ?CriticalLimit $critical): string
    {
        if ($patient->is_demo) {
            return 'DEMO';
        }

        if ($definition->data_type !== 'numeric' || $numeric === null) {
            return '';
        }

        if ($critical !== null) {
            if ($critical->lower_bound !== null && $numeric < (float) $critical->lower_bound) {
                return 'CL';
            }

            if ($critical->upper_bound !== null && $numeric > (float) $critical->upper_bound) {
                return 'CH';
            }
        }

        if ($interval === null) {
            return '';
        }

        if ($interval->lower_bound !== null && $numeric < (float) $interval->lower_bound) {
            return 'L';
        }

        if ($interval->upper_bound !== null && $numeric > (float) $interval->upper_bound) {
            return 'H';
        }

        return 'N';
    }

    private function syncCriticalAlert(Result $result, string $flag): void
    {
        $critical = in_array($flag, ['CL', 'CH'], true);
        $alert = $result->criticalAlert;

        if ($critical && $alert === null) {
            $result->criticalAlert()->create(['status' => 'open']);

            return;
        }

        if (! $critical && $alert !== null && $alert->status === 'open') {
            $alert->delete();
        }
    }
}
