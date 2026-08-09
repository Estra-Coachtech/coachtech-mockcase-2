<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'comment',
    ];

    /**
     * 申請を行ったユーザー。
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 申請対象の勤怠。
     */
    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    /**
     * 申請内容（修正案）の休憩。
     */
    public function proposalBreaks(): HasMany
    {
        return $this->hasMany(ApplicationBreak::class);
    }
}
