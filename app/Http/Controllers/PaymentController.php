<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Reservation;
use App\Services\CinetPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected CinetPayService $cinetPayService;

    public function __construct(CinetPayService $cinetPayService)
    {
        $this->cinetPayService = $cinetPayService;
    }

    /**
     * Initialiser un paiement pour une réservation
     */
    public function initiate(Request $request, Reservation $reservation)
    {
        $user = Auth::user();

        // Vérifier que la réservation appartient à l'utilisateur
        if ($reservation->user_id !== $user->id) {
            return response()->json(['error' => 'Réservation non trouvée'], 404);
        }

        // Vérifier qu'il n'y a pas déjà un paiement réussi
        $existingPayment = $reservation->payments()
            ->where('status', 'completed')
            ->first();

        if ($existingPayment) {
            return response()->json([
                'error' => 'Cette réservation a déjà été payée',
                'payment' => $existingPayment,
            ], 422);
        }

        // Valider les données
        $validator = Validator::make($request->all(), [
            'customer_phone_number' => 'required|string',
            'customer_address' => 'nullable|string',
            'customer_city' => 'nullable|string',
            'customer_country' => 'nullable|string|size:2',
            'customer_state' => 'nullable|string',
            'customer_zip_code' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Calculer le montant (en centimes)
        $amount = $reservation->estimated_cents ?? 0;

        if ($amount <= 0) {
            return response()->json(['error' => 'Le montant de la réservation est invalide'], 422);
        }

        // Préparer les données pour CinetPay
        $paymentData = [
            'amount' => $amount,
            'currency' => 'XOF',
            'description' => "Paiement de réservation - Lab #{$reservation->lab_id}",
            'customer_id' => $user->id,
            'customer_name' => $user->name,
            'customer_surname' => '',
            'customer_email' => $user->email,
            'customer_phone_number' => $request->input('customer_phone_number', $user->phone ?? '+225000000000'),
            'customer_address' => $request->input('customer_address', ''),
            'customer_city' => $request->input('customer_city', ''),
            'customer_country' => $request->input('customer_country', 'CM'),
            'customer_state' => $request->input('customer_state', 'CM'),
            'customer_zip_code' => $request->input('customer_zip_code', ''),
            'metadata' => json_encode([
                'reservation_id' => $reservation->id,
                'user_id' => $user->id,
            ]),
            'lang' => 'FR',
        ];

        // Initialiser le paiement avec CinetPay
        $result = $this->cinetPayService->initiatePayment($paymentData);

        if (!$result['success']) {
            return response()->json([
                'error' => $result['error'] ?? 'Erreur lors de l\'initialisation du paiement',
            ], 500);
        }

        // Créer l'enregistrement de paiement
        $payment = Payment::create([
            'user_id' => $user->id,
            'reservation_id' => $reservation->id,
            'transaction_id' => $result['transaction_id'],
            'cinetpay_transaction_id' => $result['data']['transaction_id'] ?? null,
            'amount' => $amount,
            'currency' => 'XOF',
            'status' => 'pending',
            'customer_name' => $user->name,
            'customer_surname' => '',
            'customer_email' => $user->email,
            'customer_phone_number' => $paymentData['customer_phone_number'],
            'description' => $paymentData['description'],
            'cinetpay_response' => $result['data'],
        ]);

        return response()->json([
            'success' => true,
            'payment' => $payment,
            'payment_url' => $result['payment_url'],
            'transaction_id' => $result['transaction_id'],
        ], 201);
    }

    /**
     * Vérifier le statut d'un paiement
     */
    public function checkStatus(Payment $payment)
    {
        $user = Auth::user();

        // Vérifier que le paiement appartient à l'utilisateur
        if ($payment->user_id !== $user->id) {
            return response()->json(['error' => 'Paiement non trouvé'], 404);
        }

        // Vérifier le statut avec CinetPay
        $status = $this->cinetPayService->checkPaymentStatus($payment->transaction_id);

        if ($status['success']) {
            // Mettre à jour le statut local
            $paymentData = $status['data'];

            if (($status['status'] ?? '') === 'ACCEPTED') {
                $payment->markAsCompleted();

                // Mettre à jour la réservation si nécessaire
                if ($payment->reservation && $payment->reservation->status === 'pending') {
                    $payment->reservation->update(['status' => 'active']);
                }
            } elseif (($status['status'] ?? '') === 'REFUSED') {
                $payment->markAsFailed();
            }

            $payment->update([
                'cinetpay_response' => array_merge($payment->cinetpay_response ?? [], $paymentData),
            ]);
        }

        return response()->json([
            'payment' => $payment->fresh(),
            'status' => $status,
        ]);
    }

    /**
     * Webhook CinetPay pour les notifications de paiement
     */
    public function webhook(Request $request)
    {
        Log::info('CinetPay webhook received', $request->all());

        $data = $request->all();

        // Vérifier la signature (si nécessaire selon la doc CinetPay)
        // $isValid = $this->cinetPayService->verifyWebhookSignature($data);
        // if (!$isValid) {
        //     return response()->json(['error' => 'Signature invalide'], 401);
        // }

        // Trouver le paiement
        $transactionId = $data['cpm_trans_id'] ?? $data['transaction_id'] ?? null;

        if (!$transactionId) {
            Log::warning('CinetPay webhook: transaction_id manquant', $data);
            return response()->json(['error' => 'transaction_id manquant'], 400);
        }

        $payment = Payment::where('transaction_id', $transactionId)
            ->orWhere('cinetpay_transaction_id', $transactionId)
            ->first();

        if (!$payment) {
            Log::warning('CinetPay webhook: paiement non trouvé', ['transaction_id' => $transactionId]);
            return response()->json(['error' => 'Paiement non trouvé'], 404);
        }

        // Mettre à jour le paiement
        $payment->update([
            'webhook_data' => $data,
            'cinetpay_response' => array_merge($payment->cinetpay_response ?? [], $data),
        ]);

        // Traiter le statut
        $status = $data['cpm_result'] ?? $data['status'] ?? null;

        if ($status === '00' || $status === 'ACCEPTED' || ($data['cpm_error_message'] ?? '') === '') {
            $payment->markAsCompleted();
            $payment->update([
                'payment_method' => $data['payment_method'] ?? $data['cpm_payment_method'] ?? null,
            ]);

            // Activer la réservation
            if ($payment->reservation && $payment->reservation->status === 'pending') {
                $payment->reservation->update(['status' => 'active']);
            }

            Log::info('CinetPay webhook: paiement accepté', ['payment_id' => $payment->id]);
        } else {
            $payment->markAsFailed();
            Log::warning('CinetPay webhook: paiement refusé', [
                'payment_id' => $payment->id,
                'status' => $status,
                'error' => $data['cpm_error_message'] ?? 'Unknown error',
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Page de retour après paiement (succès)
     */
    public function return(Request $request)
    {
        $transactionId = $request->input('transaction_id') ?? $request->input('cpm_trans_id');

        if (!$transactionId) {
            return redirect()->route('dashboard')->with('error', 'Transaction ID manquant');
        }

        $payment = Payment::where('transaction_id', $transactionId)
            ->orWhere('cinetpay_transaction_id', $transactionId)
            ->first();

        if (!$payment) {
            return redirect()->route('dashboard')->with('error', 'Paiement non trouvé');
        }

        // Vérifier le statut avec CinetPay
        $this->checkStatus($payment);

        if ($payment->isCompleted()) {
            return redirect()->route('reservations.show', $payment->reservation_id)
                ->with('success', 'Paiement effectué avec succès');
        }

        return redirect()->route('reservations.show', $payment->reservation_id)
            ->with('error', 'Le paiement n\'a pas pu être validé');
    }

    /**
     * Page de retour après annulation
     */
    public function cancel(Request $request)
    {
        $transactionId = $request->input('transaction_id') ?? $request->input('cpm_trans_id');

        if ($transactionId) {
            $payment = Payment::where('transaction_id', $transactionId)
                ->orWhere('cinetpay_transaction_id', $transactionId)
                ->first();

            if ($payment && $payment->isPending()) {
                $payment->update(['status' => 'cancelled']);
            }
        }

        return redirect()->route('dashboard')
            ->with('info', 'Paiement annulé');
    }

    /**
     * Liste des paiements de l'utilisateur
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $payments = Payment::where('user_id', $user->id)
            ->with(['reservation.lab', 'reservation.rate'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($payments);
    }

    /**
     * Détails d'un paiement
     */
    public function show(Payment $payment)
    {
        $user = Auth::user();

        if ($payment->user_id !== $user->id) {
            return response()->json(['error' => 'Paiement non trouvé'], 404);
        }

        return response()->json($payment->load(['reservation.lab', 'reservation.rate']));
    }
}
