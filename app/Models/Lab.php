<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lab extends Model
{
    protected $fillable = [
        'cml_id',
        'created',
        'modified',
        'lab_description',
        'node_count',
        'state',
        'lab_title',
        'owner',
        'link_count',
        'effective_permissions',
        // Métadonnées
        'price_cents',
        'currency',
        'readme',
        'short_description',
        'tags',
        'categories',
        'difficulty_level',
        'estimated_duration_minutes',
        'is_featured',
        'is_published',
        'view_count',
        'reservation_count',
        'rating',
        'rating_count',
        'requirements',
        'learning_objectives',
        'metadata',
    ];

    protected $casts = [
        // lab_description est stocké en JSON (string encodée en JSON)
        // On utilise un accessor personnalisé pour le décoder correctement
        'lab_description' => 'array', // Laravel décodera automatiquement
        'effective_permissions' => 'array',
        'tags' => 'array',
        'categories' => 'array',
        'requirements' => 'array',
        'learning_objectives' => 'array',
        'metadata' => 'array',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'price_cents' => 'integer',
        'view_count' => 'integer',
        'reservation_count' => 'integer',
        'rating_count' => 'integer',
        'rating' => 'decimal:2',
        'estimated_duration_minutes' => 'integer',
    ];

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function usageRecords(): HasMany
    {
        return $this->hasMany(UsageRecord::class);
    }

    public function documentationMedia(): HasMany
    {
        return $this->hasMany(LabDocumentationMedia::class)->orderBy('order');
    }

    public function activeDocumentationMedia(): HasMany
    {
        return $this->hasMany(LabDocumentationMedia::class)
            ->where('is_active', true)
            ->orderBy('order');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(LabSnapshot::class)->orderBy('snapshot_at', 'desc');
    }

    public function defaultSnapshot(): HasOne
    {
        return $this->hasOne(LabSnapshot::class)->where('is_default', true);
    }

    /**
     * Accessor pour lab_description
     * Décoder le JSON et retourner la string originale si c'était une string encodée
     */
    public function getLabDescriptionAttribute($value)
    {
        if (is_null($value)) {
            return null;
        }
        
        // Si c'est déjà décodé par Laravel (array), vérifier si c'était une string simple
        if (is_array($value)) {
            // Si c'est un array avec une seule clé numérique ou une string, retourner la string
            if (count($value) === 1 && isset($value[0]) && is_string($value[0])) {
                return $value[0];
            }
            return $value;
        }
        
        // Si c'est une string JSON, la décoder
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            // Si le JSON décodé est une string simple, la retourner
            if (is_string($decoded)) {
                return $decoded;
            }
            return $decoded ?? $value;
        }
        
        return $value;
    }

    /**
     * Helpers
     */
    public function getFormattedPriceAttribute(): string
    {
        if (!$this->price_cents) {
            return 'Gratuit';
        }
        return number_format($this->price_cents / 100, 0, ',', ' ') . ' ' . $this->currency;
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function incrementReservationCount(): void
    {
        $this->increment('reservation_count');
    }

    public function updateRating(float $newRating): void
    {
        $currentTotal = ($this->rating ?? 0) * $this->rating_count;
        $this->rating_count++;
        $calculatedRating = round(($currentTotal + $newRating) / $this->rating_count, 2);
        $this->update([
            'rating' => $calculatedRating,
            'rating_count' => $this->rating_count,
        ]);
    }
}
