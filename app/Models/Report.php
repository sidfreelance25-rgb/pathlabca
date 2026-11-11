<?php

namespace App\Models;

use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'patient_name',
    'referred_by_doctor_id',
    'referred_by_name',
    'status',
])]
class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory;

    public const REFERRED_BY_SELF = 'Self';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'referred_by_name' => self::REFERRED_BY_SELF,
        'status' => 'draft',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function referredByDoctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'referred_by_doctor_id');
    }

    public function isReferredBySelf(): bool
    {
        return $this->referred_by_doctor_id === null;
    }
}
