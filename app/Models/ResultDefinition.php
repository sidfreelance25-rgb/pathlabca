<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResultDefinition extends Model
{
    protected $fillable = [
        'test_definition_id', 'code', 'label', 'loinc', 'data_type', 'unit', 'decimals',
        'allowed_values', 'formula', 'display_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'allowed_values' => 'array',
        ];
    }

    /**
     * @return BelongsTo<TestDefinition, $this>
     */
    public function testDefinition(): BelongsTo
    {
        return $this->belongsTo(TestDefinition::class);
    }

    /**
     * @return HasMany<ReferenceInterval, $this>
     */
    public function referenceIntervals(): HasMany
    {
        return $this->hasMany(ReferenceInterval::class);
    }

    /**
     * @return HasMany<CriticalLimit, $this>
     */
    public function criticalLimits(): HasMany
    {
        return $this->hasMany(CriticalLimit::class);
    }
}
