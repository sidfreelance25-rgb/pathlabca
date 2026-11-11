<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTestSetting extends Model
{
    protected $fillable = [
        'user_id', 'test_definition_id', 'name', 'price_paise', 'tat_minutes', 'active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<TestDefinition, $this>
     */
    public function test(): BelongsTo
    {
        return $this->belongsTo(TestDefinition::class, 'test_definition_id');
    }
}
