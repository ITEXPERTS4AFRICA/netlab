<?php

namespace App\Services\Cisco;

use Illuminate\Support\Facades\Validator;

class ValidationService
{
    /**
     * Valider les données d'un lab
     */
    public function validateLabData(array $data): array
    {
        $validator = Validator::make($data, [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'nodes' => 'nullable|array',
            'links' => 'nullable|array',
        ]);

        return [
            'valid' => $validator->passes(),
            'errors' => $validator->errors()->all()
        ];
    }

    /**
     * Valider la configuration d'un node
     */
    public function validateNodeConfig(array $config): array
    {
        $validator = Validator::make($config, [
            'label' => 'required|string|max:255',
            'node_definition' => 'required|string',
            'x' => 'nullable|integer',
            'y' => 'nullable|integer',
            'configuration' => 'nullable|array',
        ]);

        return [
            'valid' => $validator->passes(),
            'errors' => $validator->errors()->all()
        ];
    }

    /**
     * Nettoyer la configuration
     */
    public function sanitizeConfig(array $config): array
    {
        return array_filter($config, function($value) {
            return !is_null($value) && $value !== '';
        });
    }

    /**
     * Valider un token JWT
     */
    public function validateToken(string $token): bool
    {
        return preg_match('/^[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+$/', $token) === 1;
    }

    /**
     * Vérifier les permissions utilisateur
     */
    public function checkPermissions(string $userId, string $labId, string $action): bool
    {
        // Logique de vérification des permissions
        // À implémenter selon vos besoins
        return true;
    }

    /**
     * Valider l'adresse IP
     */
    public function validateIpAddress(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Valider une configuration réseau
     */
    public function validateNetworkConfig(array $config): array
    {
        $errors = [];

        if (isset($config['ipv4_address'])) {
            if (!$this->validateIpAddress($config['ipv4_address'])) {
                $errors[] = 'Invalid IPv4 address';
            }
        }

        if (isset($config['subnet_mask'])) {
            if (!$this->validateSubnetMask($config['subnet_mask'])) {
                $errors[] = 'Invalid subnet mask';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Valider un masque de sous-réseau
     */
    protected function validateSubnetMask(string $mask): bool
    {
        if (!$this->validateIpAddress($mask)) {
            return false;
        }

        $parts = explode('.', $mask);
        $binary = '';
        
        foreach ($parts as $part) {
            $binary .= str_pad(decbin((int)$part), 8, '0', STR_PAD_LEFT);
        }

        return preg_match('/^1*0*$/', $binary) === 1;
    }

    /**
     * Sécuriser les données sensibles
     */
    public function secureSensitiveData(array $data): array
    {
        $sensitive = ['password', 'secret', 'token', 'api_key'];
        
        foreach ($sensitive as $key) {
            if (isset($data[$key])) {
                $data[$key] = '***REDACTED***';
            }
        }

        return $data;
    }

    /**
     * Vérifier les limites de ressources
     */
    public function checkResourceLimits(array $labData): array
    {
        $limits = config('cml.limits', [
            'max_nodes' => 100,
            'max_links' => 200,
            'max_labs' => 50
        ]);

        $errors = [];

        if (isset($labData['nodes']) && count($labData['nodes']) > $limits['max_nodes']) {
            $errors[] = "Too many nodes (max: {$limits['max_nodes']})";
        }

        if (isset($labData['links']) && count($labData['links']) > $limits['max_links']) {
            $errors[] = "Too many links (max: {$limits['max_links']})";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}

