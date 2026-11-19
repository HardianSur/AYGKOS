<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $owner_id
 * @property string|null $reviewed_by
 * @property string $name
 * @property string $address
 * @property string $city
 * @property string $latitude
 * @property string $longitude
 * @property string $type
 * @property string|null $description
 * @property string|null $rules
 * @property string $status
 * @property string|null $status_reason
 * @property string $verification_document_path
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'owner_id', 'reviewed_by', 'name', 'address', 'city', 'latitude', 'longitude',
    'type', 'description', 'rules', 'status', 'status_reason',
    'verification_document_path', 'reviewed_at',
])]
class Kost extends Model
{
    use HasUuids, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return HasMany<KostPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(KostPhoto::class);
    }

    /**
     * @return BelongsToMany<Facility, $this>
     */
    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(Facility::class, 'kost_facilities');
    }

    /**
     * @return HasMany<Room, $this>
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    /**
     * @return HasMany<Complaint, $this>
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }
}
