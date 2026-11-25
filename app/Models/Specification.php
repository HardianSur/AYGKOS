<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $name
 * @property string $input_type
 * @property string|null $unit
 */
#[Fillable(['name', 'input_type', 'unit'])]
class Specification extends Model
{
    use HasUuids;

    public $timestamps = false;
}
