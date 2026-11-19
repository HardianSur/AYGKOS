<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $user_id
 * @property string $target_number
 * @property string $code_hash
 * @property Carbon $expires_at
 * @property int $attempt_count
 * @property int $resend_count
 * @property Carbon $last_sent_at
 * @property Carbon|null $consumed_at
 * @property Carbon|null $created_at
 */
#[Fillable(['user_id', 'target_number', 'code_hash', 'expires_at', 'attempt_count', 'resend_count', 'last_sent_at', 'consumed_at'])]
class WhatsappVerification extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
