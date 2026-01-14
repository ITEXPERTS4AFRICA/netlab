<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Services\CiscoApiService;
use App\Models\Reservation;
use Illuminate\Support\Facades\Auth;
use DB;

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

        // Get labs from CML API
        $lab_id = [];
        if ($token) {
            $this->apiService->setToken($token);
            $lab_id = $this->apiService->getLabs();
        }

        // Get lab statistics from database (local cache)
        // Check if the API response contains an error
        if (isset($lab_id['error'])) {
            Log::warning('Failed to fetch labs from CML API', ['error' => $lab_id['error']]);
            $totalLabs = 0;
        } elseif (is_array($lab_id)) {
            $totalLabs = count($lab_id);
        } else {
            $totalLabs = 0;
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
            ->with('lab')
            ->where('start_at', '<=', now())
            ->where('end_at', '>', now())
            ->where('status', '!=', 'cancelled')
            ->get();


        // Get CML data with caching and error handling
        $cmlLabsData = [];
        $cmlSystemHealth = null;
        $systemStats = null;

        if ($token) {
            // Cache CML labs for 5 minutes
            // Utiliser un hash du token pour éviter les clés trop longues
            $tokenHash = substr(md5($token), 0, 16);
            $cacheKeyLabs = 'cml_labs_dashboard_' . $tokenHash;
            $cmlLabsData = Cache::remember($cacheKeyLabs, now()->addMinutes(5), function () use ($token) {
                try {
                    $this->apiService->setToken($token);
                    $labs = $this->apiService->getLabs();
                    // Si c'est un array et qu'il n'y a pas d'erreur, retourner les labs
                    if (is_array($labs) && !isset($labs['error'])) {
                        return array_slice($labs, 0, 10);
                    }
                    // Si erreur, logger et retourner vide
                    if (isset($labs['error'])) {
                        Log::warning('Failed to fetch CML labs data', ['error' => $labs['error'], 'status' => $labs['status'] ?? 'unknown']);
                    }
                    return [];
                } catch (\Exception $e) {
                    Log::warning('Failed to fetch CML labs data: ' . $e->getMessage());
                    return [];
                }
            });

            // Cache CML system health for 5 minutes
            $cacheKeyHealth = 'cml_system_health_' . $tokenHash;
            $cmlSystemHealth = Cache::remember($cacheKeyHealth, now()->addMinutes(5), function () use ($token) {
                try {
                    $this->apiService->setToken($token);
                    return $this->apiService->system->getSystemHealth();
                } catch (\Exception $e) {
                    Log::warning('Failed to fetch CML system health: ' . $e->getMessage());
                    return null;
                }
            });

            // Cache CML system stats for 5 minutes
            $cacheKeyStats = 'cml_system_stats_' . $tokenHash;
            $systemStats = Cache::remember($cacheKeyStats, now()->addMinutes(5), function () use ($token) {
                try {
                    $this->apiService->setToken($token);
                    return $this->apiService->system->getSystemStats();
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

          // Calculate average session duration (compatible with SQLite and PostgreSQL)
        $driver = \DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite: use julianday() to calculate difference in days, then convert to minutes
            $avgSessionDuration = Reservation::where('status', 'completed')
                ->whereNotNull('start_at')
                ->whereNotNull('end_at')
                ->selectRaw('AVG((julianday(end_at) - julianday(start_at)) * 24 * 60) as avg_duration')
                ->first()
                ->avg_duration ?? 0;
        } else {
            // PostgreSQL: use EXTRACT(EPOCH FROM ...)
            $avgSessionDuration = Reservation::where('status', 'completed')
                ->whereNotNull('start_at')
                ->whereNotNull('end_at')
                ->selectRaw('AVG(EXTRACT(EPOCH FROM (end_at - start_at)) / 60) as avg_duration')
                ->first()
                ->avg_duration ?? 0;
        }




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
