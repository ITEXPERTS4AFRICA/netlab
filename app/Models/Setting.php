<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

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
    }

    /**
     * Définir une valeur de configuration
     */
    public static function set(string $key, $value, string $type = 'string', ?string $description = null, bool $encrypt = false): self
    {
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
