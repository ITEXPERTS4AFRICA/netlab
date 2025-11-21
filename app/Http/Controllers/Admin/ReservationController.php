<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Lab;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Services\ReservationCleanupService;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $query = Reservation::with(['lab', 'user', 'payments', 'usageRecord', 'rate'])
            ->orderBy('created_at', 'desc');

        // Filtres
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('lab_id') && $request->lab_id) {
            $query->where('lab_id', $request->lab_id);
        }

        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        // Recherche par date
        if ($request->has('date_from')) {
            $query->where('start_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('end_at', '<=', $request->date_to);
        }

        $reservations = $query->paginate(50)->appends($request->query());

        // Statistiques globales
        $stats = [
            'total' => Reservation::count(),
            'active' => Reservation::where('status', 'active')
                ->where('end_at', '>', now())
                ->count(),
            'pending' => Reservation::where('status', 'pending')
                ->where('created_at', '>', now()->subMinutes(15))
                ->count(),
            'pending_expired' => Reservation::where('status', 'pending')
                ->where('created_at', '<=', now()->subMinutes(15))
                ->count(),
            'completed' => Reservation::where('status', 'completed')->count(),
            'cancelled' => Reservation::where('status', 'cancelled')->count(),
            'total_revenue_cents' => Reservation::whereHas('payments', function($q) {
                    $q->where('status', 'completed');
                })
                ->sum('estimated_cents'),
            'today_reservations' => Reservation::whereDate('created_at', today())->count(),
            'week_reservations' => Reservation::where('created_at', '>=', now()->subWeek())->count(),
            'month_reservations' => Reservation::where('created_at', '>=', now()->subMonth())->count(),
        ];

        // Statistiques par statut
        $statusStats = Reservation::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Statistiques par lab
        $labStats = Reservation::select('lab_id', DB::raw('count(*) as count'))
            ->with('lab:id,lab_title')
            ->groupBy('lab_id')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->map(function($item) {
                return [
                    'lab_id' => $item->lab_id,
                    'lab_title' => $item->lab->lab_title ?? 'Unknown',
                    'count' => $item->count,
                ];
            });

        // Statistiques de paiement
        $paymentStats = [
            'total_paid' => Payment::where('status', 'completed')->count(),
            'total_pending' => Payment::where('status', 'pending')->count(),
            'total_failed' => Payment::where('status', 'failed')->count(),
            'total_revenue_cents' => Payment::where('status', 'completed')->sum('amount'),
        ];

        // Réservations actives avec détails
        $activeReservations = Reservation::with(['lab', 'user', 'payments'])
            ->where('status', 'active')
            ->where('end_at', '>', now())
            ->orderBy('start_at', 'asc')
            ->get()
            ->map(function($reservation) {
                return [
                    'id' => $reservation->id,
                    'lab_title' => $reservation->lab->lab_title ?? 'Unknown',
                    'user_name' => $reservation->user->name ?? 'Unknown',
                    'user_email' => $reservation->user->email ?? 'Unknown',
                    'start_at' => $reservation->start_at?->format('Y-m-d H:i:s'),
                    'end_at' => $reservation->end_at?->format('Y-m-d H:i:s'),
                    'estimated_cents' => $reservation->estimated_cents,
                    'has_payment' => $reservation->payments->where('status', 'completed')->isNotEmpty(),
                    'duration_hours' => $reservation->start_at && $reservation->end_at
                        ? round($reservation->start_at->diffInHours($reservation->end_at), 2)
                        : null,
                    'time_remaining_minutes' => $reservation->end_at
                        ? max(0, now()->diffInMinutes($reservation->end_at, false))
                        : null,
                ];
            });

        // Réservations pending expirées (à nettoyer)
        $expiredPendingReservations = Reservation::with(['lab', 'user'])
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subMinutes(15))
            ->where('estimated_cents', '>', 0) // Seulement celles qui nécessitent un paiement
            ->whereDoesntHave('payments', function($q) {
                $q->where('status', 'completed');
            })
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function($reservation) {
                return [
                    'id' => $reservation->id,
                    'lab_title' => $reservation->lab->lab_title ?? 'Unknown',
                    'user_name' => $reservation->user->name ?? 'Unknown',
                    'created_at' => $reservation->created_at->format('Y-m-d H:i:s'),
                    'estimated_cents' => $reservation->estimated_cents,
                    'expired_minutes_ago' => now()->diffInMinutes($reservation->created_at),
                ];
            });

        return Inertia::render('admin/reservations/index', [
            'reservations' => $reservations->map(function($reservation) {
                $payments = $reservation->payments->map(function($payment) {
                    return [
                        'id' => $payment->id,
                        'transaction_id' => $payment->transaction_id,
                        'cinetpay_transaction_id' => $payment->cinetpay_transaction_id,
                        'amount' => $payment->amount,
                        'currency' => $payment->currency,
                        'status' => $payment->status,
                        'payment_method' => $payment->payment_method,
                        'customer_name' => $payment->customer_name,
                        'customer_email' => $payment->customer_email,
                        'customer_phone_number' => $payment->customer_phone_number,
                        'description' => $payment->description,
                        'paid_at' => $payment->paid_at?->format('Y-m-d H:i:s'),
                        'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $payment->updated_at->format('Y-m-d H:i:s'),
                    ];
                });

                $usageRecord = $reservation->usageRecord ? [
                    'id' => $reservation->usageRecord->id,
                    'started_at' => $reservation->usageRecord->started_at?->format('Y-m-d H:i:s'),
                    'ended_at' => $reservation->usageRecord->ended_at?->format('Y-m-d H:i:s'),
                    'duration_seconds' => $reservation->usageRecord->duration_seconds,
                    'cost_cents' => $reservation->usageRecord->cost_cents,
                ] : null;

                return [
                    'id' => $reservation->id,
                    'lab_title' => $reservation->lab->lab_title ?? 'Unknown',
                    'lab_id' => $reservation->lab_id,
                    'lab_cml_id' => $reservation->lab->cml_id ?? null,
                    'user_name' => $reservation->user->name ?? 'Unknown',
                    'user_email' => $reservation->user->email ?? 'Unknown',
                    'user_id' => $reservation->user_id,
                    'rate_id' => $reservation->rate_id,
                    'start_at' => $reservation->start_at?->format('Y-m-d H:i:s'),
                    'end_at' => $reservation->end_at?->format('Y-m-d H:i:s'),
                    'status' => $reservation->status,
                    'estimated_cents' => $reservation->estimated_cents,
                    'notes' => $reservation->notes,
                    'has_payment' => $reservation->payments->where('status', 'completed')->isNotEmpty(),
                    'payment_status' => $reservation->payments->first()?->status ?? 'none',
                    'payments' => $payments,
                    'usage_record' => $usageRecord,
                    'created_at' => $reservation->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $reservation->updated_at->format('Y-m-d H:i:s'),
                    'duration_hours' => $reservation->start_at && $reservation->end_at
                        ? round($reservation->start_at->diffInHours($reservation->end_at), 2)
                        : null,
                ];
            }),
            'pagination' => [
                'current_page' => $reservations->currentPage(),
                'last_page' => $reservations->lastPage(),
                'per_page' => $reservations->perPage(),
                'total' => $reservations->total(),
            ],
            'stats' => $stats,
            'statusStats' => $statusStats,
            'labStats' => $labStats,
            'paymentStats' => $paymentStats,
            'activeReservations' => $activeReservations,
            'expiredPendingReservations' => $expiredPendingReservations,
            'filters' => $request->only(['status', 'lab_id', 'user_id', 'date_from', 'date_to']),
            'labs' => Lab::select('id', 'lab_title')->orderBy('lab_title')->get(),
            'users' => User::select('id', 'name', 'email')->orderBy('name')->get(),
        ]);
    }

    public function show(Reservation $reservation)
    {
        $reservation->load(['lab', 'user', 'rate', 'payments', 'usageRecord']);

        $payments = $reservation->payments->map(function($payment) {
            return [
                'id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'cinetpay_transaction_id' => $payment->cinetpay_transaction_id,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'status' => $payment->status,
                'payment_method' => $payment->payment_method,
                'customer_name' => $payment->customer_name,
                'customer_surname' => $payment->customer_surname,
                'customer_email' => $payment->customer_email,
                'customer_phone_number' => $payment->customer_phone_number,
                'description' => $payment->description,
                'cinetpay_response' => $payment->cinetpay_response,
                'webhook_data' => $payment->webhook_data,
                'paid_at' => $payment->paid_at?->format('Y-m-d H:i:s'),
                'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $payment->updated_at->format('Y-m-d H:i:s'),
            ];
        });

        $usageRecord = $reservation->usageRecord ? [
            'id' => $reservation->usageRecord->id,
            'started_at' => $reservation->usageRecord->started_at?->format('Y-m-d H:i:s'),
            'ended_at' => $reservation->usageRecord->ended_at?->format('Y-m-d H:i:s'),
            'duration_seconds' => $reservation->usageRecord->duration_seconds,
            'cost_cents' => $reservation->usageRecord->cost_cents,
            'duration_hours' => $reservation->usageRecord->duration_seconds 
                ? round($reservation->usageRecord->duration_seconds / 3600, 2) 
                : null,
        ] : null;

        $lab = [
            'id' => $reservation->lab->id,
            'cml_id' => $reservation->lab->cml_id,
            'lab_title' => $reservation->lab->lab_title,
            'lab_description' => $reservation->lab->lab_description,
            'short_description' => $reservation->lab->short_description,
            'state' => $reservation->lab->state,
            'price_cents' => $reservation->lab->price_cents ?? null,
            'currency' => $reservation->lab->currency ?? 'XOF',
            'is_published' => Lab::hasColumn('is_published') ? ($reservation->lab->is_published ?? false) : true,
        ];

        $user = [
            'id' => $reservation->user->id,
            'name' => $reservation->user->name,
            'email' => $reservation->user->email,
            'phone' => $reservation->user->phone ?? null,
        ];

        $rate = $reservation->rate ? [
            'id' => $reservation->rate->id,
            'name' => $reservation->rate->name ?? null,
        ] : null;

        // Calculer le temps restant si la réservation est active
        $timeRemaining = null;
        if ($reservation->status === 'active' && $reservation->end_at) {
            $now = now();
            $end = $reservation->end_at;
            if ($end > $now) {
                $timeRemaining = [
                    'minutes' => $now->diffInMinutes($end),
                    'hours' => round($now->diffInHours($end), 2),
                    'is_expired' => false,
                ];
            } else {
                $timeRemaining = [
                    'minutes' => 0,
                    'hours' => 0,
                    'is_expired' => true,
                ];
            }
        }

        return Inertia::render('admin/reservations/show', [
            'reservation' => [
                'id' => $reservation->id,
                'lab' => $lab,
                'user' => $user,
                'rate' => $rate,
                'start_at' => $reservation->start_at?->format('Y-m-d H:i:s'),
                'end_at' => $reservation->end_at?->format('Y-m-d H:i:s'),
                'status' => $reservation->status,
                'estimated_cents' => $reservation->estimated_cents,
                'notes' => $reservation->notes,
                'payments' => $payments,
                'usage_record' => $usageRecord,
                'time_remaining' => $timeRemaining,
                'created_at' => $reservation->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $reservation->updated_at->format('Y-m-d H:i:s'),
                'duration_hours' => $reservation->start_at && $reservation->end_at
                    ? round($reservation->start_at->diffInHours($reservation->end_at), 2)
                    : null,
            ],
        ]);
    }

    public function update(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:pending,active,completed,cancelled',
            'notes' => 'sometimes|nullable|string|max:1000',
        ]);

        $reservation->update($validated);

        return redirect()->route('admin.reservations.show', $reservation)
            ->with('success', 'Réservation mise à jour avec succès');
    }

    public function cancel(Reservation $reservation)
    {
        if ($reservation->status === 'cancelled') {
            return redirect()->route('admin.reservations.show', $reservation)
                ->with('error', 'Cette réservation est déjà annulée');
        }

        $reservation->update(['status' => 'cancelled']);

        return redirect()->route('admin.reservations.show', $reservation)
            ->with('success', 'Réservation annulée avec succès');
    }

    public function cleanupExpired(Request $request, ReservationCleanupService $cleanupService)
    {
        $dryRun = $request->boolean('dry_run', false);
        $limit = $request->integer('limit') ?: null;

        $result = $cleanupService->cleanup($dryRun, $limit);

        $message = $dryRun
            ? sprintf('%d réservation(s) seraient annulées (simulation).', $result['count'])
            : sprintf('%d réservation(s) pending ont été annulées.', $result['count']);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $message,
                'result' => $result,
            ]);
        }

        return redirect()->route('admin.reservations.index')
            ->with('success', $message);
    }
}

