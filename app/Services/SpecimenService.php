<?php

namespace App\Services;

use App\Models\Specimen;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SpecimenService
{
    public function __construct(private AuditRecorder $audit) {}

    public function collect(Specimen $specimen, User $actor, ?string $site, ?string $condition): Specimen
    {
        return $this->transition($specimen, $actor, 'collected', 'specimen.collected', function (Specimen $specimen) use ($actor, $site, $condition): void {
            $specimen->forceFill([
                'status' => 'collected',
                'collected_at' => now(),
                'collected_by' => $actor->id,
                'collection_site' => $site,
                'condition' => $condition,
            ])->save();
        });
    }

    public function receive(Specimen $specimen, User $actor, ?string $condition): Specimen
    {
        if ($specimen->status === 'pending') {
            throw ValidationException::withMessages([
                'barcode' => 'Collect the specimen before accessioning it.',
            ]);
        }

        return $this->transition($specimen, $actor, 'received', 'specimen.received', function (Specimen $specimen) use ($actor, $condition): void {
            $specimen->forceFill([
                'status' => 'received',
                'received_at' => now(),
                'received_by' => $actor->id,
                'condition' => $condition ?: $specimen->condition,
            ])->save();
            $specimen->order()->update(['status' => 'in_lab']);
        });
    }

    /**
     * @param  callable(Specimen): void  $apply
     */
    private function transition(Specimen $specimen, User $actor, string $status, string $action, callable $apply): Specimen
    {
        return DB::transaction(function () use ($specimen, $actor, $status, $action, $apply): Specimen {
            $apply($specimen);
            $specimen->events()->create([
                'user_id' => $actor->id,
                'status' => $status,
                'note' => null,
            ]);
            $this->audit->record($actor, $action, $specimen);

            $order = $specimen->order()->with('specimens')->firstOrFail();
            if ($status === 'collected' && $order->specimens->every(fn (Specimen $item): bool => in_array($item->status, ['collected', 'received'], true))) {
                $order->update(['status' => 'collected']);
            }

            return $specimen->refresh();
        });
    }
}
