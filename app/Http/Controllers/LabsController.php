<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\CiscoApiService;
use Illuminate\Support\Facades\Cache;
use App\Models\Lab;
use App\Models\Reservation;
use Illuminate\Support\Facades\Auth;
use App\Traits\HandlesCmlToken;

class LabsController extends Controller
{
    use HandlesCmlToken;
    /**
     * Display a listing of the resource.
     * Affiche uniquement les labs publiés (is_published = true)
     */
    public function index(Request $request, CiscoApiService $cisco)
    {
        try {
            // Vérifier si la colonne is_published existe avant de l'utiliser
            $hasPublishedColumn = Lab::hasColumn('is_published');
            
            // Récupérer les labs depuis la base de données
            $query = Lab::query();
            
            // Filtrer par is_published uniquement si la colonne existe
            if ($hasPublishedColumn) {
                $query->where('is_published', true);
            }
            
            $query->withCount(['reservations' => function ($q) {
                    $q->where('status', 'active')
                      ->where('end_at', '>', now());
                }]);
            
            // Trier par is_featured uniquement si la colonne existe
            if (Lab::hasColumn('is_featured')) {
                $query->orderBy('is_featured', 'desc'); // Labs en avant en premier
            }
            
            $query->orderBy('created_at', 'desc');

            // Recherche par titre ou description
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('lab_title', 'like', "%{$search}%")
                      ->orWhere('short_description', 'like', "%{$search}%")
                      ->orWhere('lab_description', 'like', "%{$search}%");
                });
            }

            // Filtre par difficulté
            if ($request->has('difficulty') && $request->difficulty !== 'all') {
                $query->where('difficulty_level', $request->difficulty);
            }

            // Filtre par état (optionnel, pour compatibilité)
            if ($request->has('state') && $request->state !== 'all') {
                $query->where('state', $request->state);
            }

            // Pagination
            $perPage = max(1, (int) $request->query('per_page', 12));
            $labs = $query->paginate($perPage);

            // Formater les labs pour le frontend (compatibilité avec l'ancien format)
            $formattedLabs = $labs->map(function ($lab) {
                // Décoder lab_description si c'est une string JSON
                $description = $lab->lab_description;
                if (is_string($description)) {
                    $decoded = json_decode($description, true);
                    if (is_string($decoded)) {
                        $description = $decoded;
                    } elseif (is_array($decoded)) {
                        $description = is_array($decoded) ? json_encode($decoded) : $description;
                    }
                }

                // Calculer le nombre d'interfaces : chaque lien connecte 2 interfaces
                // C'est une approximation du nombre d'interfaces connectées
                $linkCount = $lab->link_count ?? 0;
                $interfaceCount = $linkCount * 2;

                return [
                    'id' => $lab->cml_id, // Utiliser cml_id pour compatibilité avec l'ancien système
                    'db_id' => $lab->id, // ID de la base de données
                    'title' => $lab->lab_title ?? 'Sans titre', // Alias pour compatibilité
                    'state' => $lab->state ?? 'STOPPED',
                    'lab_title' => $lab->lab_title ?? 'Sans titre',
                    'lab_description' => $description ?? $lab->short_description ?? '',
                    'description' => $description ?? $lab->short_description ?? '', // Alias pour compatibilité
                    'short_description' => $lab->short_description,
                    'node_count' => $lab->node_count ?? 0,
                    'link_count' => $linkCount,
                    'interface_count' => $interfaceCount,
                    'created' => $lab->created ?? $lab->created_at->format('c'),
                    'modified' => $lab->modified ?? $lab->updated_at->format('c'),
                    // Métadonnées enrichies
                    'price_cents' => $lab->price_cents,
                    'currency' => $lab->currency ?? 'XOF',
                    'difficulty_level' => $lab->difficulty_level,
                    'estimated_duration_minutes' => $lab->estimated_duration_minutes,
                    'is_featured' => $lab->is_featured,
                    'rating' => $lab->rating ? (float) $lab->rating : null,
                    'rating_count' => $lab->rating_count,
                    'view_count' => $lab->view_count,
                    'reservation_count' => $lab->reservation_count,
                    'active_reservations_count' => $lab->reservations_count ?? 0,
                    'tags' => $lab->tags ?? [],
                    'categories' => $lab->categories ?? [],
                    'metadata' => $lab->metadata ?? [],
                ];
            });

            return Inertia::render('labs/Labs', [
                'labs' => $formattedLabs->toArray(),
                'pagination' => [
                    'page' => $labs->currentPage(),
                    'per_page' => $labs->perPage(),
                    'total' => $labs->total(),
                    'total_pages' => $labs->lastPage(),
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur récupération labs publiés', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Inertia::render('labs/Labs', [
                'labs' => [],
                'pagination' => [
                    'page' => 1,
                    'per_page' => 12,
                    'total' => 0,
                    'total_pages' => 0,
                ],
                'error' => 'Une erreur est survenue lors de la récupération des labs: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Liste des labs réservés par l'utilisateur
     */
    public function myReservedLabs(Request $request, CiscoApiService $cisco)
    {
        $user = Auth::user();
        
        // Obtenir ou rafraîchir automatiquement le token CML
        $token = $this->getOrRefreshCmlToken($cisco);

        // Si toujours pas de token après rafraîchissement, continuer sans erreur
        // Les fonctionnalités qui nécessitent CML seront simplement désactivées
        if (!$token) {
            \Log::warning('Token CML non disponible pour myReservedLabs, continuation sans erreur');
        }

        // Récupérer les réservations de l'utilisateur avec leurs labs (exclure les terminées)
        // Exclure les réservations "pending" qui nécessitent un paiement mais n'ont pas de paiement réussi
        // Exclure également les réservations pending expirées (créées il y a plus de 15 minutes)
        $reservations = Reservation::with(['lab', 'payments'])
            ->where('user_id', $user->id)
            ->where('status', '!=', 'cancelled')
            ->where('end_at', '>', now()) // Exclure les réservations terminées
            ->where(function($query) {
                // Inclure les réservations actives
                $query->where('status', 'active')
                    // Ou les réservations pending qui sont gratuites (estimated_cents = 0)
                    ->orWhere(function($q) {
                        $q->where('status', 'pending')
                          ->where('estimated_cents', 0)
                          ->where('created_at', '>', now()->subMinutes(15)); // Non expirées
                    })
                    // Ou les réservations pending qui ont un paiement réussi
                    ->orWhereHas('payments', function($q) {
                        $q->where('status', 'completed');
                    });
            })
            ->orderBy('start_at', 'desc')
            ->get();

        $reservedLabs = $reservations->map(function ($reservation) use ($token, $cisco) {
            $lab = $reservation->lab;

            // Vérifier l'état actuel du lab via CML
            $currentState = null;
            $timeInfo = null;

            if ($lab && $token) {
                try {
                    $labState = $cisco->getLabState($token, $lab->cml_id);
                    if (!isset($labState['error']) && !isset($labState['connection_error'])) {
                        // Extraire l'état du lab (peut être dans 'state' ou directement la valeur)
                        $currentState = is_array($labState)
                            ? ($labState['state'] ?? $labState['data']['state'] ?? null)
                            : (is_string($labState) ? $labState : null);

                        if ($currentState) {
                            // Mettre à jour l'état dans la base de données
                            $lab->state = $currentState;
                            $lab->save();
                        }
                    } else {
                        // En cas d'erreur (connexion ou autre), utiliser l'état de la base de données
                        $currentState = $lab->state ?? 'STOPPED';
                    }
                } catch (\Exception $e) {
                    // En cas d'exception, utiliser l'état de la base de données
                    \Log::warning('Erreur lors de la récupération de l\'état du lab', [
                        'lab_id' => $lab->id,
                        'error' => $e->getMessage(),
                    ]);
                    $currentState = $lab->state ?? 'STOPPED';
                }
            } else {
                $currentState = $lab->state ?? 'STOPPED';
            }

            // Calculer les informations temporelles
            $now = now();
            $isActive = $reservation->start_at <= $now && $reservation->end_at > $now;
            $canAccess = $isActive && in_array($currentState, ['DEFINED_ON_CORE', 'STARTED']);

            if ($isActive) {
                $timeRemaining = $now->diffInMinutes($reservation->end_at, false);
                $timeInfo = [
                    'status' => 'active',
                    'time_remaining_minutes' => max(0, $timeRemaining),
                    'end_time' => $reservation->end_at->format('H:i'),
                    'can_access' => $canAccess
                ];
            } elseif ($reservation->start_at > $now) {
                $timeToStart = $now->diffInMinutes($reservation->start_at, false);
                $timeInfo = [
                    'status' => 'pending',
                    'time_to_start_minutes' => max(0, $timeToStart),
                    'start_time' => $reservation->start_at->format('H:i'),
                    'can_access' => false
                ];
            } else {
                $timeInfo = [
                    'status' => 'expired',
                    'can_access' => false
                ];
            }

            // S'assurer que lab_description est une string, pas un objet
            $labDescription = $lab->lab_description;
            if (is_array($labDescription)) {
                $labDescription = json_encode($labDescription);
            } elseif (!is_string($labDescription)) {
                $labDescription = (string) $labDescription;
            }

            return [
                'reservation_id' => (string) $reservation->id,
                'lab_id' => (string) $lab->id,
                'cml_id' => (string) $lab->cml_id,
                'lab_title' => (string) ($lab->lab_title ?? ''),
                'lab_description' => $labDescription,
                'node_count' => (int) ($lab->node_count ?? 0),
                'current_state' => (string) ($currentState ?? 'STOPPED'),
                'reservation_start' => $reservation->start_at->format('Y-m-d H:i:s'),
                'reservation_end' => $reservation->end_at->format('Y-m-d H:i:s'),
                'duration_hours' => round($reservation->start_at->diffInHours($reservation->end_at), 1),
                'time_info' => $timeInfo,
                'can_access' => (bool) $canAccess,
                'status' => (string) $reservation->status,
            ];
        });

        return Inertia::render('labs/MyReservedLabs', [
            'reservedLabs' => $reservedLabs,
        ]);
    }

    public function workspace(Lab $lab, CiscoApiService $cisco)
    {
        // Obtenir ou rafraîchir automatiquement le token CML
        $token = $this->getOrRefreshCmlToken($cisco);

        // Ensure the lab exists in our database, create if not
        if (!$lab->exists) {
            $response = $cisco->getLab($token, $lab->cml_id);
            if (isset($response['error'])) {
                abort(404, 'Lab not found');
            }
            $lab = Lab::create([
                'cml_id' => $response['id'],
                'created' => $response['created'],
                'modified' => $response['modified'],
                'lab_description' => $response['lab_description'],
                'node_count' => $response['node_count'],
                'state' => $response['state'],
                'lab_title' => $response['lab_title'],
                'owner' => $response['owner'],
                'link_count' => $response['link_count'],
                'effective_permissions' => $response['effective_permissions']
            ]);
        }

        // Get reservation for the user (active or pending)
        $user = Auth::user();
        $reservation = Reservation::where('user_id', $user->id)
            ->where('lab_id', $lab->id)
            ->where('end_at', '>', now()) // Only exclude expired reservations
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_at', 'desc')
            ->first();

        // Check if user has a reservation (active or pending)
        if (!$reservation) {
            return redirect()->route('labs')->with('error', 'You do not have a reservation for this lab.');
        }

        // Get current lab state from CML (only if token is available)
        if ($token) {
            $labState = $cisco->getLabState($token, $lab->cml_id);
            if (!isset($labState['error'])) {
                // Extraire l'état : peut être dans 'state', 'data', ou directement la valeur
                $extractedState = null;
                if (is_array($labState)) {
                    // Essayer différentes structures possibles
                    if (isset($labState['state'])) {
                        $extractedState = $labState['state'];
                    } elseif (isset($labState['data'])) {
                        // Si data est un string (ex: {"data":"defined_on_core"})
                        if (is_string($labState['data'])) {
                            $extractedState = $labState['data'];
                        } 
                        // Si data est un array avec 'state' (ex: {"data":{"state":"defined_on_core"}})
                        elseif (is_array($labState['data']) && isset($labState['data']['state'])) {
                            $extractedState = $labState['data']['state'];
                        }
                    }
                } elseif (is_string($labState)) {
                    $extractedState = $labState;
                }
                
                // Si on a extrait un état valide, le normaliser
                if ($extractedState) {
                    // Normaliser les formats comme {"data":"defined_on_core"} -> "DEFINED_ON_CORE"
                    if (is_string($extractedState)) {
                        $extractedState = strtoupper(trim($extractedState));
                    } elseif (is_array($extractedState) && isset($extractedState['data'])) {
                        $extractedState = strtoupper(trim((string)$extractedState['data']));
                    }
                    $lab->state = $extractedState;
                $lab->save();
                }
            }
        }

        // Get annotations for the lab (only if token is available)
        $annotations = [];
        if ($token) {
        $annotations = $cisco->getLabsAnnotation($token, $lab->cml_id);
            if (isset($annotations['error'])) {
                $annotations = [];
            }
        }

        // Fetch lab nodes for console management (only if token is available)
        $nodes = [];
        if ($token) {
            $nodesData = $cisco->getLabNodes($token, $lab->cml_id);
            
            if (isset($nodesData['error'])) {
                \Log::warning('Erreur lors de la récupération des nodes', [
                    'lab_id' => $lab->cml_id,
                    'error' => $nodesData['error'],
                ]);
            $nodes = [];
            } elseif (is_array($nodesData)) {
                // Normaliser la réponse : l'API peut retourner soit un tableau de strings (UUIDs)
                // soit un tableau d'objets Node
                $nodes = array_map(function($node) {
                    if (is_string($node)) {
                        // Si c'est juste un UUID, on ne peut pas obtenir le label sans un appel supplémentaire
                        // On retourne l'UUID pour l'instant, mais on devrait faire un appel getNode() pour chaque UUID
                        return [
                            'id' => $node,
                            'label' => $node, // Temporaire, sera remplacé si on a les détails
                            'name' => $node,
                        ];
                    } elseif (is_array($node)) {
                        // Si c'est un objet Node, normaliser les champs
                        // L'API CML retourne 'label' pour le nom affiché du node
                        $nodeId = $node['id'] ?? $node['node_id'] ?? '';
                        $label = $node['label'] ?? $node['name'] ?? '';
                        
                        // Si pas de label, essayer d'autres champs
                        if (empty($label)) {
                            $label = $node['node_definition'] ?? $node['definition'] ?? $nodeId;
                        }
                        
                        return [
                            'id' => $nodeId,
                            'label' => $label,
                            'name' => $node['name'] ?? $label,
                            'state' => $node['state'] ?? null,
                            'node_definition' => $node['node_definition'] ?? $node['definition'] ?? null,
                        ];
                    }
                    return null;
                }, $nodesData);
                
                // Filtrer les valeurs null
                $nodes = array_filter($nodes, fn($node) => $node !== null && !empty($node['id']));
                $nodes = array_values($nodes); // Réindexer
                
                \Log::info('Nodes normalisés', [
                    'count' => count($nodes),
                    'sample' => $nodes[0] ?? null,
                ]);
            }
        }

        // Fetch active console sessions (best effort, only if token is available)
        $consoleSessions = [];
        if ($token) {
        $consoleSessions = $cisco->console->getConsoleSessions();
        if (isset($consoleSessions['error'])) {
            $consoleSessions = [];
            }
        }

        // Fetch lab topology (nodes, links, interfaces) for graph display
        // Charger la topologie si le lab est RUNNING ou STARTED
        $topology = null;
        $tile = null;
        $links = [];
        
        // Normaliser l'état pour la vérification (peut être en majuscules après notre normalisation)
        $normalizedState = strtoupper(trim($lab->state ?? ''));
        
        if ($token && ($normalizedState === 'RUNNING' || $normalizedState === 'STARTED')) {
            $cisco->setToken($token);
            
            // Récupérer la topologie
            $topology = $cisco->getTopology($token, $lab->cml_id);
            
            \Log::info('Topologie récupérée', [
                'lab_id' => $lab->cml_id,
                'has_error' => isset($topology['error']),
                'has_nodes' => isset($topology['nodes']) && is_array($topology['nodes']),
                'nodes_count' => isset($topology['nodes']) && is_array($topology['nodes']) ? count($topology['nodes']) : 0,
                'has_links' => isset($topology['links']) && is_array($topology['links']),
                'links_count' => isset($topology['links']) && is_array($topology['links']) ? count($topology['links']) : 0,
            ]);
            
            if (isset($topology['error'])) {
                \Log::warning('Erreur récupération topologie', [
                    'lab_id' => $lab->cml_id,
                    'error' => $topology['error'],
                ]);
                $topology = null;
            } elseif (is_array($topology)) {
                // Toujours utiliser les nodes de la topologie si disponibles (plus complets)
                $topologyNodes = $topology['nodes'] ?? [];
                if (is_array($topologyNodes) && !empty($topologyNodes)) {
                    $topologyNodesNormalized = array_map(function($node) {
                        if (is_array($node)) {
                            return [
                                'id' => $node['id'] ?? $node['node_id'] ?? '',
                                'label' => $node['label'] ?? $node['name'] ?? $node['id'] ?? '',
                                'name' => $node['name'] ?? $node['label'] ?? $node['id'] ?? '',
                                'state' => $node['state'] ?? null,
                                'node_definition' => $node['node_definition'] ?? $node['definition'] ?? null,
                                'x' => $node['x'] ?? null,
                                'y' => $node['y'] ?? null,
                            ];
                        }
                        return null;
                    }, $topologyNodes);
                    $topologyNodesNormalized = array_filter($topologyNodesNormalized, fn($node) => $node !== null && !empty($node['id']));
                    $topologyNodesNormalized = array_values($topologyNodesNormalized);
                    
                    // Utiliser les nodes de la topologie s'ils sont disponibles, sinon garder ceux récupérés précédemment
                    if (!empty($topologyNodesNormalized)) {
                        $nodes = $topologyNodesNormalized;
                        \Log::info('Nodes normalisés depuis topologie', [
                            'count' => count($nodes),
                        ]);
                    }
                }
                
                // Normaliser les links de la topologie si disponibles (sera enrichi plus tard avec les interfaces)
                // Format LinkTopology: i1, i2, n1, n2 (IDs courts comme "l1")
                // Format Link: interface_a, interface_b, node_a, node_b (UUIDs complets)
                $topologyLinks = $topology['links'] ?? [];
                if (is_array($topologyLinks) && !empty($topologyLinks)) {
                    $topology['links'] = array_map(function($link) {
                        if (is_array($link)) {
                            return [
                                'id' => $link['id'] ?? $link['link_id'] ?? uniqid('link_'),
                                'n1' => $link['n1'] ?? $link['node1'] ?? $link['node_a'] ?? $link['src'] ?? null,
                                'n2' => $link['n2'] ?? $link['node2'] ?? $link['node_b'] ?? $link['dst'] ?? null,
                                'i1' => $link['i1'] ?? $link['interface1'] ?? $link['interface_a'] ?? $link['src_interface'] ?? null,
                                'i2' => $link['i2'] ?? $link['interface2'] ?? $link['interface_b'] ?? $link['dst_interface'] ?? null,
                                'state' => $link['state'] ?? null,
                            ];
                        }
                        return $link;
                    }, $topologyLinks);
                }
            }
            
            // Also fetch tile for additional lab info
            $tile = $cisco->labs->getLabTile($lab->cml_id);
            if (isset($tile['error'])) {
                $tile = null;
            }
            
            // Fetch links for topology graph (utiliser les links de la topologie si disponibles, sinon récupérer séparément)
            if (!isset($topology['links']) || empty($topology['links'])) {
            $linksData = $cisco->getLabLinks($lab->cml_id);
            if (!isset($linksData['error']) && is_array($linksData)) {
                    // Normaliser les links récupérés
                    // Format Link: interface_a, interface_b, node_a, node_b (UUIDs complets)
                    // Format LinkTopology: i1, i2, n1, n2 (IDs courts)
                    $links = array_map(function($link) {
                        if (is_array($link)) {
                            return [
                                'id' => $link['id'] ?? $link['link_id'] ?? uniqid('link_'),
                                'n1' => $link['n1'] ?? $link['node1'] ?? $link['node_a'] ?? $link['src'] ?? null,
                                'n2' => $link['n2'] ?? $link['node2'] ?? $link['node_b'] ?? $link['dst'] ?? null,
                                'i1' => $link['i1'] ?? $link['interface1'] ?? $link['interface_a'] ?? $link['src_interface'] ?? null,
                                'i2' => $link['i2'] ?? $link['interface2'] ?? $link['interface_b'] ?? $link['dst_interface'] ?? null,
                                'state' => $link['state'] ?? null,
                            ];
                        }
                        return $link;
                    }, $linksData);
            }
            } else {
                // Utiliser les links de la topologie
                $links = $topology['links'] ?? [];
            }
            
            // Approche optimisée : récupérer uniquement les interfaces utilisées dans les liens
            $interfacesMap = [];
            
            // D'abord, collecter tous les IDs d'interfaces utilisés dans les liens
            // Support des deux formats : i1/i2 (topology) et interface_a/interface_b (links)
            $interfaceIdsToFetch = [];
            foreach ($links as $link) {
                if (is_array($link)) {
                    $i1 = $link['i1'] ?? $link['interface_a'] ?? null;
                    $i2 = $link['i2'] ?? $link['interface_b'] ?? null;
                    
                    if (!empty($i1)) {
                        $interfaceIdsToFetch[$i1] = true;
                    }
                    if (!empty($i2)) {
                        $interfaceIdsToFetch[$i2] = true;
                    }
                }
            }
            
            \Log::info('Interfaces à récupérer depuis les liens', [
                'lab_id' => $lab->cml_id,
                'interface_ids_count' => count($interfaceIdsToFetch),
                'interface_ids' => array_slice(array_keys($interfaceIdsToFetch), 0, 10), // Limiter à 10 pour le log
                'sample_link' => is_array($links[0] ?? null) ? [
                    'id' => $links[0]['id'] ?? null,
                    'i1' => $links[0]['i1'] ?? null,
                    'i2' => $links[0]['i2'] ?? null,
                    'interface_a' => $links[0]['interface_a'] ?? null,
                    'interface_b' => $links[0]['interface_b'] ?? null,
                ] : null,
            ]);
            
            // Récupérer les détails de chaque interface utilisée dans les liens
            // LIMITATION : Pour éviter les timeouts, on limite à 20 interfaces maximum
            $interfaceIdsArray = array_keys($interfaceIdsToFetch);
            $maxInterfaces = 20;
            $limitedInterfaceIds = array_slice($interfaceIdsArray, 0, $maxInterfaces);
            
            \Log::info('Récupération des interfaces limitée', [
                'lab_id' => $lab->cml_id,
                'total_interfaces' => count($interfaceIdsArray),
                'fetching_count' => count($limitedInterfaceIds),
                'limited' => count($interfaceIdsArray) > $maxInterfaces,
            ]);
            
            foreach ($limitedInterfaceIds as $interfaceId) {
                try {
                    $interfaceDetails = $cisco->interfaces->getInterface($lab->cml_id, $interfaceId);
                    if (!isset($interfaceDetails['error']) && is_array($interfaceDetails)) {
                        $interfacesMap[$interfaceId] = [
                            'id' => $interfaceId,
                            'label' => $interfaceDetails['label'] ?? null,
                            'type' => $interfaceDetails['type'] ?? null,
                            'is_connected' => $interfaceDetails['is_connected'] ?? false,
                            'state' => $interfaceDetails['state'] ?? null,
                            'mac_address' => $interfaceDetails['mac_address'] ?? null,
                            'node' => $interfaceDetails['node'] ?? null,
                        ];
                    }
                } catch (\Exception $e) {
                    \Log::warning('Erreur lors de la récupération de l\'interface', [
                        'lab_id' => $lab->cml_id,
                        'interface_id' => $interfaceId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            \Log::info('Interfaces récupérées pour les liens', [
                'lab_id' => $lab->cml_id,
                'interfaces_count' => count($interfacesMap),
                'sample_interface_ids' => array_slice(array_keys($interfacesMap), 0, 3),
            ]);
            
            // Fonction pour enrichir un lien avec les informations des interfaces
            $enrichLink = function($link) use ($interfacesMap) {
                if (!is_array($link)) {
                    return $link;
                }
                
                $enriched = $link;
                
                // Support des deux formats : i1/i2 (topology) et interface_a/interface_b (links)
                $i1 = $link['i1'] ?? $link['interface_a'] ?? null;
                $i2 = $link['i2'] ?? $link['interface_b'] ?? null;
                
                // Enrichir l'interface 1
                if (!empty($i1) && isset($interfacesMap[$i1])) {
                    $enriched['interface1'] = $interfacesMap[$i1];
                }
                
                // Enrichir l'interface 2
                if (!empty($i2) && isset($interfacesMap[$i2])) {
                    $enriched['interface2'] = $interfacesMap[$i2];
                }
                
                return $enriched;
            };
            
            // Enrichir les liens
            $links = array_map($enrichLink, $links);
            
            // Enrichir aussi les liens de la topologie si disponibles
            if (isset($topology['links']) && is_array($topology['links'])) {
                $topology['links'] = array_map($enrichLink, $topology['links']);
            }
            
            \Log::info('Données topologie finales', [
                'lab_id' => $lab->cml_id,
                'nodes_count' => count($nodes),
                'links_count' => count($links),
                'interfaces_count' => count($interfacesMap),
                'topology_has_nodes' => isset($topology['nodes']) && is_array($topology['nodes']),
                'topology_has_links' => isset($topology['links']) && is_array($topology['links']),
            ]);
        } else {
            \Log::info('Topologie non chargée', [
                'lab_id' => $lab->cml_id,
                'has_token' => !empty($token),
                'state' => $lab->state,
                'normalized_state' => $normalizedState,
            ]);
        }

        // Récupérer les informations complètes du lab depuis CML pour obtenir owner_username et owner_fullname
        $labInfo = null;
        if ($token) {
            $cisco->setToken($token);
            $labInfo = $cisco->getLab($lab->cml_id);
            if (isset($labInfo['error'])) {
                \Log::warning('Impossible de récupérer les infos complètes du lab', [
                    'lab_id' => $lab->cml_id,
                    'error' => $labInfo['error'],
                ]);
            }
        }

        // Préparer les données du lab avec owner_username et owner_fullname
        $labData = [
            'id' => $lab->cml_id,
            'db_id' => $lab->id,
            'cml_id' => $lab->cml_id,
            'state' => $lab->state,
            'lab_title' => $lab->lab_title,
            'node_count' => $lab->node_count,
            'lab_description' => $lab->lab_description,
            'created' => $lab->created,
            'modified' => $lab->modified,
            'owner' => $lab->owner,
            'link_count' => $lab->link_count,
            'effective_permissions' => $lab->effective_permissions,
            // Ajouter owner_username et owner_fullname depuis l'API CML si disponibles
            'owner_username' => $labInfo['owner_username'] ?? null,
            'owner_fullname' => $labInfo['owner_fullname'] ?? null,
        ];

        return Inertia::render('labs/Workspace', [
            'lab' => $labData,
            'reservation' => $reservation,
            'annotations' => $annotations,
            'nodes' => $nodes,
            'links' => $links,
            'consoleSessions' => $consoleSessions,
            'topology' => $topology,
            'tile' => $tile,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
