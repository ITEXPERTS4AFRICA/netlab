<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modèle Rate pour l'application NetLab
 *
 * Ce modèle gère les tarifs de facturation pour l'utilisation des laboratoires.
 * Il définit les coûts par minute d'utilisation selon différents tarifs.
 *
 * @property int $id Identifiant unique du tarif
 * @property string $name Nom du tarif (ex: "Standard", "Premium", "Étudiant")
 * @property int $cents_per_minute Coût en centimes par minute d'utilisation
 * @property string $currency Devise utilisée pour le tarif (EUR, USD, etc.)
 * @property \Carbon\Carbon $created_at Date de création du tarif
 * @property \Carbon\Carbon $updated_at Date de dernière modification
 */
class Rate extends Model
{
    /**
     * Les attributs qui peuvent être assignés en masse
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'cents_per_minute',
        'currency'
    ];
}
