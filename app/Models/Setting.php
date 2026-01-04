<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * @mixin IdeHelperSetting
 */
class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'is_encrypted',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    /**
     * Récupérer une valeur de configuration
     */
    public static function get(string $key, $default = null)
    {
        try {
            // Vérifier que la table existe avant d'essayer de la lire
            if (!self::tableExists()) {
                return $default;
            }

            $setting = self::where('key', $key)->first();
            
            if (!$setting) {
                return $default;
            }

            $value = $setting->is_encrypted 
                ? Crypt::decryptString($setting->value) 
                : $setting->value;

            // Convertir selon le type
            return match($setting->type) {
                'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                'integer' => (int) $value,
                'json' => json_decode($value, true),
                default => $value,
            };
        } catch (\Exception $e) {
            // Si la table n'existe pas ou en cas d'erreur, retourner la valeur par défaut
            // Ne logger que si c'est une vraie erreur (pas juste une table manquante)
            if (!str_contains($e->getMessage(), 'does not exist') && 
                !str_contains($e->getMessage(), 'relation') &&
                !str_contains($e->getMessage(), 'Base table')) {
                \Log::warning('Setting::get() error', [
                    'key' => $key,
                    'error' => $e->getMessage(),
                ]);
            }
            return $default;
        }
    }

    /**
     * Vérifier si la table settings existe dans la base de données
     */
    protected static function tableExists(): bool
    {
        try {
            // Utiliser DB::connection() au lieu de getConnection() sur une instance
            $connection = DB::connection();
            $schemaBuilder = $connection->getSchemaBuilder();
            return $schemaBuilder->hasTable((new static)->getTable());
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Définir une valeur de configuration
     */
    public static function set(string $key, $value, string $type = 'string', ?string $description = null, bool $encrypt = false): ?self
    {
        try {
            // Vérifier que la table existe avant d'essayer de créer/modifier
            if (!self::tableExists()) {
                \Log::warning('Setting::set() appelé mais la table settings n\'existe pas', [
                    'key' => $key,
                ]);
                return null;
            }

            // Convertir la valeur selon le type
            $storedValue = match($type) {
                'boolean' => $value ? '1' : '0',
                'integer' => (string) $value,
                'json' => json_encode($value),
                default => (string) $value,
            };

            // Crypter si nécessaire
            if ($encrypt) {
                $storedValue = Crypt::encryptString($storedValue);
            }

            return self::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $storedValue,
                    'type' => $type,
                    'description' => $description,
                    'is_encrypted' => $encrypt,
                ]
            );
        } catch (\Exception $e) {
            // Si la table n'existe pas ou en cas d'erreur, logger et retourner null
            if (!str_contains($e->getMessage(), 'does not exist') && 
                !str_contains($e->getMessage(), 'relation') &&
                !str_contains($e->getMessage(), 'Base table')) {
                \Log::warning('Setting::set() error', [
                    'key' => $key,
                    'error' => $e->getMessage(),
                ]);
            }
            return null;
        }
    }

    /**
     * Accesseur pour obtenir la valeur décryptée
     */
    public function getDecryptedValueAttribute()
    {
        if (!$this->is_encrypted) {
            return $this->value;
        }

        try {
            return Crypt::decryptString($this->value);
        } catch (\Exception $e) {
            return null;
        }
    }
}
