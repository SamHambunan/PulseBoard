<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    /**
     * @property int $id
     */
    use HasFactory,HasUuids;

    protected $fillable = [
        'name',
    ];
}
