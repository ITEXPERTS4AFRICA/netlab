<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * Modèle utilisateur pour l'application NetLab
 *
 * Ce modèle représente les utilisateurs du système de gestion de laboratoires réseau.
 * Il gère l'authentification, les préférences utilisateur et les paramètres de notification.
 *
 * @property string $id Identifiant unique ULID de l'utilisateur
 * @property string $name Nom complet de l'utilisateur
 * @property string $email Adresse email de l'utilisateur (doit être unique)
 * @property string $password Mot de passe hashé de l'utilisateur
 * @property array|null $preferences Préférences utilisateur personnalisées (stockées en JSON)
 * @property bool $notification_enabled Activation des notifications pour l'utilisateur
 * @property string|null $notification_type Type de notifications préféré (email, push, etc.)
 * @property string $timezone Fuseau horaire de l'utilisateur
 * @property string $language Langue préférée de l'utilisateur
 * @property bool $auto_start_labs Démarrage automatique des labs réservés
 * @property int $notification_advance_minutes Minutes d'avance pour les notifications de réservation
 * @property \Carbon\Carbon $email_verified_at Date de vérification de l'email
 * @property \Carbon\Carbon $created_at Date de création du compte
 * @property \Carbon\Carbon $updated_at Date de dernière modification
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasUlids;


    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'preferences',
        'notification_enabled',
        'notification_type',
        'timezone',
        'language',
        'auto_start_labs',
        'notification_advance_minutes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'preferences' => 'array',
            'notification_enabled' => 'boolean',
            'auto_start_labs' => 'boolean',
            'notification_advance_minutes' => 'integer',
        ];
    }
}
