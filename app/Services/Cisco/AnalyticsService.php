<?php

namespace App\Services\Cisco;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AnalyticsService extends BaseCiscoApiService
{
    /**
     * Tracker un appel API
     */
    public function trackApiCall(string $endpoint, float $duration, bool $success = true): void
    {
        DB::table('api_metrics')->insert([
            'endpoint' => $endpoint,
            'duration_ms' => $duration,
            'success' => $success,
            'created_at' => now()
        ]);
    }

    /**
     * Obtenir les statistiques d'utilisation d'un lab
     */
    public function getLabUsageStats(string $labId): array
    {
        $labInfo = $this->get("/v0/labs/{$labId}");
        
        return [
            'lab_id' => $labId,
            'title' => $labInfo['lab_title'] ?? '',
            'nodes_count' => count($labInfo['nodes'] ?? []),
            'links_count' => count($labInfo['links'] ?? []),
            'state' => $labInfo['state'] ?? '',
            'created' => $labInfo['created'] ?? '',
            'modified' => $labInfo['modified'] ?? '',
        ];
    }

    /**
     * Rapport d'utilisation par utilisateur
     */
    public function getUserUsageReport(string $userId, string $period = 'monthly'): array
    {
        $labs = $this->get('/v0/labs?show_all=true');
        $userLabs = collect($labs)->where('owner', $userId);

        return [
            'user_id' => $userId,
            'period' => $period,
            'total_labs' => $userLabs->count(),
            'active_labs' => $userLabs->where('state', 'STARTED')->count(),
            'stopped_labs' => $userLabs->where('state', 'STOPPED')->count(),
            'total_nodes' => $userLabs->sum(fn($lab) => count($lab['nodes'] ?? [])),
        ];
    }

    /**
     * Métriques de performance de l'API
     */
    public function getPerformanceMetrics(): array
    {
        $cacheKey = 'analytics:performance';
        
        return Cache::remember($cacheKey, 300, function() {
            $metrics = DB::table('api_metrics')
                ->where('created_at', '>=', now()->subHours(24))
                ->get();

            return [
                'total_calls' => $metrics->count(),
                'avg_response_time' => $metrics->avg('duration_ms'),
                'error_rate' => $metrics->where('success', false)->count() / max($metrics->count(), 1) * 100,
                'success_rate' => $metrics->where('success', true)->count() / max($metrics->count(), 1) * 100,
                'slowest_endpoint' => $metrics->sortByDesc('duration_ms')->first()->endpoint ?? null,
            ];
        });
    }

    /**
     * Statistiques d'utilisation des ressources
     */
    public function getResourceStats(): array
    {
        $labs = $this->get('/v0/labs?show_all=true');
        
        return [
            'total_labs' => count($labs),
            'running_labs' => collect($labs)->where('state', 'STARTED')->count(),
            'stopped_labs' => collect($labs)->where('state', 'STOPPED')->count(),
            'total_nodes' => collect($labs)->sum(fn($lab) => count($lab['nodes'] ?? [])),
            'total_links' => collect($labs)->sum(fn($lab) => count($lab['links'] ?? [])),
            'avg_nodes_per_lab' => collect($labs)->avg(fn($lab) => count($lab['nodes'] ?? [])),
        ];
    }

    /**
     * Tendances d'utilisation
     */
    public function getUsageTrends(int $days = 30): array
    {
        $trends = [];
        
        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->toDateString();
            
            $trends[$date] = [
                'date' => $date,
                'api_calls' => $this->getApiCallsForDate($date),
                'labs_created' => $this->getLabsCreatedForDate($date),
            ];
        }

        return array_reverse($trends);
    }

    /**
     * Obtenir les appels API pour une date
     */
    protected function getApiCallsForDate(string $date): int
    {
        return DB::table('api_metrics')
            ->whereDate('created_at', $date)
            ->count();
    }

    /**
     * Obtenir les labs créés pour une date
     */
    protected function getLabsCreatedForDate(string $date): int
    {
        return DB::table('labs')
            ->whereDate('created_at', $date)
            ->count();
    }

    /**
     * Statistiques en temps réel
     */
    public function getRealTimeStats(): array
    {
        return [
            'timestamp' => now()->toIso8601String(),
            'active_users' => $this->getActiveUsersCount(),
            'running_labs' => $this->getRunningLabsCount(),
            'api_load' => $this->getCurrentApiLoad(),
        ];
    }

    protected function getActiveUsersCount(): int
    {
        return Cache::get('analytics:active_users', 0);
    }

    protected function getRunningLabsCount(): int
    {
        $labs = $this->get('/v0/labs?show_all=true');
        return collect($labs)->where('state', 'STARTED')->count();
    }

    protected function getCurrentApiLoad(): float
    {
        $recentCalls = DB::table('api_metrics')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();
            
        return $recentCalls / 5; // calls per minute
    }
}

