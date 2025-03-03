<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_record_id',
        'approval_status',
        'application_date',
        'new_date',
        'new_clock_in',
        'new_clock_out',
        'new_break_in',
        'new_break_out',
        'new_break2_in',
        'new_break2_out',
        'comment'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendanceRecord()
    {
        return $this->belongsTo(AttendanceRecord::class);
    }
}
