<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Services\CiscoApiService;
use App\Models\Lab;
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

        $totalLabs = count($lab_id);
        $activeReservations = Reservation::where('status', ' DEFINED_ON_CORE')
            ->where('end_at', '>', now())
            ->with(['user', 'lab'])
            ->get();
        $availableLabs = $totalLabs - $activeReservations->count();

        // Get recent reservations for the current user
        $userReservations = Reservation::where('user_id', $user->id)
            ->with('lab')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get CML data with caching and error handling
        $cmlLabsData = [];
        $cmlSystemHealth = null;

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
            'cmlLabs' => $cmlLabsData,
            'cmlSystemHealth' => $cmlSystemHealth,
        ]);
    }
}
