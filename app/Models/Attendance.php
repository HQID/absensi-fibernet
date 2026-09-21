<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $guarded = [];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'check_in_latitude' => 'decimal:8',
        'check_in_longitude' => 'decimal:8',
        'check_in_accuracy' => 'decimal:2',
        'check_in_distance' => 'decimal:2',
        'check_out_latitude' => 'decimal:8',
        'check_out_longitude' => 'decimal:8',
        'check_out_accuracy' => 'decimal:2',
        'check_out_distance' => 'decimal:2',
        'is_approved' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
