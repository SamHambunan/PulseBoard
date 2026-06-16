<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dashboard extends Model
{
    /**
     * @property int $id
     * @property int $tenant_id
     */
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'created_by',
        'name',
    ];

    public function widgets()
    {
        return $this->hasMany(Widget::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
