<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'total_time',
        'total_break_time',
        'comment',
    ];

    protected $casts = [
        'date'              => 'datetime',
        'clock_in'          => 'datetime:H:i',
        'clock_out'         => 'datetime:H:i',
        'total_time'        => 'string',
        'total_break_time'  => 'string',
    ];

    /**
     * 勤怠の所有ユーザー。
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * この勤怠に対する修正申請。
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * この勤怠に紐づく休憩レコード。
     */
    public function breaks(): HasMany
    {
        return $this->hasMany(AttendanceBreak::class);
    }
}
