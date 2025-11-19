<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $now = now();
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $now->copy()->endOfWeek();
        $startRange = $now->copy()->startOfDay();
        $endRange = $now->copy()->addDays(7)->endOfDay();

        $summary = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'active_reservations' => Reservation::where('status', 'active')
                ->where('end_at', '>', $now)
                ->count(),
            'pending_reservations' => Reservation::where('status', 'pending')->count(),
            'weekly_reservations' => Reservation::whereBetween('start_at', [$startOfWeek, $endOfWeek])->count(),
            'monthly_revenue_cents' => Payment::where('status', 'completed')
                ->whereBetween('paid_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
                ->sum('amount'),
        ];

        $pipelineRaw = Reservation::select(
            'status',
            DB::raw('COUNT(*) as count'),
            DB::raw('COALESCE(SUM(estimated_cents), 0) as value')
        )
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $orderedStatuses = ['pending', 'active', 'completed', 'cancelled'];
        $pipeline = collect($orderedStatuses)->map(function ($status) use ($pipelineRaw) {
            $item = $pipelineRaw[$status] ?? null;
            return [
                'status' => $status,
                'count' => $item ? (int) $item->count : 0,
                'value' => $item ? (int) $item->value : 0,
            ];
        });

        $upcomingReservations = Reservation::with(['lab:id,lab_title', 'user:id,name,email'])
            ->where('end_at', '>', $startRange)
            ->orderBy('start_at')
            ->limit(10)
            ->get()
            ->map(function (Reservation $reservation) {
                return [
                    'id' => $reservation->id,
                    'lab_title' => $reservation->lab->lab_title ?? 'Lab',
                    'user_name' => $reservation->user->name ?? 'Utilisateur',
                    'status' => $reservation->status,
                    'start_at' => $reservation->start_at?->toIso8601String(),
                    'end_at' => $reservation->end_at?->toIso8601String(),
                    'duration_hours' => $reservation->start_at && $reservation->end_at
                        ? round($reservation->start_at->diffInMinutes($reservation->end_at) / 60, 1)
                        : null,
                ];
            });

        $ganttReservations = Reservation::with(['lab:id,lab_title', 'user:id,name'])
            ->where(function ($query) use ($startRange, $endRange) {
                $query->whereBetween('start_at', [$startRange, $endRange])
                    ->orWhereBetween('end_at', [$startRange, $endRange])
                    ->orWhere(function ($sub) use ($startRange, $endRange) {
                        $sub->where('start_at', '<', $startRange)
                            ->where('end_at', '>', $endRange);
                    });
            })
            ->orderBy('start_at')
            ->limit(20)
            ->get()
            ->map(function (Reservation $reservation) {
                return [
                    'id' => $reservation->id,
                    'lab_title' => $reservation->lab->lab_title ?? 'Lab',
                    'user_name' => $reservation->user->name ?? 'Utilisateur',
                    'status' => $reservation->status,
                    'start_at' => $reservation->start_at?->toIso8601String(),
                    'end_at' => $reservation->end_at?->toIso8601String(),
                    'estimated_cents' => $reservation->estimated_cents ?? 0,
                ];
            });

        $recentReservationActions = Reservation::with(['user:id,name,email', 'lab:id,lab_title'])
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function (Reservation $reservation) {
                return [
                    'id' => 'reservation-' . $reservation->id,
                    'type' => 'reservation',
                    'title' => $reservation->lab->lab_title ?? 'Réservation',
                    'status' => $reservation->status,
                    'success' => in_array($reservation->status, ['active', 'completed']),
                    'timestamp' => $reservation->updated_at?->toIso8601String(),
                    'description' => $reservation->user ? $reservation->user->name : 'Utilisateur',
                ];
            });

        $recentPaymentActions = Payment::with(['user:id,name,email', 'reservation.lab:id,lab_title'])
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function (Payment $payment) {
                return [
                    'id' => 'payment-' . $payment->id,
                    'type' => 'payment',
                    'title' => $payment->reservation?->lab->lab_title ?? 'Paiement',
                    'status' => $payment->status,
                    'success' => $payment->status === 'completed',
                    'timestamp' => $payment->updated_at?->toIso8601String(),
                    'description' => $payment->user?->name ?? 'Utilisateur',
                ];
            });

        $activityFeed = $recentReservationActions
            ->merge($recentPaymentActions)
            ->sortByDesc('timestamp')
            ->take(12)
            ->values();

        $actionStats = [
            'success' => $activityFeed->where('success', true)->count(),
            'failed' => $activityFeed->where('success', false)->count(),
        ];

        return Inertia::render('admin/dashboard/index', [
            'summary' => $summary,
            'pipeline' => $pipeline,
            'upcomingReservations' => $upcomingReservations,
            'gantt' => [
                'range' => [
                    'start' => $startRange->toIso8601String(),
                    'end' => $endRange->toIso8601String(),
                ],
                'items' => $ganttReservations,
            ],
            'activityFeed' => $activityFeed,
            'actionStats' => $actionStats,
        ]);
    }
}

