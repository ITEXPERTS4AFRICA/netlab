<?php

namespace App\Console\Commands;

use App\Services\ReservationCleanupService;
use Illuminate\Console\Command;

class CleanupExpiredReservations extends Command
{
    protected $signature = 'reservations:cleanup {--dry-run : Ne pas annuler, seulement compter} {--limit= : Limiter le nombre de réservations à traiter}';

    protected $description = 'Annule les réservations pending depuis plus de 15 minutes sans paiement complété';

    public function handle(ReservationCleanupService $cleanupService): int
    {
        $limit = $this->option('limit');
        $limit = $limit ? (int) $limit : null;

        $dryRun = (bool) $this->option('dry-run');

        $result = $cleanupService->cleanup($dryRun, $limit);

        if ($result['count'] === 0) {
            $this->info('Aucune réservation pending expirée à traiter.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info(sprintf('%d réservation(s) seraient annulées (dry-run).', $result['count']));
        } else {
            $this->info(sprintf('%d réservation(s) pending ont été annulées.', $result['count']));
        }

        $this->line('IDs: ' . implode(', ', $result['reservation_ids']));

        return self::SUCCESS;
    }
}

