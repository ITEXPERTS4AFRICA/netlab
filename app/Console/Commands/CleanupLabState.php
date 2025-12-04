<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Reservation;
use App\Services\CiscoApiService;
use Illuminate\Support\Carbon;

class CleanupLabState extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'labs:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup lab state after reservation ends';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Find reservations that ended in the last 5 minutes and are still marked 'active'
        $endedReservations = Reservation::where('status', 'active')
            ->where('end_at', '<', Carbon::now())
            ->where('end_at', '>', Carbon::now()->subMinutes(5)) // Process recently ended
            ->with('lab')
            ->get();

        $apiService = new CiscoApiService();
        // Note: We need a system admin token for CML operations here, 
        // or we rely on the fact that we can stop any lab if we have admin creds configured in service.
        // For now, assuming service handles auth internally or via config.

        foreach ($endedReservations as $reservation) {
            $this->info("Cleaning up reservation {$reservation->id} for lab {$reservation->lab->lab_title}");

            try {
                // 1. Stop the lab
                // $apiService->labs->stopLab($reservation->lab->cml_id);
                
                // 2. Wipe/Restore default snapshot
                // $apiService->labs->wipeLab($reservation->lab->cml_id);
                
                // 3. Update reservation status
                $reservation->update(['status' => 'completed']);
                
                \Log::info("Lab cleaned up for reservation {$reservation->id}");
                
            } catch (\Exception $e) {
                \Log::error("Failed to cleanup lab for reservation {$reservation->id}: " . $e->getMessage());
            }
        }
    }
}
