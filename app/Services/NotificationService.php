<?php

namespace App\Services;

use App\Models\User;
use App\Models\Reservation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\LabAvailabilityNotification;

class NotificationService
{
    /**
     * Check for upcoming reservations and send notifications
     */
    public function checkUpcomingReservations(): void
    {
        $now = now();

        // Get reservations starting in the next 15-60 minutes (based on user preferences)
        $reservations = Reservation::with(['user', 'lab'])
            ->where('status', '!=', 'cancelled')
            ->where('start_at', '>', $now)
            ->where('start_at', '<=', $now->copy()->addMinutes(60))
            ->get();

        foreach ($reservations as $reservation) {
            $this->processReservationNotification($reservation);
        }
    }

    /**
     * Process notification for a specific reservation
     */
    private function processReservationNotification(Reservation $reservation): void
    {
        $user = $reservation->user;
        $minutesUntilStart = now()->diffInMinutes($reservation->start_at, false);

        // Check if user wants notifications
        if (!$user->notification_enabled) {

            return;
        }

        // Check if we should send notification based on user's advance notice preference
        $advanceMinutes = $user->notification_advance_minutes ?? 15;
        if ($minutesUntilStart > $advanceMinutes) {


            return;
        }

        // Check if we already sent a notification for this reservation
        if ($this->notificationAlreadySent($reservation, $advanceMinutes)) {

            
            return;
        }

        // Send notification based on user preference
        $this->sendNotification($user, $reservation);
    }

    /**
     * Check if notification was already sent for this reservation
     */
    private function notificationAlreadySent(Reservation $reservation, int $advanceMinutes): bool
    {
        // This is a simple check - in production you might want to store notification history
        $notificationKey = "reservation_{$reservation->id}_notified_{$advanceMinutes}min";

        // For now, we'll use a simple cache check
        // In production, you might want to store this in a notifications table
        return cache()->has($notificationKey);
    }

    /**
     * Send notification to user based on their preferences
     */
    private function sendNotification(User $user, Reservation $reservation): void
    {
        try {
            $notificationData = [
                'reservation' => $reservation,
                'lab' => $reservation->lab,
                'minutes_until_start' => now()->diffInMinutes($reservation->start_at, false),
            ];

            switch ($user->notification_type) {
                case 'email':
                    $user->notify(new LabAvailabilityNotification($notificationData, 'email'));
                    break;

                case 'browser':
                    // Browser notifications would be handled via WebSockets/Pusher
                    $this->sendBrowserNotification($user, $notificationData);
                    break;

                case 'both':
                default:
                    $user->notify(new LabAvailabilityNotification($notificationData, 'email'));
                    $this->sendBrowserNotification($user, $notificationData);
                    break;
            }

            // Mark notification as sent
            $advanceMinutes = $user->notification_advance_minutes ?? 15;
            $notificationKey = "reservation_{$reservation->id}_notified_{$advanceMinutes}min";
            cache()->put($notificationKey, true, now()->addHours(2));

            Log::info("Notification sent to user {$user->id} for reservation {$reservation->id}");

        } catch (\Exception $e) {
            Log::error("Failed to send notification to user {$user->id}: " . $e->getMessage());
        }
    }

    /**
     * Send browser notification (via WebSockets or real-time events)
     */
    private function sendBrowserNotification(User $user, array $notificationData): void
    {
        // This would typically use Laravel Broadcasting or WebSockets
        // For now, we'll just log it
        Log::info("Browser notification would be sent to user {$user->id}", $notificationData);

        // In a real implementation, you would broadcast to a channel like:
        // broadcast(new LabAvailabilityEvent($user, $notificationData))->toOthers();
    }

    /**
     * Send notification when lab becomes available
     */
    public function notifyLabAvailable(User $user, $lab): void
    {
        if (!$user->notification_enabled) {
            return;
        }

        try {
            $notificationData = [
                'lab' => $lab,
                'type' => 'lab_available',
            ];

            switch ($user->notification_type) {
                case 'email':
                    $user->notify(new LabAvailabilityNotification($notificationData, 'email'));
                    break;

                case 'browser':
                    $this->sendBrowserNotification($user, $notificationData);
                    break;

                case 'both':
                default:
                    $user->notify(new LabAvailabilityNotification($notificationData, 'email'));
                    $this->sendBrowserNotification($user, $notificationData);
                    break;
            }

        } catch (\Exception $e) {
            Log::error("Failed to send lab available notification to user {$user->id}: " . $e->getMessage());
        }
    }

    /**
     * Schedule notification check (to be called by a scheduled command)
     */
    public function scheduleNotificationCheck(): void
    {
        // This method would be called by a Laravel scheduler
        // php artisan schedule:work
        $this->checkUpcomingReservations();
    }
}
