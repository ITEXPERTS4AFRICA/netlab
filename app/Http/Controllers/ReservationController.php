<?php

namespace App\Http\Controllers;

use App\Models\Lab;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use App\Services\CiscoApiService;
use App\Services\Annotation\LabAnnotationService;

class ReservationController extends Controller
{
    use AuthorizesRequests;
    public function index(Request $request)
    {
        $user = Auth::user();
        $reservations = Reservation::with('lab','rate')
            ->when(! $request->query('all'), fn($q) => $q->where('user_id', $user->id))
            ->orderBy('start_at','desc')
            ->paginate(20);

        return response()->json($reservations);
    }

    public function store(Request $request)
    {
        $request->validate([
            'lab_id' => 'required|exists:labs,cml_id',
            'start_at' => 'required|date|after:now',
            'end_at' => 'required|date|after:start_at',
            'rate_id' => 'nullable|exists:rates,id',
        ]);

        $user = Auth::user();
        $lab = \App\Models\Lab::where('cml_id', $request->lab_id)->firstOrFail();

        // check overlap
        $start = new \DateTime($request->start_at);
        $end = new \DateTime($request->end_at);

        $conflict = Reservation::where('lab_id', $lab->id)
            ->where('status', '!=', 'cancelled')
            ->where(function($q) use ($start,$end){
                $q->whereBetween('start_at', [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')])
                  ->orWhereBetween('end_at', [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')])
                  ->orWhere(function($q2) use ($start,$end){
                      $q2->where('start_at','<',$start->format('Y-m-d H:i:s'))->where('end_at','>',$end->format('Y-m-d H:i:s'));
                  });
            })->exists();

        if ($conflict) {
            return response()->json(['error' => 'Requested time slot conflicts with existing reservation'], 422);
        }

        $reservation = Reservation::create([
            'user_id' => $user->id,
            'lab_id' => $lab->id,
            'rate_id' => $request->rate_id,
            'start_at' => $request->start_at,
            'end_at' => $request->end_at,
            'status' => 'pending',
        ]);

        return response()->json($reservation, 201);
    }

    public function show(Reservation $reservation)
    {
        $this->authorize('view', $reservation);
        return response()->json($reservation->load('lab','rate','usageRecord'));
    }

    public function destroy(Reservation $reservation)
    {
        $this->authorize('delete', $reservation);
        $reservation->delete();
        return response()->json(null,204);
    }

    public function createReservation(Request $request)
    {
        $request->validate([
            'lab_id' => 'required|exists:labs,cml_id',
            'start_at' => 'required|date|after:now',
            'end_at' => 'required|date|after:start_at',
        ]);

        $user = Auth::user();
        $lab = Lab::where('cml_id', $request->lab_id)->firstOrFail();

        // 🏗️ Exploiter CiscoApiService pour vérifier l'état réel du lab
        $apiService = new CiscoApiService();
        $token = session('cml_token');

        if (!$token) {
            return back()->withErrors(['error' => 'Unable to connect to CML service. Please authenticate.']);
        }

        // Vérifier l'état réel du lab via l'API CML
        $labStatus = $apiService->getLabState($token, $lab->cml_id);

        if (isset($labStatus['error'])) {
            return back()->withErrors(['error' => 'Unable to verify lab status: ' . $labStatus['error']]);
        }

        $actualLabState = $labStatus['state'] ?? $lab->state;
        $needsAutoStart = false;

        // Si le lab est arrêté et que l'utilisateur essaie de le réserver maintenant (+-15 min)
        $reservationStart = new \DateTime($request->start_at);
        $now = new \DateTime();
        $fifteenMinutesFromNow = new \DateTime('+15 minutes');

        if ($actualLabState === 'STOPPED' && $reservationStart >= $now && $reservationStart <= $fifteenMinutesFromNow) {
            $needsAutoStart = true;
        }

        // Vérifier les conflits de réservation existants
        $start = new \DateTime($request->start_at);
        $end = new \DateTime($request->end_at);

        $conflict = Reservation::where('lab_id', $lab->id)
            ->where('status', '!=', 'cancelled')
            ->where(function($q) use ($start, $end){
                $q->whereBetween('start_at', [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')])
                  ->orWhereBetween('end_at', [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')])
                  ->orWhere(function($q2) use ($start, $end){
                      $q2->where('start_at','<',$start->format('Y-m-d H:i:s'))->where('end_at','>',$end->format('Y-m-d H:i:s'));
                  });
            })->exists();

        if ($conflict) {
            return back()->withErrors(['time_slot' => 'Requested time slot conflicts with existing reservation']);
        }

        // 🚀 Démarrer automatiquement le lab si nécessaire
        if ($needsAutoStart) {
            $startResult = $apiService->startLab($token, $lab->cml_id);

            if (isset($startResult['error'])) {
                return back()->withErrors(['error' => 'Unable to start lab automatically: ' . $startResult['error']]);
            }

            // Attendre un peu que le lab démarre (optionnel)
            sleep(2);
        }

        // 📝 Exploiter LabAnnotationService pour enrichir l'expérience
        $annotationService = new LabAnnotationService();
        $annotations = $annotationService->getLabsAnnotation($token, $lab->cml_id);

        // Créer la réservation
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'lab_id' => $lab->id,
            'start_at' => $request->start_at,
            'end_at' => $request->end_at,
            'status' => $needsAutoStart ? 'active' : 'pending',
            'auto_started' => $needsAutoStart,
            'annotations_count' => is_array($annotations) ? count($annotations) : 0,
        ]);

        $message = $needsAutoStart
            ? 'Reservation created and lab started automatically!'
            : 'Reservation created successfully!';

        return redirect()->route('labs.workspace', ['lab' => $lab->id])->with('success', $message);
    }
}
