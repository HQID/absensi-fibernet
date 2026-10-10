<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficeLocation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'center_latitude' => 'decimal:8',
        'center_longitude' => 'decimal:8',
        'polygon_coordinates' => 'array',
        'active' => 'boolean',
    ];
}
