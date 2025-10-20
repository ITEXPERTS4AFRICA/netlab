<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle Lab pour l'application NetLab
 *
 * Ce modèle représente les laboratoires réseau Cisco Modeling Labs (CML) disponibles
 * dans le système. Il synchronise les données depuis l'API CML et gère les réservations.
 *
 * @property int $id Identifiant unique du lab dans la base de données locale
 * @property string $cml_id Identifiant unique du lab dans le système CML
 * @property string $created Date de création du lab dans CML (format ISO 8601)
 * @property string $modified Date de dernière modification du lab dans CML (format ISO 8601)
 * @property string|null $lab_description Description détaillée du lab
 * @property int $node_count Nombre de nœuds/équipements réseau dans le lab
 * @property string $state État actuel du lab (DEFINED_ON_CORE, STOPPED, etc.)
 * @property string $lab_title Titre/nom du laboratoire
 * @property string $owner Propriétaire du lab dans CML
 * @property int $link_count Nombre de connexions réseau dans le lab
 * @property array $effective_permissions Permissions effectives sur le lab (stockées en JSON)
 * @property \Carbon\Carbon $created_at Date de création de l'enregistrement local
 * @property \Carbon\Carbon $updated_at Date de dernière modification de l'enregistrement local
 */
class Lab extends Model
{
    /**
     * Les attributs qui peuvent être assignés en masse
     *
     * @var array<string>
     */
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
        'effective_permissions'
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs
     *
     * @var array<string, string>
     */
    protected $casts = [
        'effective_permissions' => 'array',
    ];

    /**
     * Relation avec les réservations de ce lab
     *
     * Un lab peut avoir plusieurs réservations au cours du temps.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Reservation>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Relation avec les enregistrements d'utilisation de ce lab
     *
     * Un lab peut avoir plusieurs enregistrements d'utilisation pour le suivi.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\UsageRecord>
     */
    public function usageRecords(): HasMany
    {
        return $this->hasMany(UsageRecord::class);
    }
}
