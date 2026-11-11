<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $ordered_at
 * @property string|null $clinical_notes
 * @property string $order_number
 * @property string $priority
 */
class Order extends Model
{
    protected $fillable = [
        'branch_id', 'patient_id', 'referring_doctor_id', 'ordered_by', 'order_number',
        'priority', 'status', 'clinical_notes', 'ordered_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ordered_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<ReferringDoctor, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(ReferringDoctor::class, 'referring_doctor_id');
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<Specimen, $this>
     */
    public function specimens(): HasMany
    {
        return $this->hasMany(Specimen::class);
    }

    /**
     * @return HasMany<ReportVersion, $this>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(ReportVersion::class)->orderByDesc('version');
    }

    /**
     * @return HasOne<Invoice, $this>
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }
}
