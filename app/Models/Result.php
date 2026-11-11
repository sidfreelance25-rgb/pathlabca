<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property array<string, mixed>|null $reference_snapshot
 * @property string|null $value
 * @property string|null $flag
 * @property string|null $comment
 * @property string|null $unit
 * @property string $status
 * @property float|string|null $numeric_value
 */
class Result extends Model
{
    protected $fillable = [
        'order_item_id', 'result_definition_id', 'specimen_id', 'value', 'numeric_value',
        'unit', 'flag', 'comment', 'reference_snapshot', 'status', 'entered_by', 'entered_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reference_snapshot' => 'array',
            'entered_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * @return BelongsTo<ResultDefinition, $this>
     */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(ResultDefinition::class, 'result_definition_id');
    }

    /**
     * @return HasOne<CriticalAlert, $this>
     */
    public function criticalAlert(): HasOne
    {
        return $this->hasOne(CriticalAlert::class);
    }

    /**
     * @return HasMany<ResultReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ResultReview::class);
    }
}
