<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class LabDocumentationMedia extends Model
{
    protected $fillable = [
        'lab_id',
        'type',
        'title',
        'description',
        'file_path',
        'file_url',
        'mime_type',
        'file_size',
        'thumbnail_path',
        'order',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'file_size' => 'integer',
        'order' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Relations
     */
    public function lab(): BelongsTo
    {
        return $this->belongsTo(Lab::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Helpers
     */
    public function getUrlAttribute(): ?string
    {
        if ($this->file_url) {
            return $this->file_url;
        }

        if ($this->file_path && Storage::disk('public')->exists($this->file_path)) {
            return Storage::disk('public')->url($this->file_path);
        }

        return null;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if ($this->thumbnail_path && Storage::disk('public')->exists($this->thumbnail_path)) {
            return Storage::disk('public')->url($this->thumbnail_path);
        }

        // Si c'est une image, utiliser l'image elle-même comme thumbnail
        if ($this->type === 'image' && $this->url) {
            return $this->url;
        }

        return null;
    }

    public function isImage(): bool
    {
        return $this->type === 'image' || str_starts_with($this->mime_type ?? '', 'image/');
    }

    public function isVideo(): bool
    {
        return $this->type === 'video' || str_starts_with($this->mime_type ?? '', 'video/');
    }

    public function isLink(): bool
    {
        return $this->type === 'link';
    }

    public function isDocument(): bool
    {
        return $this->type === 'document' || in_array($this->mime_type ?? '', [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    /**
     * Supprimer le fichier associé
     */
    public function deleteFile(): bool
    {
        if ($this->file_path && Storage::disk('public')->exists($this->file_path)) {
            Storage::disk('public')->delete($this->file_path);
        }

        if ($this->thumbnail_path && Storage::disk('public')->exists($this->thumbnail_path)) {
            Storage::disk('public')->delete($this->thumbnail_path);
        }

        return true;
    }

    /**
     * Boot
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($media) {
            $media->deleteFile();
        });
    }
}
