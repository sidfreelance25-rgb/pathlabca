<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferenceInterval extends Model
{
    protected $fillable = [
        'result_definition_id', 'sex', 'age_min_years', 'age_max_years', 'lower_bound',
        'upper_bound', 'unit', 'source', 'source_url', 'population_note', 'approval_status',
    ];

    /**
     * @return BelongsTo<ResultDefinition, $this>
     */
    public function resultDefinition(): BelongsTo
    {
        return $this->belongsTo(ResultDefinition::class);
    }

    public function display(): string
    {
        $lower = $this->lower_bound;
        $upper = $this->upper_bound;

        if ($lower !== null && $upper !== null) {
            return self::trimNumber($lower).' – '.self::trimNumber($upper);
        }

        if ($upper !== null) {
            return '< '.self::trimNumber($upper);
        }

        if ($lower !== null) {
            return '≥ '.self::trimNumber($lower);
        }

        return '';
    }

    public static function trimNumber(float|string|null $value): string
    {
        if ($value === null) {
            return '';
        }

        $number = (float) $value;

        if (floor($number) === $number) {
            return (string) (int) $number;
        }

        return rtrim(rtrim(number_format($number, 4, '.', ''), '0'), '.');
    }
}
