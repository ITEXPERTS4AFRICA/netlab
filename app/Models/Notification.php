<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle Notification pour l'application NetLab
 *
 * Ce modèle gère le système de notifications interne de l'application.
 * Il permet de stocker et gérer les notifications pour les utilisateurs,
 * incluant les notifications de réservation, de système, etc.
 *
 * @property int $id Identifiant unique de la notification
 * @property int $user_id Identifiant de l'utilisateur destinataire
 * @property string $type Type de notification (reservation, system, lab_availability, etc.)
 * @property string $title Titre de la notification
 * @property string $message Contenu du message de notification
 * @property string $priority Niveau de priorité (low, medium, high, urgent)
 * @property string $category Catégorie de notification (labs, reservations, system, etc.)
 * @property array|null $data Données supplémentaires (stockées en JSON)
 * @property string|null $action_url URL d'action associée à la notification
 * @property bool $read Statut de lecture de la notification
 * @property \Carbon\Carbon|null $read_at Date de lecture de la notification
 * @property \Carbon\Carbon $created_at Date de création de la notification
 * @property \Carbon\Carbon $updated_at Date de dernière modification
 */
class Notification extends Model
{
    /**
     * Les attributs qui peuvent être assignés en masse
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'priority',
        'category',
        'data',
        'action_url',
        'read',
        'read_at',
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Get the user that owns the notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark the notification as read.
     */
    public function markAsRead(): bool
    {
        if ($this->read) {
            return false;
        }

        $this->update([
            'read' => true,
            'read_at' => now(),
        ]);

        return true;
    }

    /**
     * Mark the notification as unread.
     */
    public function markAsUnread(): bool
    {
        if (!$this->read) {
            return false;
        }

        $this->update([
            'read' => false,
            'read_at' => null,
        ]);

        return true;
    }

    /**
     * Scope for unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->where('read', false);
    }

    /**
     * Scope for read notifications.
     */
    public function scopeRead($query)
    {
        return $query->where('read', true);
    }

    /**
     * Scope for notifications by priority.
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for notifications by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for notifications by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
