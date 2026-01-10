<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable; 
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use App\Models\Reservation;
use App\Models\Payment;
use App\Models\TokenTransaction;

/**
 * @mixin IdeHelperUser
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable,HasUlids; 
    

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'avatar',
        'bio',
        'phone',
        'organization',
        'department',
        'position',
        'skills',
        'certifications',
        'education',
        'total_reservations',
        'total_labs_completed',
        'last_activity_at',
        'metadata',
        // Champs CML
        'cml_username',
        'cml_user_id',
        'cml_admin',
        'cml_groups',
        'cml_resource_pool_id',
        'cml_pubkey',
        'cml_directory_dn',
        'cml_opt_in',
        'cml_tour_version',
        'cml_token',
        'cml_token_expires_at',
        'cml_owned_labs',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'cml_token', // Token CML ne doit pas être exposé dans les réponses JSON
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
            'is_active' => 'boolean',
            'skills' => 'array',
            'certifications' => 'array',
            'education' => 'array',
            'metadata' => 'array',
            'last_activity_at' => 'datetime',
            // Casts CML
            'cml_admin' => 'boolean',
            'cml_groups' => 'array',
            'cml_opt_in' => 'boolean',
            'cml_token_expires_at' => 'datetime',
            'cml_owned_labs' => 'array',
        ];
    }

    /**
     * Relations
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function otpCodes(): HasMany
    {
        return $this->hasMany(OtpCode::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function tokenTransactions(): HasMany
    {
        return $this->hasMany(TokenTransaction::class);
    }

    /**
     * Vérifier si l'utilisateur est admin CML
     */
    public function isCmlAdmin(): bool
    {
        return $this->cml_admin === true;
    }

    /**
     * Vérifier si l'utilisateur a un token CML valide
     */
    public function hasValidCmlToken(): bool
    {
        return !empty($this->cml_token) && 
               $this->cml_token_expires_at && 
               $this->cml_token_expires_at->isFuture();
    }

    /**
     * Vérifier si l'utilisateur appartient à un groupe CML
     */
    public function belongsToCmlGroup(string $groupId): bool
    {
        return in_array($groupId, $this->cml_groups ?? []);
    }

    /**
     * Vérifier si l'utilisateur possède un lab CML
     */
    public function ownsCmlLab(string $labId): bool
    {
        return in_array($labId, $this->cml_owned_labs ?? []);
    }

    /**
     * Ajouter des tokens à l'utilisateur
     */
    public function addTokens(int $amount, string $type, string $description = null, string $referenceId = null): TokenTransaction
    {
        $this->increment('tokens_balance', $amount);

        return TokenTransaction::create([
            'user_id' => $this->id,
            'amount' => $amount,
            'type' => $type,
            'description' => $description,
            'reference_id' => $referenceId,
        ]);
    }

    /**
     * Déduire des tokens de l'utilisateur
     */
    public function deductTokens(int $amount, string $type, string $description = null, string $referenceId = null): ?TokenTransaction
    {
        if ($this->tokens_balance < $amount) {
            return null; // Solde insuffisant
        }

        $this->decrement('tokens_balance', $amount);

        return TokenTransaction::create([
            'user_id' => $this->id,
            'amount' => -$amount,
            'type' => $type,
            'description' => $description,
            'reference_id' => $referenceId,
        ]);
    }

    /**
     * Vérifier si l'utilisateur a suffisamment de tokens
     */
    public function hasEnoughTokens(int $amount): bool
    {
        return $this->tokens_balance >= $amount;
    }
}
