<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationBreak extends Model
{
    protected $table = 'application_breaks';
    protected $fillable = ['application_id', 'break_in', 'break_out'];

    /**
     * この休憩（修正案）が属する修正申請。
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
