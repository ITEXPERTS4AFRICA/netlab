<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class OtpCode extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'expires_at',
        'used',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean',
    ];

    
    /* ============================
     | Relations
     |============================ */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    /* ============================
     | Scopes utiles
     |============================ */

    public function scopeValid($query)
    {
        return $query
            ->where('used', false)
            ->where('expires_at', '>', now());
    }

}
