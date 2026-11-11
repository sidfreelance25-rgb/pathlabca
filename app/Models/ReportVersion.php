<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property array<string, mixed> $snapshot
 * @property Carbon|null $released_at
 * @property string|null $checksum
 * @property string|null $file_path
 * @property string $status
 * @property string $verification_token
 * @property string $report_number
 * @property int $version
 * @property int|null $report_template_id
 * @property int $template_version
 * @property string|null $correction_reason
 */
class ReportVersion extends Model
{
    protected $fillable = [
        'order_id', 'report_template_id', 'report_number', 'version', 'template_version',
        'status', 'snapshot', 'file_path', 'checksum', 'verification_token', 'correction_reason',
        'supersedes_id', 'released_by', 'released_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'released_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<ReportTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class, 'report_template_id');
    }

    /**
     * @return HasMany<ReportEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(ReportEvent::class);
    }
}
