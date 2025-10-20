<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Modèle Reservation pour l'application NetLab
 *
 * Ce modèle gère les réservations de laboratoires réseau par les utilisateurs.
 * Il assure le suivi des créneaux de réservation, leur statut et les coûts associés.
 *
 * @property int $id Identifiant unique de la réservation
 * @property int $user_id Identifiant de l'utilisateur qui a fait la réservation
 * @property int $lab_id Identifiant du laboratoire réservé
 * @property int|null $rate_id Identifiant du tarif appliqué (peut être null)
 * @property \Carbon\Carbon $start_at Date et heure de début de la réservation
 * @property \Carbon\Carbon $end_at Date et heure de fin de la réservation
 * @property string $status Statut de la réservation (active, cancelled, completed, etc.)
 * @property int|null $estimated_cents Coût estimé en centimes (peut être null)
 * @property string|null $notes Notes supplémentaires sur la réservation
 * @property \Carbon\Carbon $created_at Date de création de la réservation
 * @property \Carbon\Carbon $updated_at Date de dernière modification
 */
class Reservation extends Model
{
    /**
     * Les attributs qui peuvent être assignés en masse
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'lab_id',
        'rate_id',
        'start_at',
        'end_at',
        'status',
        'estimated_cents',
        'notes'
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    /**
     * Relation avec l'utilisateur qui a fait la réservation
     *
     * Une réservation appartient à un utilisateur spécifique.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec le laboratoire réservé
     *
     * Une réservation concerne un laboratoire spécifique.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Lab>
     */
    public function lab(): BelongsTo
    {
        return $this->belongsTo(Lab::class);
    }

    /**
     * Relation avec le tarif appliqué à cette réservation
     *
     * Une réservation peut être associée à un tarif spécifique (optionnel).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Rate>
     */
    public function rate(): BelongsTo
    {
        return $this->belongsTo(Rate::class);
    }

    /**
     * Relation avec l'enregistrement d'utilisation associé
     *
     * Une réservation peut avoir un enregistrement d'utilisation pour le suivi.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<\App\Models\UsageRecord>
     */
    public function usageRecord(): HasOne
    {
        return $this->hasOne(UsageRecord::class);
    }
}
