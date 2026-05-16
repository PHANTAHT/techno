<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Tambahkan baris ini untuk mengizinkan mass assignment
#[Fillable(['user_id', 'moodle_url', 'token'])]
class MoodleConfig extends Model
{
    /**
     * Relasi balik ke model User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}