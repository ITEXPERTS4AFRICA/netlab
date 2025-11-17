<?php

namespace App\Helpers;

use App\Models\Setting;

/**
 * Helper pour récupérer la configuration CML
 * Priorité : Base de données (Setting) > config/services.php > .env
 */
class CmlConfigHelper
{
    /**
     * Obtenir l'URL de base CML
     */
    public static function getBaseUrl(): ?string
    {
        try {
            // Essayer depuis la base de données d'abord
            $baseUrl = Setting::get('cml.base_url');
            
            if ($baseUrl) {
                return rtrim($baseUrl, '/');
            }
        } catch (\Exception $e) {
            // Si la table settings n'existe pas encore, continuer avec le fallback
        }

        // Fallback sur config/services.php
        $baseUrl = config('services.cml.base_url');
        
        if ($baseUrl) {
            return rtrim($baseUrl, '/');
        }

        return null;
    }

    /**
     * Obtenir le nom d'utilisateur CML
     */
    public static function getUsername(): ?string
    {
        try {
            $username = Setting::get('cml.username');
            if ($username) {
                return $username;
            }
        } catch (\Exception $e) {
            // Si la table settings n'existe pas encore, continuer avec le fallback
        }

        return config('services.cml.username') ?? env('CML_USERNAME');
    }

    /**
     * Obtenir le mot de passe CML
     */
    public static function getPassword(): ?string
    {
        try {
            $password = Setting::get('cml.password');
            if ($password) {
                return $password;
            }
        } catch (\Exception $e) {
            // Si la table settings n'existe pas encore, continuer avec le fallback
        }

        return config('services.cml.password') ?? env('CML_PASSWORD');
    }

    /**
     * Obtenir toutes les credentials CML
     */
    public static function getCredentials(): array
    {
        return [
            'base_url' => self::getBaseUrl(),
            'username' => self::getUsername(),
            'password' => self::getPassword(),
        ];
    }

    /**
     * Vérifier si la configuration CML est complète
     */
    public static function isConfigured(): bool
    {
        $credentials = self::getCredentials();
        return !empty($credentials['base_url']) 
            && !empty($credentials['username']) 
            && !empty($credentials['password']);
    }
}

