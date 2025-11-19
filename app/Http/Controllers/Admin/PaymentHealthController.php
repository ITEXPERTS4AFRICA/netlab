<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CinetPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class PaymentHealthController extends Controller
{
    protected CinetPayService $cinetPayService;

    public function __construct(CinetPayService $cinetPayService)
    {
        $this->cinetPayService = $cinetPayService;
    }

    /**
     * Afficher la page de santé de l'API de paiement
     */
    public function index(): Response
    {
        // Effectuer un check de santé
        $health = $this->cinetPayService->checkHealth();

        // Récupérer les statistiques de paiement récentes
        $recentPayments = \App\Models\Payment::with(['reservation.lab', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                    'created_at' => $payment->created_at->toIso8601String(),
                    'lab_title' => $payment->reservation->lab->lab_title ?? 'N/A',
                    'user_name' => $payment->user->name ?? 'N/A',
                ];
            });

        // Statistiques des paiements
        $paymentStats = [
            'total' => \App\Models\Payment::count(),
            'completed' => \App\Models\Payment::where('status', 'completed')->count(),
            'pending' => \App\Models\Payment::where('status', 'pending')->count(),
            'failed' => \App\Models\Payment::where('status', 'failed')->count(),
            'cancelled' => \App\Models\Payment::where('status', 'cancelled')->count(),
            'last_24h' => \App\Models\Payment::where('created_at', '>=', now()->subDay())->count(),
            'last_7d' => \App\Models\Payment::where('created_at', '>=', now()->subDays(7))->count(),
        ];

        return Inertia::render('admin/payments/health', [
            'health' => $health,
            'recentPayments' => $recentPayments,
            'paymentStats' => $paymentStats,
        ]);
    }

    /**
     * Rafraîchir l'état de santé (AJAX)
     */
    public function refresh(): \Illuminate\Http\JsonResponse
    {
        try {
            $health = $this->cinetPayService->checkHealth();
            
            return response()->json([
                'success' => true,
                'health' => $health,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors du rafraîchissement de la santé de l\'API de paiement', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la vérification de l\'état de santé: ' . $e->getMessage(),
            ], 500);
        }
    }
}

