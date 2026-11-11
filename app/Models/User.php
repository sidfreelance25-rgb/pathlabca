<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Role|null $role
 * @property int|null $lab_id
 * @property int|null $branch_id
 * @property string|null $designation
 * @property string|null $qualification
 * @property array<string, mixed>|null $letterhead
 * @property int|null $active_letterhead_id
 */
#[Fillable(['name', 'email', 'password', 'role', 'designation', 'qualification', 'lab_id', 'branch_id', 'letterhead', 'active_letterhead_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token', 'letterhead'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * @var list<string>
     */
    protected $appends = ['permissions'];

    /**
     * @return Attribute<list<string>, never>
     */
    protected function permissions(): Attribute
    {
        return Attribute::get(function (): array {
            return $this->role?->permissions() ?? [];
        });
    }

    /**
     * @return BelongsTo<Lab, $this>
     */
    public function lab(): BelongsTo
    {
        return $this->belongsTo(Lab::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function isLabAdmin(): bool
    {
        return $this->role === Role::LabAdmin;
    }

    /**
     * @return BelongsTo<LetterheadTemplate, $this>
     */
    public function activeLetterhead(): BelongsTo
    {
        return $this->belongsTo(LetterheadTemplate::class, 'active_letterhead_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'role' => Role::class,
            'letterhead' => 'array',
        ];
    }
}
