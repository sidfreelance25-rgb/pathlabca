<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestDefinition extends Model
{
    protected $fillable = [
        'lab_id', 'owner_user_id', 'source_test_id', 'department_id', 'specimen_type_id', 'code', 'name', 'loinc', 'synonyms',
        'method', 'is_panel', 'price_paise', 'tat_minutes', 'report_section', 'display_order',
        'catalogue_version', 'active', 'collection_notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_panel' => 'boolean',
            'active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_test_id');
    }

    /**
     * @return BelongsTo<SpecimenType, $this>
     */
    public function specimenType(): BelongsTo
    {
        return $this->belongsTo(SpecimenType::class);
    }

    /**
     * @return BelongsToMany<TestDefinition, $this>
     */
    public function components(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'test_panel_components', 'panel_id', 'component_id')
            ->withPivot(['display_order', 'required'])
            ->orderByPivot('display_order');
    }

    /**
     * @return HasMany<ResultDefinition, $this>
     */
    public function resultDefinitions(): HasMany
    {
        return $this->hasMany(ResultDefinition::class)->orderBy('display_order');
    }
}
