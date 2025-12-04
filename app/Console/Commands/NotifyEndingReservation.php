<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Reservation;
use Illuminate\Support\Carbon;

class NotifyEndingReservation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:notify-ending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify users when their reservation is ending (30, 15, 10 mins)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $intervals = [30, 15, 10];

        foreach ($intervals as $minutes) {
            $endingSoon = Reservation::where('status', 'active')
                ->whereBetween('end_at', [
                    Carbon::now()->addMinutes($minutes - 1),
                    Carbon::now()->addMinutes($minutes + 1)
                ])
                ->with('user', 'lab')
                ->get();

            foreach ($endingSoon as $reservation) {
                $this->info("Notifying user {$reservation->user->id} that reservation {$reservation->id} ends in {$minutes} mins");
                
                \Log::info("Notification: Your lab {$reservation->lab->lab_title} ends in {$minutes} minutes.", [
                    'user_id' => $reservation->user->id,
                    'reservation_id' => $reservation->id,
                    'minutes_left' => $minutes
                ]);
                
                // TODO: Send real notification
                // $reservation->user->notify(new EndingReservationNotification($reservation, $minutes));
            }
        }
    }
}
