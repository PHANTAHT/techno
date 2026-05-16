<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleToken extends Model
{
    // Tambahkan baris ini agar Laravel mengizinkan penyimpanan data dari Controller
    protected $fillable = [
        'user_id',
        'access_token',
        'refresh_token',
        'expires_at',
    ];

    // (Opsional) Relasi agar nyambung dengan User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}