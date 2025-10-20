<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle UsageRecord pour l'application NetLab
 *
 * Ce modèle enregistre l'utilisation effective des laboratoires par les utilisateurs.
 * Il permet de suivre le temps d'utilisation réel et de calculer les coûts effectifs
 * après la fin d'une session de laboratoire.
 *
 * @property int $id Identifiant unique de l'enregistrement d'utilisation
 * @property int $reservation_id Identifiant de la réservation associée
 * @property int $user_id Identifiant de l'utilisateur qui a utilisé le lab
 * @property int $lab_id Identifiant du laboratoire utilisé
 * @property \Carbon\Carbon $started_at Date et heure de début d'utilisation effective
 * @property \Carbon\Carbon $ended_at Date et heure de fin d'utilisation effective
 * @property int $duration_seconds Durée totale d'utilisation en secondes
 * @property int $cost_cents Coût total en centimes pour cette utilisation
 * @property \Carbon\Carbon $created_at Date de création de l'enregistrement
 * @property \Carbon\Carbon $updated_at Date de dernière modification
 */
class UsageRecord extends Model
{
    /**
     * Les attributs qui peuvent être assignés en masse
     *
     * @var array<string>
     */
    protected $fillable = [
        'reservation_id',
        'user_id',
        'lab_id',
        'started_at',
        'ended_at',
        'duration_seconds',
        'cost_cents'
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs
     *
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    /**
     * Relation avec la réservation associée
     *
     * Chaque enregistrement d'utilisation est lié à une réservation spécifique.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Reservation>
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Relation avec l'utilisateur qui a utilisé le lab
     *
     * Chaque enregistrement d'utilisation appartient à un utilisateur spécifique.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec le laboratoire utilisé
     *
     * Chaque enregistrement d'utilisation concerne un laboratoire spécifique.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Lab>
     */
    public function lab(): BelongsTo
    {
        return $this->belongsTo(Lab::class);
    }
}
