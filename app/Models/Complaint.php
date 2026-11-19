<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $user_id
 * @property string|null $kost_id
 * @property string|null $handled_by
 * @property string $category
 * @property string $title
 * @property string $description
 * @property string $status
 * @property string|null $admin_response
 * @property Carbon|null $responded_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'kost_id', 'handled_by', 'category', 'title', 'description', 'status', 'admin_response', 'responded_at'])]
class Complaint extends Model
{
    use HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Kost, $this>
     */
    public function kost(): BelongsTo
    {
        return $this->belongsTo(Kost::class)->withDefault();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /**
     * @return HasMany<ComplaintAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ComplaintAttachment::class);
    }
}
