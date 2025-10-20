<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\CiscoApiService;
use Illuminate\Support\Facades\Cache;
use App\Models\Lab;
use App\Models\Reservation;
use Illuminate\Support\Facades\Auth;

/**
 * Contrôleur principal pour la gestion des laboratoires réseau
 *
 * Ce contrôleur gère l'affichage, la réservation et l'accès aux laboratoires
 * Cisco Modeling Labs (CML). Il assure la synchronisation avec l'API CML
 * et la gestion des réservations utilisateur.
 */
class LabsController extends Controller
{
    /**
     * Afficher la liste des laboratoires disponibles
     *
     * Cette méthode principale récupère et affiche tous les laboratoires CML disponibles
     * avec support de la pagination, du cache et de la synchronisation automatique.
     *
     * Fonctionnalités :
     * - Vérification de l'authentification CML
     * - Cache des IDs de labs pour 5 minutes
     * - Synchronisation automatique avec la base de données locale
     * - Gestion de la pagination personnalisée
     * - Gestion d'erreurs complète
     *
     * @param \Illuminate\Http\Request $request Requête HTTP avec paramètres de pagination
     * @param \App\Services\CiscoApiService $cisco Service d'API Cisco pour communiquer avec CML
     * @return \Inertia\Response Vue Inertia avec les données des labs ou message d'erreur
     */
    public function index(Request $request, CiscoApiService $cisco)
    {
        // Récupération du token d'authentification CML depuis la session
        $token = session('cml_token');

        // Vérification de l'existence du token CML
        if (!$token) {
            return Inertia::render('labs/Labs', [
                'labs' => [],
                'pagination' => [
                    'page' => 1,
                    'per_page' => 2,
                    'total' => 0,
                    'total_pages' => 0,
                ],
                'error' => 'CML authentication required. Please log in to CML first.',
            ]);
        }

        try {
            // Récupération des IDs de labs depuis le cache ou l'API CML
            $labs_ids = Cache::has('labs_ids') ? Cache::get('labs_ids') : $cisco->getLabs($token);

            // Gestion des erreurs de l'API CML
            if (isset($labs_ids['error'])) {
                return Inertia::render('labs/Labs', [
                    'labs' => [],
                    'pagination' => [
                        'page' => 1,
                        'per_page' => 9,
                        'total' => 0,
                        'total_pages' => 0,
                    ],
                    'error' => 'Unable to fetch labs from CML: ' . $labs_ids['error'],
                ]);
            }

            // Mise en cache des IDs de labs si pas déjà présent (cache de 5 minutes)
            if (!Cache::has('labs_ids')) {
                Cache::put('labs_ids', $labs_ids, now()->addMinutes(5));
            }

            // Récupération et validation des paramètres de pagination
            $page = max(1, (int) $request->query('page', 1));
            $perPage = max(1, (int) $request->query('per_page', 11));
            $total = count($labs_ids);
            $totalPages = ceil($total / $perPage);

            // Extraction des IDs de labs pour la page courante
            $currentLabIds = array_slice($labs_ids, ($page - 1) * $perPage, $perPage);

            // Récupération des détails de chaque lab via l'API CML
            $labs = collect($currentLabIds)->map(function ($id) use ($cisco, $token) {
                $response = $cisco->getlab($token, $id);
                return !isset($response['error']) ? $response : null;
            })->filter()->toArray();

            // Synchronisation automatique avec la base de données locale
            foreach($labs as $key => $lab) {
                $Lab = Lab::where('cml_id', $lab['id'])->first();
                if(!$Lab) {
                    Lab::create([
                        'cml_id' => $lab['id'],
                        'created' => $lab['created'],
                        'modified' => $lab['modified'],
                        'lab_description' => $lab['lab_description'],
                        'node_count' => $lab['node_count'],
                        'state' => $lab['state'],
                        'lab_title' => $lab['lab_title'],
                        'owner' => $lab['owner'],
                        'link_count' => $lab['link_count'],
                        'effective_permissions' => $lab['effective_permissions']
                    ]);
                }
            }

            // Retour de la vue avec les données des labs et pagination
            return Inertia::render('labs/Labs', [
                'labs' => $labs,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => $totalPages,
                ],
            ]);

        } catch (\Exception $e) {
            // Gestion des erreurs inattendues avec redirection et message d'erreur
            return redirect()->back()->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Afficher les laboratoires réservés par l'utilisateur connecté
     *
     * Cette méthode récupère et affiche toutes les réservations actives de l'utilisateur
     * avec des informations en temps réel sur l'état des labs et les créneaux temporels.
     *
     * Fonctionnalités :
     * - Récupération des réservations non annulées et non expirées
     * - Vérification en temps réel de l'état des labs via l'API CML
     * - Calcul des informations temporelles (temps restant, accès possible, etc.)
     * - Synchronisation de l'état des labs en base de données
     * - Formatage des données pour l'interface utilisateur
     *
     * @param \Illuminate\Http\Request $request Requête HTTP (non utilisée actuellement)
     * @param \App\Services\CiscoApiService $cisco Service d'API Cisco pour communiquer avec CML
     * @return \Inertia\Response Vue Inertia avec les réservations ou message d'erreur
     */
    public function myReservedLabs(Request $request, CiscoApiService $cisco)
    {
        // Récupération de l'utilisateur connecté et du token CML
        $user = Auth::user();
        $token = session('cml_token');

        // Vérification de l'authentification CML
        if (!$token) {
            return Inertia::render('labs/MyReservedLabs', [
                'reservedLabs' => [],
                'error' => 'CML authentication required. Please log in to CML first.',
            ]);
        }

        // Récupération des réservations actives de l'utilisateur avec leurs labs associés
        // Exclut les réservations annulées et terminées, triées par date de début décroissante
        $reservations = Reservation::with('lab')
            ->where('user_id', $user->id)
            ->where('status', '!=', 'cancelled')
            ->where('end_at', '>', now()) // Exclure les réservations terminées
            ->orderBy('start_at', 'desc')
            ->get();

        // Transformation des réservations en données enrichies pour l'interface
        $reservedLabs = $reservations->map(function ($reservation) use ($token, $cisco) {
            $lab = $reservation->lab;

            // Variables pour stocker l'état et les informations temporelles
            $currentState = null;
            $timeInfo = null;

            // Vérification de l'état actuel du lab via l'API CML si le lab existe
            if ($lab && $token) {
                $labState = $cisco->getLabState($token, $lab->cml_id);
                if (!isset($labState['error'])) {
                    $currentState = $labState;
                    // Mise à jour de l'état en base de données pour synchronisation
                    $lab->state = $currentState;
                    $lab->save();
                }
            }

            // Calcul des informations temporelles pour la réservation
            $now = now();
            $isActive = $reservation->start_at <= $now && $reservation->end_at > $now;
            $canAccess = $isActive && in_array($currentState, ['DEFINED_ON_CORE', 'STARTED']);

            // Calcul des informations temporelles selon le statut de la réservation
            if ($isActive) {
                // Réservation active - calcul du temps restant
                $timeRemaining = $now->diffInMinutes($reservation->end_at, false);
                $timeInfo = [
                    'status' => 'active',
                    'time_remaining_minutes' => max(0, $timeRemaining),
                    'end_time' => $reservation->end_at->format('H:i'),
                    'can_access' => $canAccess
                ];
            } elseif ($reservation->start_at > $now) {
                // Réservation future - calcul du temps avant le début
                $timeToStart = $now->diffInMinutes($reservation->start_at, false);
                $timeInfo = [
                    'status' => 'pending',
                    'time_to_start_minutes' => max(0, $timeToStart),
                    'start_time' => $reservation->start_at->format('H:i'),
                    'can_access' => false
                ];
            } else {
                // Réservation expirée
                $timeInfo = [
                    'status' => 'expired',
                    'can_access' => false
                ];
            }

            // Retour des données formatées pour l'interface utilisateur
            return [
                'reservation_id' => $reservation->id,
                'lab_id' => $lab->id,
                'cml_id' => $lab->cml_id,
                'lab_title' => $lab->lab_title,
                'lab_description' => $lab->lab_description,
                'node_count' => $lab->node_count,
                'current_state' => $currentState,
                'reservation_start' => $reservation->start_at->format('Y-m-d H:i'),
                'reservation_end' => $reservation->end_at->format('Y-m-d H:i'),
                'duration_hours' => round($reservation->start_at->diffInHours($reservation->end_at), 1),
                'time_info' => $timeInfo,
                'can_access' => $canAccess,
                'status' => $reservation->status,
            ];
        });

        // Retour de la vue avec les données des réservations
        return Inertia::render('labs/MyReservedLabs', [
            'reservedLabs' => $reservedLabs,
        ]);
    }

    /**
     * Accéder à l'espace de travail d'un laboratoire réservé
     *
     * Cette méthode permet à un utilisateur d'accéder à l'interface de travail
     * d'un laboratoire pour lequel il a une réservation active. Elle vérifie
     * les permissions et fournit les données nécessaires à l'interface.
     *
     * Processus :
     * 1. Vérification/synchronisation de l'existence du lab en base locale
     * 2. Récupération de l'état actuel depuis CML
     * 3. Vérification de la réservation active de l'utilisateur
     * 4. Récupération des annotations du lab
     * 5. Affichage de l'espace de travail
     *
     * Sécurité :
     * - Vérification stricte de la réservation active
     * - Contrôle des permissions d'accès
     * - Redirection automatique en cas d'accès non autorisé
     *
     * @param \App\Models\Lab $lab Instance du laboratoire à accéder (Route Model Binding)
     * @param \App\Services\CiscoApiService $cisco Service d'API Cisco pour communiquer avec CML
     * @return \Inertia\Response|\Illuminate\Http\RedirectResponse Vue de l'espace de travail ou redirection
     */
    public function workspace(Lab $lab, CiscoApiService $cisco)
    {
        // Synchronisation du lab avec la base de données locale
        // Si le lab n'existe pas localement, le créer depuis les données CML
        if (!$lab->exists) {
            $response = $cisco->getlab(session('cml_token'), $lab->cml_id);
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

        // Récupération de l'état actuel du lab depuis CML pour synchronisation
        $labState = $cisco->getLabState(session('cml_token'), $lab->cml_id);

        // Mise à jour de l'état en base de données si récupération réussie
        if (!isset($labState['error'])) {
            $lab->state = $labState['state'] ?? $lab->state;
            $lab->save();
        }

        // Recherche d'une réservation active pour l'utilisateur connecté
        $user = Auth::user();
        $reservation = Reservation::where('user_id', $user->id)
            ->where('lab_id', $lab->id)
            ->where('start_at', '<=', now())
            ->where('end_at', '>', now())
            ->where('status', '!=', 'cancelled')
            ->first();

        // Vérification de l'existence d'une réservation active
        if (!$reservation) {
            return redirect()->route('labs')->with('error', 'You do not have an active reservation for this lab.');
        }

        // Récupération des annotations du laboratoire depuis CML
        $annotations = $cisco->getLabsAnnotation(session('cml_token'), $lab->cml_id);

        // Affichage de l'espace de travail avec toutes les données nécessaires
        return Inertia::render('labs/Workspace', [
            'lab' => $lab,
            'reservation' => $reservation,
            'annotations' => $annotations,
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
