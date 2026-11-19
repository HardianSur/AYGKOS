<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $complaint_id
 * @property string $file_path
 * @property string|null $original_name
 */
#[Fillable(['complaint_id', 'file_path', 'original_name'])]
class ComplaintAttachment extends Model
{
    use HasUuids;

    public $timestamps = false;

    /**
     * @return BelongsTo<Complaint, $this>
     */
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }
}
