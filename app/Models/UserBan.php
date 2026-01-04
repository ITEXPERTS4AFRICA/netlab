<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * @mixin IdeHelperUserBan
 */
class UserBan extends Model
{
    use HasUlids;

    protected $fillable = [
        'user_id',
        'banned_by',
        'reason',
        'details',
        'banned_until',
        'is_permanent',
    ];

    protected $casts = [
        'banned_until' => 'datetime',
        'is_permanent' => 'boolean',
    ];

    /**
     * Utilisateur banni
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Admin ou Instructor qui a banni
     */
    public function banner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'banned_by');
    }

    /**
     * Vérifier si le banissement est toujours actif
     */
    public function isActive(): bool
    {
        if ($this->is_permanent) {
            return true;
        }

        if (!$this->banned_until) {
            return false;
        }

        return $this->banned_until->isFuture();
    }

    /**
     * Vérifier si le banissement est expiré
     */
    public function isExpired(): bool
    {
        if ($this->is_permanent) {
            return false;
        }

        if (!$this->banned_until) {
            return true;
        }

        return $this->banned_until->isPast();
    }
}
