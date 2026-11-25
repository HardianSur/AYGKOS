<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $id
 * @property string $name
 * @property string $scope
 * @property string $icon
 */
#[Fillable(['name', 'scope', 'icon'])]
class Facility extends Model
{
    use HasUuids;

    public $timestamps = false;

    /**
     * @return BelongsToMany<Kost, $this>
     */
    public function kosts(): BelongsToMany
    {
        return $this->belongsToMany(Kost::class, 'kost_facilities');
    }
}
