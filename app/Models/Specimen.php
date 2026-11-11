<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $collected_at
 * @property Carbon|null $received_at
 * @property string $barcode
 * @property string|null $condition
 */
class Specimen extends Model
{
    protected $fillable = [
        'order_id', 'specimen_type_id', 'barcode', 'accession_number', 'status',
        'collection_site', 'condition', 'collected_at', 'collected_by', 'received_at', 'received_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'collected_at' => 'datetime',
            'received_at' => 'datetime',
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
     * @return BelongsTo<SpecimenType, $this>
     */
    public function specimenType(): BelongsTo
    {
        return $this->belongsTo(SpecimenType::class);
    }

    /**
     * @return HasMany<SpecimenEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(SpecimenEvent::class)->latest();
    }
}
