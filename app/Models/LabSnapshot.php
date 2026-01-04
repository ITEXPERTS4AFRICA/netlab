<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin IdeHelperLabSnapshot
 */
class LabSnapshot extends Model
{
    protected $fillable = [
        'lab_id',
        'name',
        'description',
        'config_yaml',
        'config_json',
        'metadata',
        'is_default',
        'created_by',
        'snapshot_at',
    ];

    protected $casts = [
        'config_json' => 'array',
        'metadata' => 'array',
        'is_default' => 'boolean',
        'snapshot_at' => 'datetime',
    ];

    /**
     * Relations
     */
    public function lab(): BelongsTo
    {
        return $this->belongsTo(Lab::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scopes
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeForLab($query, $labId)
    {
        return $query->where('lab_id', $labId);
    }

    /**
     * Helpers
     */
    public function setAsDefault(): void
    {
        // Désactiver les autres snapshots par défaut pour ce lab
        static::where('lab_id', $this->lab_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    /**
     * Obtenir la taille du snapshot
     */
    public function getSizeAttribute(): int
    {
        return strlen($this->config_yaml);
    }

    /**
     * Obtenir la taille formatée
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
