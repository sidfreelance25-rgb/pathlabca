<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $lab_id
 * @property int|null $owner_user_id
 * @property int|null $source_letterhead_id
 * @property string $name
 * @property array<string, mixed> $design
 * @property bool $is_default
 */
class LetterheadTemplate extends Model
{
    protected $fillable = [
        'lab_id', 'owner_user_id', 'source_letterhead_id', 'name', 'design', 'is_default',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'design' => 'array',
            'is_default' => 'boolean',
        ];
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
        return $this->belongsTo(self::class, 'source_letterhead_id');
    }
}
