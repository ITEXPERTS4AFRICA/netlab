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
     */
    public function index(Request $request, CiscoApiService $cisco)
    {
        $token = session('cml_token');

        // Check if CML token exists
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

            $labs_ids = Cache::has('labs_ids') ? Cache::get('labs_ids') : $cisco->getLabs($token);


            // Handle API errors

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

            if (!Cache::has('labs_ids')) {
                Cache::put('labs_ids', $labs_ids, now()->addMinutes(5));
            }

            $page = max(1, (int) $request->query('page', 1));
            $perPage = max(1, (int) $request->query('per_page', 11));
            $total = count($labs_ids);
            $totalPages = ceil($total / $perPage);

            $currentLabIds = array_slice($labs_ids, ($page - 1) * $perPage, $perPage);

            $labs = collect($currentLabIds)->map(function ($id) use ($cisco, $token) {
                $response = $cisco->getlab($token, $id);
                return !isset($response['error']) ? $response : null;
            })->filter()->toArray();

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
            // Handle any unexpected errors
            return redirect()->back()->with('error', 'An error occurred: ' . $e->getMessage());
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
                    $currentState = $labState;
                    // Mettre à jour l'état dans la base de données
                    $lab->state = $currentState;
                    $lab->save();
                }
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

        return Inertia::render('labs/MyReservedLabs', [
            'reservedLabs' => $reservedLabs,
        ]);
    }

    public function workspace(Lab $lab, CiscoApiService $cisco)
    {
        // Ensure the lab exists in our database, create if not
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

        // Get current lab state from CML
        $labState = $cisco->getLabState(session('cml_token'), $lab->cml_id);


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
        $annotations = $cisco->getLabsAnnotation(session('cml_token'), $lab->cml_id);

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
