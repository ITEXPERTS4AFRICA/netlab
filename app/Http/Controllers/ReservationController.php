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
        // Augmenter le timeout pour cette opération (paiement peut prendre du temps)
        set_time_limit(60); // 1 minute
        
        // Log pour déboguer
        \Log::info('Reservation request received', [
            'data' => $request->all(),
            'is_instant' => $request->boolean('instant', false),
            'lab_id' => $request->lab_id,
            'start_at' => $request->start_at,
            'end_at' => $request->end_at,
            'user_id' => Auth::id(),
        ]);

        try {
            $validated = $request->validate([
                'lab_id' => 'required|string', // cml_id (UUID)
                'start_at' => ['required', 'date', function ($attribute, $value, $fail) {
                    try {
                        new \DateTime($value);
                    } catch (\Exception $e) {
                        $fail('Le champ ' . $attribute . ' doit être une date valide.');
                    }
                }],
                'end_at' => ['required', 'date', function ($attribute, $value, $fail) use ($request) {
                    try {
                        $end = new \DateTime($value);
                        $start = new \DateTime($request->start_at);
                        if ($end <= $start) {
                            $fail('La date de fin doit être après la date de début.');
                        }
                    } catch (\Exception $e) {
                        $fail('Le champ ' . $attribute . ' doit être une date valide.');
                    }
                }],
                'rate_id' => 'nullable|exists:rates,id',
                'instant' => 'nullable|boolean', // Réservation instantanée
                'skip_payment' => 'nullable|boolean', // Ignorer le paiement (pour labs gratuits)
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Reservation validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
            ]);
            return response()->json([
                'message' => 'Les données fournies sont invalides.',
                'errors' => $e->errors(),
            ], 422);
        }

        $user = Auth::user();

        // Trouver le lab par cml_id
        $lab = Lab::where('cml_id', $request->lab_id)->first();
        
        if (!$lab) {
            \Log::error('Lab not found for reservation', [
                'lab_id' => $request->lab_id,
                'user_id' => $user->id,
            ]);
            return response()->json(['error' => 'Lab non trouvé'], 404);
        }

        // Vérifier que le lab est publié (si la colonne existe)
        if (Lab::hasColumn('is_published') && !$lab->is_published) {
            return response()->json(['error' => 'Ce lab n\'est pas disponible pour la réservation'], 403);
        }

        try {
            $start = new \DateTime($request->start_at);
            $end = new \DateTime($request->end_at);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Format de date invalide: ' . $e->getMessage()], 422);
        }

        $isInstant = $request->boolean('instant', false);
        $now = new \DateTime();
        
        \Log::info('Reservation timing check', [
            'is_instant' => $isInstant,
            'start_at' => $start->format('Y-m-d H:i:s'),
            'now' => $now->format('Y-m-d H:i:s'),
            'start_diff_seconds' => $start->getTimestamp() - $now->getTimestamp(),
        ]);

        // Pour les réservations instantanées, ajuster start_at à maintenant si nécessaire
        if ($isInstant) {
            // Pour les réservations instantanées, on accepte start_at dans le passé (jusqu'à 5 minutes)
            // ou dans le futur (jusqu'à 15 minutes)
            $fiveMinutesAgo = (clone $now)->modify('-5 minutes');
            $fifteenMinutesFromNow = (clone $now)->modify('+15 minutes');
            
            if ($start < $fiveMinutesAgo) {
                // Si la date est trop dans le passé, ajuster à maintenant
                $start = clone $now;
                \Log::info('Adjusted start_at to now for instant reservation', [
                    'original_start' => $request->start_at,
                    'adjusted_start' => $start->format('Y-m-d H:i:s'),
                ]);
            } elseif ($start > $fifteenMinutesFromNow) {
                return response()->json([
                    'error' => 'Une réservation instantanée doit commencer dans les 15 prochaines minutes',
                    'start_at' => $start->format('Y-m-d H:i:s'),
                    'now' => $now->format('Y-m-d H:i:s'),
                    'max_start' => $fifteenMinutesFromNow->format('Y-m-d H:i:s'),
                ], 422);
            }
        } else {
            // Pour les réservations normales, start_at doit être dans le futur (au moins 1 minute)
            $oneMinuteFromNow = (clone $now)->modify('+1 minute');
            if ($start <= $oneMinuteFromNow) {
                return response()->json(['error' => 'La date de début doit être dans le futur (au moins 1 minute)'], 422);
            }
        }

        // Vérifier s'il existe une réservation en attente pour cet utilisateur et ce créneau
        // Si oui, vérifier le nombre de tentatives échouées
        $existingPendingReservation = Reservation::where('lab_id', $lab->id)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->where('start_at', $start->format('Y-m-d H:i:s'))
            ->where('end_at', $end->format('Y-m-d H:i:s'))
            ->where('created_at', '>', now()->subMinutes(15)) // Pas expirée
            ->first();

        // Si une réservation en attente existe avec 3 échecs ou plus, l'annuler et permettre de recommencer
        if ($existingPendingReservation && $existingPendingReservation->failed_attempts >= 3) {
            \Log::info('Cancelling reservation with 3+ failed attempts', [
                'reservation_id' => $existingPendingReservation->id,
                'failed_attempts' => $existingPendingReservation->failed_attempts,
            ]);
            
            $existingPendingReservation->update([
                'status' => 'cancelled',
                'notes' => trim(($existingPendingReservation->notes ? $existingPendingReservation->notes . PHP_EOL : '') . 
                    'Annulée automatiquement après 3 tentatives échouées le ' . now()->format('Y-m-d H:i:s')),
            ]);
            
            // Continuer pour créer une nouvelle réservation
        } elseif ($existingPendingReservation) {
            // Si une réservation en attente existe avec moins de 3 échecs, incrémenter les tentatives
            $newFailedAttempts = $existingPendingReservation->failed_attempts + 1;
            
            \Log::info('Incrementing failed attempts for existing pending reservation', [
                'reservation_id' => $existingPendingReservation->id,
                'old_failed_attempts' => $existingPendingReservation->failed_attempts,
                'new_failed_attempts' => $newFailedAttempts,
            ]);
            
            $existingPendingReservation->update([
                'failed_attempts' => $newFailedAttempts,
                'notes' => trim(($existingPendingReservation->notes ? $existingPendingReservation->notes . PHP_EOL : '') . 
                    "Tentative {$newFailedAttempts}/3 échouée (créneau déjà réservé) le " . now()->format('Y-m-d H:i:s')),
            ]);
            
            // Retourner la réservation existante avec les informations de retry
            return response()->json([
                'error' => 'Ce créneau horaire est déjà réservé',
                'code' => 'SLOT_ALREADY_RESERVED',
                'can_retry' => $newFailedAttempts < 3,
                'failed_attempts' => $newFailedAttempts,
                'max_attempts' => 3,
                'remaining_attempts' => 3 - $newFailedAttempts,
                'reservation_id' => $existingPendingReservation->id,
                'message' => $newFailedAttempts >= 3 
                    ? 'Maximum de 3 tentatives atteint. La réservation a été annulée. Veuillez choisir un autre créneau.'
                    : "Tentative {$newFailedAttempts}/3 échouée. Il reste " . (3 - $newFailedAttempts) . " tentative(s). Veuillez réessayer avec un autre créneau.",
            ], 422);
        }

        // Vérifier les conflits avec d'autres utilisateurs (exclure les réservations annulées et les réservations pending expirées)
        // Les réservations pending de plus de 15 minutes sont considérées comme expirées
        $conflict = Reservation::where('lab_id', $lab->id)
            ->where('status', '!=', 'cancelled')
            ->where('user_id', '!=', $user->id) // Exclure les réservations de l'utilisateur actuel
            ->where(function($q) {
                // Exclure les réservations pending expirées (plus de 15 minutes)
                $q->where(function($q2) {
                    $q2->where('status', '!=', 'pending')
                       ->orWhere('created_at', '>', now()->subMinutes(15));
                });
            })
            ->where(function($q) use ($start,$end){
                $q->whereBetween('start_at', [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')])
                  ->orWhereBetween('end_at', [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')])
                  ->orWhere(function($q2) use ($start,$end){
                      $q2->where('start_at','<',$start->format('Y-m-d H:i:s'))->where('end_at','>',$end->format('Y-m-d H:i:s'));
                  });
            })->exists();

        if ($conflict) {
            // Si c'est un conflit avec un autre utilisateur, créer une réservation avec failed_attempts = 1
            // ou incrémenter si une réservation existe déjà
            $reservation = Reservation::create([
                'user_id' => $user->id,
                'lab_id' => $lab->id,
                'rate_id' => $request->rate_id,
                'start_at' => $start->format('Y-m-d H:i:s'),
                'end_at' => $end->format('Y-m-d H:i:s'),
                'status' => 'pending',
                'estimated_cents' => $estimatedCents,
                'failed_attempts' => 1,
                'notes' => "Tentative 1/3 échouée (créneau déjà réservé par un autre utilisateur) le " . now()->format('Y-m-d H:i:s'),
            ]);
            
            return response()->json([
                'error' => 'Ce créneau horaire est déjà réservé',
                'code' => 'SLOT_ALREADY_RESERVED',
                'can_retry' => true,
                'failed_attempts' => 1,
                'max_attempts' => 3,
                'remaining_attempts' => 2,
                'reservation_id' => $reservation->id,
                'message' => 'Tentative 1/3 échouée. Il reste 2 tentative(s). Veuillez réessayer avec un autre créneau.',
            ], 422);
        }

        // Calculer le prix
        $estimatedCents = $this->calculateReservationPrice($lab, $start, $end);
        $skipPayment = $request->boolean('skip_payment', false) || $estimatedCents === 0;

        // Log pour déboguer le paiement
        \Log::info('Reservation payment check', [
            'lab_id' => $lab->id,
            'lab_price_cents' => $lab->price_cents,
            'estimated_cents' => $estimatedCents,
            'skip_payment' => $skipPayment,
            'will_initiate_payment' => $estimatedCents > 0 && !$skipPayment,
        ]);

        // Créer la réservation (failed_attempts = 0 car c'est une nouvelle tentative réussie)
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'lab_id' => $lab->id,
            'rate_id' => $request->rate_id,
            'start_at' => $start->format('Y-m-d H:i:s'),
            'end_at' => $end->format('Y-m-d H:i:s'),
            'status' => ($isInstant && $skipPayment) ? 'active' : 'pending',
            'estimated_cents' => $estimatedCents,
            'failed_attempts' => 0,
        ]);

        \Log::info('Reservation created', [
            'reservation_id' => $reservation->id,
            'is_instant' => $isInstant,
            'skip_payment' => $skipPayment,
            'estimated_cents' => $estimatedCents,
            'status' => $reservation->status,
            'will_auto_start' => $isInstant && $skipPayment,
        ]);

        // Si réservation instantanée et gratuite, démarrer le lab automatiquement
        if ($isInstant && $skipPayment) {
            $token = session('cml_token');
            if ($token) {
                $apiService = new CiscoApiService();
                $apiService->setToken($token);
                $labState = $apiService->labs->getLabState($lab->cml_id);

                if (!isset($labState['error'])) {
                    $state = is_array($labState) ? ($labState['state'] ?? null) : null;
                    if ($state === 'STOPPED' || $state === 'DEFINED_ON_CORE') {
                        $startResult = $apiService->labs->startLab($lab->cml_id);
                        \Log::info('Lab auto-started for instant reservation', [
                            'reservation_id' => $reservation->id,
                            'lab_id' => $lab->id,
                            'cml_id' => $lab->cml_id,
                            'start_result' => $startResult,
                        ]);
                    }
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
        // Préparer les données pour CinetPay selon le SDK officiel
        // Le SDK officiel n'utilise PAS les champs customer lors de l'initiation
        // Utiliser le prix réel calculé depuis les métadonnées du lab
        $paymentData = [
            'transaction_id' => 'RES_' . $reservation->id . '_' . Str::random(8),
            'amount' => $reservation->estimated_cents, // Prix réel calculé basé sur le prix du lab et la durée
            'currency' => $lab->currency ?? 'XOF',
            'description' => "Réservation - {$lab->lab_title}" . ($lab->short_description ? " ({$lab->short_description})" : ''),
            'customer_id' => (string) $user->id, // Utilisé comme cpm_custom pour identifier le payeur
        ];

        // Log détaillé pour vérifier que les prix réels sont utilisés
        $reservationStart = new \DateTime($reservation->start_at);
        $reservationEnd = new \DateTime($reservation->end_at);
        $duration = $reservationStart->diff($reservationEnd);
        $durationHours = $duration->h + ($duration->days * 24) + ($duration->i / 60);

        \Log::info('Payment data prepared with real lab prices', [
            'lab_id' => $lab->id,
            'lab_title' => $lab->lab_title,
            'lab_price_cents_per_hour' => $lab->price_cents,
            'reservation_estimated_cents' => $reservation->estimated_cents,
            'currency' => $paymentData['currency'],
            'duration_hours' => $durationHours,
            'start_at' => $reservation->start_at,
            'end_at' => $reservation->end_at,
        ]);

        // Initialiser le paiement avec CinetPay avec gestion de timeout
        try {
            $result = $this->cinetPayService->initiatePayment($paymentData);
        } catch (\Exception $e) {
            // Gérer les exceptions (timeout, erreurs réseau, etc.)
            \Log::error('CinetPay payment initiation exception in ReservationController', [
                'error' => $e->getMessage(),
                'reservation_id' => $reservation->id,
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Vérifier si c'est un timeout
            $isTimeout = strpos($e->getMessage(), 'timeout') !== false || 
                        strpos($e->getMessage(), 'timed out') !== false ||
                        strpos($e->getMessage(), 'Maximum execution time') !== false;
            
            // Si c'est un timeout, retourner 201 (créé) car la réservation existe
            // L'utilisateur pourra réessayer le paiement plus tard
            $reservation->update([
                'status' => 'pending',
                'notes' => 'Paiement non initialisé - ' . ($isTimeout ? 'Timeout' : 'Erreur SDK') . ' - ' . now()->toDateTimeString(),
            ]);
            
            return response()->json([
                'reservation' => $reservation->fresh(),
                'requires_payment' => true,
                'payment_error' => true,
                'error' => $isTimeout 
                    ? 'L\'API de paiement ne répond pas dans les temps. Veuillez réessayer plus tard.'
                    : 'Erreur lors de l\'initialisation du paiement: ' . $e->getMessage(),
                'code' => $isTimeout ? 'TIMEOUT' : 'SDK_ERROR',
                'is_timeout' => $isTimeout,
                'can_retry_payment' => true,
                'retry_payment_url' => "/api/reservations/{$reservation->id}/payments/initiate",
                'message' => 'La réservation a été créée mais le paiement n\'a pas pu être initialisé. Vous pouvez réessayer le paiement depuis la page de vos réservations. Note: La réservation sera automatiquement annulée après 15 minutes si le paiement n\'est pas réussi.',
            ], 201); // 201 Created au lieu de 500
        }

        if (!$result['success']) {
            \Log::error('CinetPay payment initiation failed in ReservationController', [
                'error' => $result['error'] ?? 'Unknown error',
                'code' => $result['code'] ?? 'UNKNOWN',
                'description' => $result['description'] ?? null,
                'is_timeout' => $result['is_timeout'] ?? false,
                'reservation_id' => $reservation->id,
            ]);

            // En cas d'erreur de paiement, la réservation reste créée mais en statut "pending"
            // Elle sera automatiquement annulée après 15 minutes si le paiement n'est pas réussi
            // L'utilisateur pourra réessayer le paiement plus tard via l'endpoint de paiement
            // Marquer la réservation avec un indicateur pour qu'elle soit automatiquement nettoyée
            $reservation->update([
                'status' => 'pending',
                'notes' => 'Paiement non initialisé - ' . ($result['is_timeout'] ? 'Timeout' : 'Erreur') . ' - ' . now()->toDateTimeString(),
            ]);
            
            return response()->json([
                'error' => $result['error'] ?? 'Erreur lors de l\'initialisation du paiement',
                'code' => $result['code'] ?? 'UNKNOWN',
                'description' => $result['description'] ?? null,
                'is_timeout' => $result['is_timeout'] ?? false,
                'reservation' => $reservation->fresh(),
                'can_retry_payment' => true,
                'retry_payment_url' => "/api/reservations/{$reservation->id}/payments/initiate",
                'message' => 'La réservation a été créée mais le paiement n\'a pas pu être initialisé. Vous pouvez réessayer le paiement depuis la page de vos réservations. Note: La réservation sera automatiquement annulée après 15 minutes si le paiement n\'est pas réussi.',
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
            'customer_name' => $user->name ?? 'N/A',
            'customer_surname' => $user->cml_fullname ?? 'N/A',
            'customer_email' => $user->email ?? 'N/A',
            'customer_phone_number' => $user->phone ?? '000000000', // Valeur par défaut si NULL
            'description' => $paymentData['description'],
            'cinetpay_response' => $result['data'],
        ]);

        \Log::info('Reservation payment initiated successfully', [
            'reservation_id' => $reservation->id,
            'payment_id' => $payment->id,
            'payment_url' => $result['payment_url'],
            'transaction_id' => $paymentData['transaction_id'],
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
