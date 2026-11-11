<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $date_of_birth
 * @property bool $is_demo
 * @property string $sex
 * @property string $name
 * @property string $patient_number
 */
class Patient extends Model
{
    protected $fillable = [
        'lab_id', 'patient_number', 'name', 'date_of_birth', 'sex', 'phone', 'email', 'address', 'is_demo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'is_demo' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function ageYears(): ?int
    {
        return $this->date_of_birth?->age;
    }
}
