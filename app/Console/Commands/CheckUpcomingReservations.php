<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class CheckUpcomingReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:check-upcoming-reservations {--dry-run : Run without sending actual notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for upcoming lab reservations and send notifications to users';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService)
    {
        $dryRun = $this->option('dry-run');

        $this->info('Checking for upcoming reservations...');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No notifications will be sent');
        }

        try {
            // Temporarily override the notification service to prevent sending in dry-run mode
            if ($dryRun) {
                $this->info('Would check for upcoming reservations and send notifications...');
                $this->info('Dry run completed successfully!');
                return 0;
            }

            $notificationService->checkUpcomingReservations();

            $this->info('Notification check completed successfully!');

        } catch (\Exception $e) {
            $this->error('Error checking reservations: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
