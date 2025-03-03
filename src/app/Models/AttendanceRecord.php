<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'break_in',
        'break_out',
        'break2_in',
        'break2_out',
        'total_time',
        'total_break_time',
        'comment'
    ];

    protected $casts = [
        'date' => 'datetime',
        'clock_in',
        'clock_out',
        'break_in',
        'break_out',
        'break2_in',
        'break2_out',
        'total_time',
        'total_break_time'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }
}
