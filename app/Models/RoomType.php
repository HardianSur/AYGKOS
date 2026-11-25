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
 * @property string $kost_id
 * @property string $name
 * @property int $price_per_month
 * @property int $total_rooms
 * @property int $available_rooms
 * @property string|null $notes
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'kost_id', 'name', 'price_per_month', 'total_rooms', 'available_rooms', 'notes', 'status',
])]
class RoomType extends Model
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
            'price_per_month' => 'integer',
            'total_rooms' => 'integer',
            'available_rooms' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Kost, $this>
     */
    public function kost(): BelongsTo
    {
        return $this->belongsTo(Kost::class);
    }

    /**
     * @return HasMany<Photo, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    /**
     * @return BelongsToMany<Facility, $this>
     */
    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(Facility::class, 'room_type_facilities');
    }

    /**
     * @return BelongsToMany<Rule, $this>
     */
    public function rules(): BelongsToMany
    {
        return $this->belongsToMany(Rule::class, 'room_type_rules');
    }

    /**
     * @return BelongsToMany<Specification, $this>
     */
    public function specifications(): BelongsToMany
    {
        return $this->belongsToMany(Specification::class, 'room_type_specifications')
            ->withPivot('value');
    }
}
