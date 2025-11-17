<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\CiscoApiService;
use Illuminate\Support\Facades\Cache;
use App\Models\Lab;
use App\Models\Reservation;
use Illuminate\Support\Facades\Auth;

class LabsController extends Controller
{
    /**
     * Display a listing of the resource.
     * Affiche uniquement les labs publiés (is_published = true)
     */
    public function index(Request $request, CiscoApiService $cisco)
    {
        try {
            // Récupérer uniquement les labs publiés depuis la base de données
            $query = Lab::where('is_published', true)
                ->withCount(['reservations' => function ($q) {
                    $q->where('status', 'active')
                      ->where('end_at', '>', now());
                }])
                ->orderBy('is_featured', 'desc') // Labs en avant en premier
                ->orderBy('created_at', 'desc');

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
                    'link_count' => $lab->link_count ?? 0,
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
        $token = session('cml_token');

        if (!$token) {
            return Inertia::render('labs/MyReservedLabs', [
                'reservedLabs' => [],
                'error' => 'CML authentication required. Please log in to CML first.',
            ]);
        }

        // Récupérer les réservations de l'utilisateur avec leurs labs (exclure les terminées)
        $reservations = Reservation::with('lab')
            ->where('user_id', $user->id)
            ->where('status', '!=', 'cancelled')
            ->where('end_at', '>', now()) // Exclure les réservations terminées
            ->orderBy('start_at', 'desc')
            ->get();

        $reservedLabs = $reservations->map(function ($reservation) use ($token,$cisco) {
            $lab = $reservation->lab;

            // Vérifier l'état actuel du lab via CML
            $currentState = null;
            $timeInfo = null;

            if ($lab && $token) {
                $labState = $cisco->getLabState($token, $lab->cml_id);
                if (!isset($labState['error'])) {
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
        $token = session('cml_token');

        // Ensure the lab exists in our database, create if not
        if (!$lab->exists) {
            $response = $cisco->getlab($token, $lab->cml_id);
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

        // Get current lab state from CML
        $labState = $cisco->getLabState($token, $lab->cml_id);
        if (!isset($labState['error'])) {
            $lab->state = $labState['state'] ?? $lab->state;
            $lab->save();
        }

        // Get active reservation for the user
        $user = Auth::user();
        $reservation = Reservation::where('user_id', $user->id)
            ->where('lab_id', $lab->id)
            ->where('start_at', '<=', now())
            ->where('end_at', '>', now())
            ->where('status', '!=', 'cancelled')
            ->first();

        // Check if user has active reservation
        if (!$reservation) {
            return redirect()->route('labs')->with('error', 'You do not have an active reservation for this lab.');
        }

        // Get annotations for the lab
        $annotations = $cisco->getLabsAnnotation($token, $lab->cml_id);

        // Fetch lab nodes for console management
        $nodes = $cisco->getLabNodes($token, $lab->cml_id);
        if (isset($nodes['error'])) {
            $nodes = [];
        }

        // Fetch active console sessions (best effort)
        $consoleSessions = $cisco->console->getConsoleSessions();
        if (isset($consoleSessions['error'])) {
            $consoleSessions = [];
        }

        return Inertia::render('labs/Workspace', [
            'lab' => $lab,
            'reservation' => $reservation,
            'annotations' => $annotations,
            'nodes' => $nodes,
            'consoleSessions' => $consoleSessions,
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
