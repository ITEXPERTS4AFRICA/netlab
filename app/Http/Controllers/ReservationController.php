<?php

namespace App\Http\Controllers;

use App\Models\Lab;
use App\Models\Reservation;
use App\Models\Payment;
use App\Services\CinetPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use App\Services\CiscoApiService;
use Illuminate\Support\Str;


class ReservationController extends Controller
{
    use AuthorizesRequests;

    protected CinetPayService $cinetPayService;

    public function __construct(CinetPayService $cinetPayService)
    {
        $this->cinetPayService = $cinetPayService;
    }

    /**
     * Calculer le prix d'une réservation basé sur le prix du lab et la durée
     */
    protected function calculateReservationPrice(Lab $lab, \DateTime $startAt, \DateTime $endAt): int
    {
        // Si le lab a un prix fixe, l'utiliser
        if ($lab->price_cents && $lab->price_cents > 0) {
            // Calculer la durée totale en heures (avec minutes)
            $diff = $startAt->diff($endAt);
            $totalHours = $diff->h + ($diff->days * 24) + ($diff->i / 60);
            
            // Prix par heure (le prix du lab est considéré comme prix par heure)
            $pricePerHour = $lab->price_cents;
            
            // Arrondir à l'heure supérieure et multiplier
            $hours = max(1, ceil($totalHours));
            
            return $hours * $pricePerHour;
        }

        // Sinon, retourner 0 (gratuit)
        return 0;
    }
    public function index(Request $request)
    {
        $user = Auth::user();
        $reservations = Reservation::with('lab','rate')
            ->when(! $request->query('all'), fn($q) => $q->where('user_id', $user->id))
            ->orderBy('start_at','desc')
            ->paginate(20);

        return response()->json($reservations);
    }

    public function store(Request $request, $lab = null)
    {
        $request->validate([
            'lab_id' => 'required|string', // cml_id (UUID)
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
            'rate_id' => 'nullable|exists:rates,id',
            'instant' => 'nullable|boolean', // Réservation instantanée
            'skip_payment' => 'nullable|boolean', // Ignorer le paiement (pour labs gratuits)
        ]);

        $user = Auth::user();
        
        // Trouver le lab par cml_id
        $lab = Lab::where('cml_id', $request->lab_id)->firstOrFail();

        // Vérifier que le lab est publié
        if (!$lab->is_published) {
            return response()->json(['error' => 'Ce lab n\'est pas disponible pour la réservation'], 403);
        }

        $start = new \DateTime($request->start_at);
        $end = new \DateTime($request->end_at);
        $isInstant = $request->boolean('instant', false);

        // Pour les réservations instantanées, vérifier que le start_at est maintenant ou dans les 15 prochaines minutes
        if ($isInstant) {
            $now = new \DateTime();
            $fifteenMinutesFromNow = (clone $now)->modify('+15 minutes');
            
            if ($start < $now) {
                $start = $now; // Ajuster au moment présent
            }
            
            if ($start > $fifteenMinutesFromNow) {
                return response()->json(['error' => 'Une réservation instantanée doit commencer dans les 15 prochaines minutes'], 422);
            }
        } else {
            // Pour les réservations normales, start_at doit être dans le futur
            if ($start <= new \DateTime()) {
                return response()->json(['error' => 'La date de début doit être dans le futur'], 422);
            }
        }

        // Vérifier les conflits
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
            return response()->json(['error' => 'Ce créneau horaire est déjà réservé'], 422);
        }

        // Calculer le prix
        $estimatedCents = $this->calculateReservationPrice($lab, $start, $end);
        $skipPayment = $request->boolean('skip_payment', false) || $estimatedCents === 0;

        // Créer la réservation
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'lab_id' => $lab->id,
            'rate_id' => $request->rate_id,
            'start_at' => $start->format('Y-m-d H:i:s'),
            'end_at' => $end->format('Y-m-d H:i:s'),
            'status' => ($isInstant && $skipPayment) ? 'active' : 'pending',
            'estimated_cents' => $estimatedCents,
        ]);

        // Si réservation instantanée et gratuite, démarrer le lab automatiquement
        if ($isInstant && $skipPayment) {
            $token = session('cml_token');
            if ($token) {
                $apiService = new CiscoApiService();
                $apiService->setToken($token);
                $labState = $apiService->getLabState($token, $lab->cml_id);
                
                if (!isset($labState['error']) && ($labState['state'] ?? 'STOPPED') === 'STOPPED') {
                    $apiService->startLab($token, $lab->cml_id);
                }
            }
        }

        // Si le lab a un prix et que le paiement n'est pas ignoré, initier le paiement
        if ($estimatedCents > 0 && !$skipPayment) {
            return $this->initiateReservationPayment($request, $reservation, $user, $lab);
        }

        return response()->json([
            'reservation' => $reservation->load('lab'),
            'message' => $isInstant ? 'Réservation instantanée créée avec succès' : 'Réservation créée avec succès',
            'requires_payment' => false,
        ], 201);
    }

    /**
     * Initier le paiement pour une réservation
     */
    protected function initiateReservationPayment(Request $request, Reservation $reservation, $user, Lab $lab)
    {
        // Préparer les données pour CinetPay
        $paymentData = [
            'transaction_id' => 'RES_' . $reservation->id . '_' . Str::random(8),
            'amount' => $reservation->estimated_cents,
            'currency' => $lab->currency ?? 'XOF',
            'description' => "Réservation - {$lab->lab_title}",
            'customer_id' => $user->id,
            'customer_name' => $user->name,
            'customer_surname' => $user->cml_fullname ?? '',
            'customer_email' => $user->email,
            'customer_phone_number' => $request->input('customer_phone_number', $user->phone ?? '+225000000000'),
            'customer_address' => $request->input('customer_address', $user->organization ?? ''),
            'customer_city' => $request->input('customer_city', ''),
            'customer_country' => $request->input('customer_country', 'CM'),
            'customer_state' => $request->input('customer_state', 'CM'),
            'customer_zip_code' => $request->input('customer_zip_code', ''),
            'metadata' => json_encode([
                'reservation_id' => $reservation->id,
                'user_id' => $user->id,
                'lab_id' => $lab->id,
            ]),
            'lang' => 'FR',
        ];

        // Initialiser le paiement avec CinetPay
        $result = $this->cinetPayService->initiatePayment($paymentData);

        if (!$result['success']) {
            return response()->json([
                'error' => $result['error'] ?? 'Erreur lors de l\'initialisation du paiement',
                'reservation' => $reservation,
            ], 500);
        }

        // Créer l'enregistrement de paiement
        $payment = Payment::create([
            'user_id' => $user->id,
            'reservation_id' => $reservation->id,
            'transaction_id' => $paymentData['transaction_id'],
            'cinetpay_transaction_id' => $result['data']['transaction_id'] ?? null,
            'amount' => $reservation->estimated_cents,
            'currency' => $paymentData['currency'],
            'status' => 'pending',
            'customer_name' => $paymentData['customer_name'],
            'customer_surname' => $paymentData['customer_surname'],
            'customer_email' => $paymentData['customer_email'],
            'customer_phone_number' => $paymentData['customer_phone_number'],
            'description' => $paymentData['description'],
            'cinetpay_response' => $result['data'],
        ]);

        return response()->json([
            'reservation' => $reservation->load('lab'),
            'payment' => $payment,
            'payment_url' => $result['payment_url'],
            'requires_payment' => true,
            'message' => 'Réservation créée. Veuillez compléter le paiement.',
        ], 201);
    }

    public function show(Reservation $reservation)
    {
        $this->authorize('view', $reservation);
        return response()->json($reservation->load('lab','rate','usageRecord'));
    }

    public function active($labId)
    {
        $user = Auth::user();
        $lab = Lab::findOrFail($labId);

        $reservation = Reservation::where('user_id', $user->id)
            ->where('lab_id', $lab->id)
            ->where('start_at', '<=', now())
            ->where('end_at', '>', now())
            ->where('status', '!=', 'cancelled')
            ->first();

        return response()->json($reservation);
    }

    public function destroy(Reservation $reservation)
    {
        $this->authorize('delete', $reservation);
        $reservation->delete();
        return response()->json(null,204);
    }

    public function createReservation(Request $request , CiscoApiService $annotationService)
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
        

        if ($needsAutoStart) {
            $reservation->update(['status' => 'active']);
        }


        $message = $needsAutoStart
            ? 'Reservation created and lab started automatically!'
            : 'Reservation created successfully!';

        return redirect()->route('labs.workspace', ['lab' => $lab->id])->with('success', $message);
    }
}
