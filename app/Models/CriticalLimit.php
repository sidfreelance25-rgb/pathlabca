<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CriticalLimit extends Model
{
    protected $fillable = [
        'result_definition_id', 'sex', 'lower_bound', 'upper_bound', 'source', 'note',
    ];

    /**
     * @return BelongsTo<ResultDefinition, $this>
     */
    public function resultDefinition(): BelongsTo
    {
        return $this->belongsTo(ResultDefinition::class);
    }
}
