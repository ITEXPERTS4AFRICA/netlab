<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LabAvailabilityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected array $notificationData;
    protected string $channel;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $notificationData, string $channel = 'email')
    {
        $this->notificationData = $notificationData;
        $this->channel = $channel;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [$this->channel];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $type = $this->notificationData['type'] ?? 'reservation';

        if ($type === 'lab_available') {
            $lab = $this->notificationData['lab'];
            return (new MailMessage)
                ->subject('Lab Available - ' . $lab->name)
                ->greeting('Hello ' . $notifiable->name . '!')
                ->line('A lab you were interested in is now available.')
                ->line('Lab: ' . $lab->name)
                ->line('Description: ' . ($lab->description ?? 'No description available'))
                ->action('View Lab', url('/labs/' . $lab->id))
                ->line('Reserve it now before someone else does!');
        }

        // Default reservation reminder
        $reservation = $this->notificationData['reservation'];
        $lab = $this->notificationData['lab'];
        $minutes = $this->notificationData['minutes_until_start'];

        return (new MailMessage)
            ->subject('Lab Reservation Reminder - ' . $minutes . ' minutes')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your lab reservation is starting soon.')
            ->line('Lab: ' . $lab->name)
            ->line('Start Time: ' . $reservation->start_at->format('M j, Y \a\t g:i A'))
            ->line('Duration: ' . $reservation->start_at->diffInMinutes($reservation->end_at) . ' minutes')
            ->action('Access Lab', url('/labs/' . $lab->id))
            ->line('Please be ready to start your lab session.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'data' => $this->notificationData,
            'channel' => $this->channel,
            'user_id' => $notifiable->id,
        ];
    }
}
