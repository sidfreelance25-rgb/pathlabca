<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $display_number
 * @property string $phone_number_id
 * @property string $access_token
 * @property string|null $verified_name
 * @property string|null $template_name
 * @property Carbon|null $connected_at
 */
class WhatsappAccount extends Model
{
    protected $fillable = [
        'user_id', 'display_number', 'phone_number_id', 'access_token',
        'verified_name', 'template_name', 'connected_at',
    ];

    protected $hidden = [
        'access_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'connected_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
