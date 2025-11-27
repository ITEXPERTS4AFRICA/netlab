<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CiscoApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NodeController extends Controller
{
    /**
     * Obtenir les interfaces d'un node
     */
    public function interfaces(string $labId, string $nodeId, CiscoApiService $cisco): JsonResponse
    {
        $token = session('cml_token');
        if ($token) {
            $cisco->setToken($token);
        }

        if (!$token) {
            return response()->json([
                'error' => 'Token CML non disponible. Veuillez vous reconnecter.',
                'status' => 401,
            ], 401);
        }

        try {
            $interfaces = $cisco->nodes->getNodeInterfaces($labId, $nodeId);

            if (isset($interfaces['error'])) {
                \Log::warning('Erreur lors de la récupération des interfaces', [
                    'lab_id' => $labId,
                    'node_id' => $nodeId,
                    'error' => $interfaces['error'],
                ]);
                return response()->json($interfaces, $interfaces['status'] ?? 500);
            }

            \Log::info('Interfaces brutes reçues de CML', [
                'lab_id' => $labId,
                'node_id' => $nodeId,
                'type' => gettype($interfaces),
                'is_array' => is_array($interfaces),
                'count' => is_array($interfaces) ? count($interfaces) : 0,
                'sample' => is_array($interfaces) && count($interfaces) > 0 ? $interfaces[0] : null,
            ]);

            // Normaliser les interfaces : l'API CML peut retourner un tableau d'UUIDs ou un tableau d'objets
            $normalizedInterfaces = [];
            if (is_array($interfaces)) {
                foreach ($interfaces as $key => $interface) {
                    $interfaceId = null;
                    
                    // Cas 1: Tableau d'UUIDs (strings) - utiliser directement l'UUID
                    if (is_string($interface) && preg_match('/^[\da-f]{8}-[\da-f]{4}-4[\da-f]{3}-[89ab][\da-f]{3}-[\da-f]{12}$/i', $interface)) {
                        $interfaceId = $interface;
                    }
                    // Cas 2: Objet avec champ 'id'
                    elseif (is_array($interface) && isset($interface['id']) && is_string($interface['id'])) {
                        $interfaceId = $interface['id'];
                    }
                    // Cas 3: Clé du tableau est un UUID (objet avec UUID comme clé)
                    elseif (is_string($key) && preg_match('/^[\da-f]{8}-[\da-f]{4}-4[\da-f]{3}-[89ab][\da-f]{3}-[\da-f]{12}$/i', $key)) {
                        $interfaceId = $key;
                    }
                    
                    // Si on n'a pas d'ID valide, ignorer cette interface
                    if (!$interfaceId) {
                        \Log::warning('Interface ignorée (pas d\'ID UUID valide)', [
                            'lab_id' => $labId,
                            'node_id' => $nodeId,
                            'key' => $key,
                            'key_type' => gettype($key),
                            'interface' => $interface,
                            'interface_type' => gettype($interface),
                        ]);
                        continue;
                    }
                    
                    // Construire l'interface normalisée
                    if (is_array($interface)) {
                        $normalizedInterface = [
                            'id' => $interfaceId,
                            'label' => $interface['label'] ?? $interface['name'] ?? $interfaceId,
                            'type' => $interface['type'] ?? null,
                            'is_connected' => $interface['is_connected'] ?? ($interface['state'] === 'STARTED' || $interface['state'] === 'started' || $interface['state'] === 'up'),
                            'state' => $interface['state'] ?? null,
                            'mac_address' => $interface['mac_address'] ?? $interface['mac'] ?? null,
                            'node' => $interface['node'] ?? $nodeId,
                        ];
                    } else {
                        // Interface simple (juste l'UUID)
                        $normalizedInterface = [
                            'id' => $interfaceId,
                            'label' => $interfaceId,
                            'type' => null,
                            'is_connected' => false,
                            'state' => null,
                            'mac_address' => null,
                            'node' => $nodeId,
                        ];
                    }
                    
                    $normalizedInterfaces[] = $normalizedInterface;
                }
            }

            \Log::info('Interfaces normalisées', [
                'lab_id' => $labId,
                'node_id' => $nodeId,
                'count' => count($normalizedInterfaces),
                'sample' => $normalizedInterfaces[0] ?? null,
            ]);

            return response()->json([
                'interfaces' => $normalizedInterfaces,
            ])->header('Cache-Control', 'public, max-age=30');
        } catch (\Exception $e) {
            \Log::error('Exception lors de la récupération des interfaces', [
                'lab_id' => $labId,
                'node_id' => $nodeId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'Erreur lors de la récupération des interfaces: ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Obtenir les liens d'un lab (filtrés par node si nécessaire)
     */
    public function links(string $labId, CiscoApiService $cisco, Request $request): JsonResponse
    {
        $token = session('cml_token');
        if ($token) {
            $cisco->setToken($token);
        }

        if (!$token) {
            return response()->json([
                'error' => 'Token CML non disponible. Veuillez vous reconnecter.',
                'status' => 401,
            ], 401);
        }

        try {
            $links = $cisco->links->getLabLinks($labId);

            if (isset($links['error'])) {
                \Log::warning('Erreur lors de la récupération des liens', [
                    'lab_id' => $labId,
                    'error' => $links['error'],
                ]);
                return response()->json($links, $links['status'] ?? 500);
            }

            // Filtrer par node_id si fourni
            $nodeId = $request->query('node_id');
            if ($nodeId && is_array($links)) {
                $links = array_filter($links, function($link) use ($nodeId) {
                    if (!is_array($link)) return false;
                    $nodeA = $link['node_a'] ?? $link['n1'] ?? null;
                    $nodeB = $link['node_b'] ?? $link['n2'] ?? null;
                    return $nodeA === $nodeId || $nodeB === $nodeId;
                });
                $links = array_values($links); // Réindexer
            }

            return response()->json([
                'links' => $links,
            ])->header('Cache-Control', 'public, max-age=30');
        } catch (\Exception $e) {
            \Log::error('Exception lors de la récupération des liens', [
                'lab_id' => $labId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'Erreur lors de la récupération des liens: ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Connecter (démarrer) une interface
     */
    public function connectInterface(string $labId, string $interfaceId, CiscoApiService $cisco): JsonResponse
    {
        $token = session('cml_token');
        if ($token) {
            $cisco->setToken($token);
        }

        if (!$token) {
            return response()->json([
                'error' => 'Token CML non disponible. Veuillez vous reconnecter.',
                'status' => 401,
            ], 401);
        }

        try {
            \Log::info('Tentative de connexion d\'interface', [
                'lab_id' => $labId,
                'interface_id' => $interfaceId,
            ]);

            $result = $cisco->interfaces->startInterface($labId, $interfaceId);

            \Log::info('Résultat de connexion d\'interface', [
                'lab_id' => $labId,
                'interface_id' => $interfaceId,
                'result' => $result,
            ]);

            if (isset($result['error'])) {
                \Log::warning('Erreur lors de la connexion de l\'interface', [
                    'lab_id' => $labId,
                    'interface_id' => $interfaceId,
                    'error' => $result['error'],
                    'status' => $result['status'] ?? null,
                ]);
                return response()->json($result, $result['status'] ?? 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Interface connectée avec succès',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            \Log::error('Exception lors de la connexion de l\'interface', [
                'lab_id' => $labId,
                'interface_id' => $interfaceId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'Erreur lors de la connexion de l\'interface: ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Déconnecter (arrêter) une interface
     */
    public function disconnectInterface(string $labId, string $interfaceId, CiscoApiService $cisco): JsonResponse
    {
        $token = session('cml_token');
        if ($token) {
            $cisco->setToken($token);
        }

        if (!$token) {
            return response()->json([
                'error' => 'Token CML non disponible. Veuillez vous reconnecter.',
                'status' => 401,
            ], 401);
        }

        try {
            \Log::info('Tentative de déconnexion d\'interface', [
                'lab_id' => $labId,
                'interface_id' => $interfaceId,
            ]);

            $result = $cisco->interfaces->stopInterface($labId, $interfaceId);

            \Log::info('Résultat de déconnexion d\'interface', [
                'lab_id' => $labId,
                'interface_id' => $interfaceId,
                'result' => $result,
            ]);

            if (isset($result['error'])) {
                \Log::warning('Erreur lors de la déconnexion de l\'interface', [
                    'lab_id' => $labId,
                    'interface_id' => $interfaceId,
                    'error' => $result['error'],
                    'status' => $result['status'] ?? null,
                ]);
                return response()->json($result, $result['status'] ?? 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Interface déconnectée avec succès',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            \Log::error('Exception lors de la déconnexion de l\'interface', [
                'lab_id' => $labId,
                'interface_id' => $interfaceId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'Erreur lors de la déconnexion de l\'interface: ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Connecter (démarrer) un lien
     */
    public function connectLink(string $labId, string $linkId, CiscoApiService $cisco): JsonResponse
    {
        $token = session('cml_token');
        if ($token) {
            $cisco->setToken($token);
        }

        if (!$token) {
            return response()->json([
                'error' => 'Token CML non disponible. Veuillez vous reconnecter.',
                'status' => 401,
            ], 401);
        }

        try {
            $result = $cisco->links->startLink($labId, $linkId);

            if (isset($result['error'])) {
                \Log::warning('Erreur lors de la connexion du lien', [
                    'lab_id' => $labId,
                    'link_id' => $linkId,
                    'error' => $result['error'],
                ]);
                return response()->json($result, $result['status'] ?? 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Lien connecté avec succès',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            \Log::error('Exception lors de la connexion du lien', [
                'lab_id' => $labId,
                'link_id' => $linkId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'Erreur lors de la connexion du lien: ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Déconnecter (arrêter) un lien
     */
    public function disconnectLink(string $labId, string $linkId, CiscoApiService $cisco): JsonResponse
    {
        $token = session('cml_token');
        if ($token) {
            $cisco->setToken($token);
        }

        if (!$token) {
            return response()->json([
                'error' => 'Token CML non disponible. Veuillez vous reconnecter.',
                'status' => 401,
            ], 401);
        }

        try {
            $result = $cisco->links->stopLink($labId, $linkId);

            if (isset($result['error'])) {
                \Log::warning('Erreur lors de la déconnexion du lien', [
                    'lab_id' => $labId,
                    'link_id' => $linkId,
                    'error' => $result['error'],
                ]);
                return response()->json($result, $result['status'] ?? 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Lien déconnecté avec succès',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            \Log::error('Exception lors de la déconnexion du lien', [
                'lab_id' => $labId,
                'link_id' => $linkId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'Erreur lors de la déconnexion du lien: ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }
}

