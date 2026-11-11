<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditRecorder
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(?User $actor, string $action, ?Model $subject = null, ?string $reason = null, array $metadata = []): void
    {
        AuditLog::query()->create([
            'user_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'reason' => $reason,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }
}
