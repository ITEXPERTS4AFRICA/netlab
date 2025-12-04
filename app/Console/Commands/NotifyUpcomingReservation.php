<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Reservation;
use Illuminate\Support\Facades\Notification;
// use App\Notifications\UpcomingReservationNotification; // Assuming this exists or we create it
use Illuminate\Support\Carbon;

class NotifyUpcomingReservation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:notify-upcoming';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify users 30 minutes before their reservation starts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $upcoming = Reservation::where('status', 'pending') // Or confirmed/active depending on flow
            ->whereBetween('start_at', [
                Carbon::now()->addMinutes(29),
                Carbon::now()->addMinutes(31)
            ])
            ->with('user', 'lab')
            ->get();

        foreach ($upcoming as $reservation) {
            $this->info("Notifying user {$reservation->user->id} for reservation {$reservation->id}");
            
            // Send notification (placeholder for actual notification class)
            // $reservation->user->notify(new UpcomingReservationNotification($reservation));
            
            // For now, we can log or just comment that notification is sent
            \Log::info("Notification: Your lab {$reservation->lab->lab_title} starts in 30 minutes.", [
                'user_id' => $reservation->user->id,
                'reservation_id' => $reservation->id
            ]);
        }
    }
}
