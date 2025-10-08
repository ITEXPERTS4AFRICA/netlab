<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Services\CiscoApiService;
use App\Models\Reservation;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected $apiService;

    public function __construct(CiscoApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function index()
    {
        $user = Auth::user();
        $token = session('cml_token');

        $lab_id = $this->apiService->getLabs($token);

        // Get lab statistics from database (local cache)
        // Check if the API response contains an error
        if (isset($lab_id['error'])) {
            $totalLabs = 0;
        } else {
            $totalLabs = count($lab_id);
        }
        $activeReservations = Reservation::where('status', 'active')
            ->where('end_at', '>', now())
            ->with(['user', 'lab'])
            ->get();
        $availableLabs = $totalLabs - $activeReservations->count();

        // Get recent reservations for the current user (exclude expired ones)
        $userReservations = Reservation::where('user_id', $user->id)
            ->with('lab')
            ->where('end_at', '>', now()) // Exclude expired reservations
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get user's active reservations (currently active)
        $userActiveReservations = Reservation::where('user_id', $user->id)
            ->where('start_at', '<=', now())
            ->where('end_at', '>', now())
            ->where('status', '!=', 'cancelled')
            ->with('lab')
            ->get();

        // Get CML data with caching and error handling
        $cmlLabsData = [];
        $cmlSystemHealth = null;
        $systemStats = null;

        if ($token) {
            // Cache CML labs for 5 minutes
            $cacheKeyLabs = 'cml_labs_dashboard_' . $token;
            $cmlLabsData = Cache::remember($cacheKeyLabs, now()->addMinutes(5), function () use ($token) {
                try {
                    if ($this->apiService->setToken($token)) {
                        $labs = $this->apiService->getLabs($token);
                        return is_array($labs) ? array_slice($labs, 0, 10) : [];
                    }
                    return [];
                } catch (\Exception $e) {
                    Log::warning('Failed to fetch CML labs data: ' . $e->getMessage());
                    return [];
                }
            });

            // Cache CML system health for 5 minutes
            $cacheKeyHealth = 'cml_system_health_' . $token;
            $cmlSystemHealth = Cache::remember($cacheKeyHealth, now()->addMinutes(5), function () use ($token) {
                try {
                    if ($this->apiService->setToken($token)) {
                        return $this->apiService->getSystemHealth($token);
                    }
                    return null;
                } catch (\Exception $e) {
                    Log::warning('Failed to fetch CML system health: ' . $e->getMessage());
                    return null;
                }
            });

            // Cache CML system stats for 5 minutes
            $cacheKeyStats = 'cml_system_stats_' . $token;
            $systemStats = Cache::remember($cacheKeyStats, now()->addMinutes(5), function () use ($token) {
                try {
                    if ($this->apiService->setToken($token)) {
                        return $this->apiService->getSystemStats($token);
                    }
                    return null;
                } catch (\Exception $e) {
                    Log::warning('Failed to fetch CML system stats: ' . $e->getMessage());
                    return null;
                }
            });
        }

        // Calculate advanced metrics
        $totalReservations = Reservation::count();
        $activeReservationsCount = Reservation::where('status', '!=', 'cancelled')
            ->where('end_at', '>', now())
            ->count();
        $completedReservations = Reservation::where('status', 'completed')->count();
        $cancelledReservations = Reservation::where('status', 'cancelled')->count();

        // Calculate usage statistics
        $todayReservations = Reservation::whereDate('created_at', today())->count();
        $weekReservations = Reservation::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $monthReservations = Reservation::whereMonth('created_at', now()->month)->count();

        // Calculate average session duration (SQLite compatible)
        $avgSessionDuration = Reservation::where('status', 'completed')
            ->whereNotNull('start_at')
            ->whereNotNull('end_at')
            ->selectRaw('AVG((julianday(end_at) - julianday(start_at)) * 1440) as avg_duration')
            ->first()
            ->avg_duration ?? 0;

        return Inertia::render('dashboard', [
            'stats' => [
                'totalLabs' => $totalLabs,
                'availableLabs' => max(0, $availableLabs),
                'occupiedLabs' => $activeReservations->count(),
                'userReservations' => $userReservations->count(),
            ],
            'activeReservations' => $activeReservations->map(function ($reservation) {
                return [
                    'id' => $reservation->id,
                    'lab_title' => $reservation->lab->lab_title ?? 'Unknown Lab',
                    'user_name' => $reservation->user->name,
                    'user_email' => $reservation->user->email,
                    'start_at' => $reservation->start_at?->format('Y-m-d H:i'),
                    'end_at' => $reservation->end_at?->format('Y-m-d H:i'),
                    'duration_hours' => $reservation->start_at && $reservation->end_at
                        ? round($reservation->start_at->diffInHours($reservation->end_at), 1)
                        : null,
                ];
            }),
            'userReservations' => $userReservations->map(function ($reservation) {
                return [
                    'id' => $reservation->id,
                    'lab_title' => $reservation->lab->lab_title ?? 'Unknown Lab',
                    'start_at' => $reservation->start_at?->format('Y-m-d H:i'),
                    'end_at' => $reservation->end_at?->format('Y-m-d H:i'),
                    'status' => $reservation->status,
                    'created_at' => $reservation->created_at?->diffForHumans(),
                ];
            }),
            'userActiveReservations' => $userActiveReservations->map(function ($reservation) {
                return [
                    'id' => $reservation->id,
                    'lab_id' => $reservation->lab->id,
                    'lab_title' => $reservation->lab->lab_title ?? 'Unknown Lab',
                    'lab_description' => $reservation->lab->lab_description ?? '',
                    'start_at' => $reservation->start_at?->format('Y-m-d H:i'),
                    'end_at' => $reservation->end_at?->format('Y-m-d H:i'),
                    'duration_hours' => $reservation->start_at && $reservation->end_at
                        ? round($reservation->start_at->diffInHours($reservation->end_at), 1)
                        : null,
                    'time_remaining' => $reservation->end_at ? now()->diffInMinutes($reservation->end_at, false) : null,
                ];
            }),
            'cmlLabs' => $cmlLabsData,
            'cmlSystemHealth' => $cmlSystemHealth,
            'systemStats' => $systemStats,
            'advancedMetrics' => [
                'totalReservations' => $totalReservations,
                'activeReservationsCount' => $activeReservationsCount,
                'completedReservations' => $completedReservations,
                'cancelledReservations' => $cancelledReservations,
                'todayReservations' => $todayReservations,
                'weekReservations' => $weekReservations,
                'monthReservations' => $monthReservations,
                'avgSessionDuration' => round($avgSessionDuration, 1),
                'utilizationRate' => $totalLabs > 0 ? round(($activeReservations->count() / $totalLabs) * 100, 1) : 0,
            ],
        ]);
    }
}
