<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Widget extends Model
{
    /**
     * @property int $id
     * @property int $dashboard_id
     */
    protected $fillable = [
        'dashboard_id',
        'type',
        'config',
        'position_x',
        'position_y',
        'width',
        'height',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array', // Automatically encode/decode JSON
        ];
    }

    public function dashboard()
    {
        return $this->belongsTo(Dashboard::class);
    }
}
