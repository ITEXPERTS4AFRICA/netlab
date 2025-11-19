<?php

namespace App\Services;

use App\Models\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReservationCleanupService
{
    protected function expiredPendingQuery(): Builder
    {
        $threshold = now()->subMinutes(15);

        return Reservation::where('status', 'pending')
            ->where('created_at', '<=', $threshold)
            ->whereDoesntHave('payments', function ($query) {
                $query->where('status', 'completed');
            })
            ->orderBy('created_at');
    }

    public function getExpiredPending(?int $limit = null): Collection
    {
        $query = $this->expiredPendingQuery();

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    public function cleanup(bool $dryRun = false, ?int $limit = null): array
    {
        $reservations = $this->getExpiredPending($limit);
        $count = $reservations->count();

        if (!$dryRun) {
            $note = 'Annulée automatiquement (pending > 15 min sans paiement) le ' . now()->format('Y-m-d H:i:s');

            foreach ($reservations as $reservation) {
                $reservation->update([
                    'status' => 'cancelled',
                    'notes' => trim(($reservation->notes ? $reservation->notes . PHP_EOL : '') . $note),
                ]);
            }
        }

        return [
            'count' => $count,
            'reservation_ids' => $reservations->pluck('id')->all(),
            'dry_run' => $dryRun,
        ];
    }
}

