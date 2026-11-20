<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $role_id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string|null $whatsapp_number
 * @property Carbon|null $whatsapp_verified_at
 * @property string $password
 * @property bool $is_active
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['role_id', 'name', 'email', 'whatsapp_number', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'whatsapp_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Determine whether the account belongs to a kost owner.
     */
    public function isPemilikKost(): bool
    {
        return $this->role?->name === 'pemilik_kost';
    }

    /**
     * Determine whether the account proved the identity its role requires.
     *
     * Pemilik Kost prove their WhatsApp number; Customer and Admin prove their
     * email address.
     */
    public function hasVerifiedIdentity(): bool
    {
        return $this->isPemilikKost()
            ? $this->whatsapp_verified_at !== null
            : $this->email_verified_at !== null;
    }

    /**
     * Determine whether the account passed the verification its role requires.
     *
     * Middleware `verified` and the login guard delegate to the per-role rule.
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->hasVerifiedIdentity();
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @return HasMany<Kost, $this>
     */
    public function kosts(): HasMany
    {
        return $this->hasMany(Kost::class, 'owner_id');
    }

    /**
     * @return HasMany<Kost, $this>
     */
    public function reviewedKosts(): HasMany
    {
        return $this->hasMany(Kost::class, 'reviewed_by');
    }

    /**
     * @return HasMany<WhatsappVerification, $this>
     */
    public function whatsappVerifications(): HasMany
    {
        return $this->hasMany(WhatsappVerification::class);
    }

    /**
     * @return HasMany<Complaint, $this>
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'user_id');
    }

    /**
     * @return HasMany<Complaint, $this>
     */
    public function handledComplaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'handled_by');
    }

    /**
     * @return HasMany<Notification, $this>
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
}
