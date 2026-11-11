<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Patient;
use App\Models\Specimen;
use App\Models\SpecimenType;
use App\Models\TestDefinition;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private AuditRecorder $audit) {}

    /**
     * @param  list<int>  $testIds
     */
    public function place(User $actor, Patient $patient, Branch $branch, array $testIds, string $priority, ?int $doctorId, ?string $notes): Order
    {
        $tests = TestDefinition::query()
            ->where('lab_id', $patient->lab_id)
            ->where('active', true)
            ->whereIn('id', $testIds)
            ->get();

        if ($tests->isEmpty()) {
            throw ValidationException::withMessages([
                'test_ids' => 'Select at least one active test.',
            ]);
        }

        return DB::transaction(function () use ($actor, $patient, $branch, $tests, $priority, $doctorId, $notes): Order {
            $order = Order::query()->create([
                'branch_id' => $branch->id,
                'patient_id' => $patient->id,
                'referring_doctor_id' => $doctorId,
                'ordered_by' => $actor->id,
                'order_number' => $this->nextNumber('ORD', Order::class, 'order_number'),
                'priority' => $priority,
                'status' => 'ordered',
                'clinical_notes' => $notes,
                'ordered_at' => now(),
            ]);

            $amount = 0;
            $specimenTypes = [];

            foreach ($tests as $test) {
                $order->items()->create([
                    'test_definition_id' => $test->id,
                    'status' => 'pending',
                    'price_paise' => $test->price_paise,
                ]);
                $amount += $test->price_paise;
                $specimenTypes[$test->specimen_type_id] = true;
            }

            foreach (array_keys($specimenTypes) as $specimenTypeId) {
                $type = SpecimenType::query()->findOrFail($specimenTypeId);
                $barcode = $this->nextNumber('PC'.$branch->code, Specimen::class, 'barcode');
                $specimen = $order->specimens()->create([
                    'specimen_type_id' => $type->id,
                    'barcode' => $barcode,
                    'accession_number' => $barcode,
                    'status' => 'pending',
                ]);
                $specimen->events()->create([
                    'user_id' => $actor->id,
                    'status' => 'pending',
                    'note' => 'Specimen identity created for '.$type->name.'.',
                ]);
            }

            Invoice::query()->create([
                'order_id' => $order->id,
                'number' => $this->nextNumber('INV', Invoice::class, 'number'),
                'amount_paise' => $amount,
                'status' => 'unpaid',
            ]);

            $this->audit->record($actor, 'order.created', $order, null, [
                'tests' => $tests->pluck('code')->all(),
            ]);

            return $order->load(['items.test', 'specimens.specimenType', 'patient', 'invoice']);
        });
    }

    /**
     * @param  class-string  $model
     */
    private function nextNumber(string $prefix, string $model, string $column): string
    {
        $stamp = now()->format('ymd');
        $root = $prefix.$stamp;
        $latest = $model::query()
            ->where($column, 'like', $root.'%')
            ->lockForUpdate()
            ->orderByDesc($column)
            ->value($column);
        $sequence = 1;

        if (is_string($latest) && str_starts_with($latest, $root)) {
            $sequence = ((int) substr($latest, strlen($root))) + 1;
        }

        return $root.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
