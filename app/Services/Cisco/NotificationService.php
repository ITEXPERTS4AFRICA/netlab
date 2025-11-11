<?php

namespace App\Services\Cisco;

use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Notifier sur changement d'état de lab
     */
    public function notifyLabStateChange(string $labId, string $oldState, string $newState, array $recipients): void
    {
        $message = "Lab {$labId} state changed from {$oldState} to {$newState}";
        
        foreach ($recipients as $recipient) {
            $this->send($recipient, 'Lab State Change', $message);
        }
    }

    /**
     * Notifier en cas de panne de node
     */
    public function notifyNodeFailure(string $labId, string $nodeId, array $channels = ['email']): void
    {
        $message = "Node {$nodeId} in lab {$labId} has failed";
        
        foreach ($channels as $channel) {
            $this->sendToChannel($channel, 'Node Failure Alert', $message);
        }
    }

    /**
     * Rappel avant réservation
     */
    public function sendReservationReminder(string $reservationId, int $minutesBefore = 15): void
    {
        $message = "Your lab reservation {$reservationId} starts in {$minutesBefore} minutes";
        
        // Logic to get reservation and user
        $this->send('user@example.com', 'Reservation Reminder', $message);
    }

    /**
     * Notification d'expiration de lab
     */
    public function notifyLabExpiry(string $labId, string $userId): void
    {
        $message = "Your lab {$labId} is about to expire";
        
        $this->send($userId, 'Lab Expiry Warning', $message);
    }

    /**
     * Alerter sur utilisation excessive de ressources
     */
    public function alertResourceUsage(array $stats, string $threshold = 'high'): void
    {
        $message = "Resource usage is {$threshold}: " . json_encode($stats);
        
        $this->sendToChannel('slack', 'Resource Alert', $message);
    }

    /**
     * Notification de succès d'opération
     */
    public function notifySuccess(string $operation, array $details): void
    {
        $message = "Operation '{$operation}' completed successfully";
        
        $this->send(auth()->user()->email ?? 'admin@example.com', 'Operation Success', $message);
    }

    /**
     * Notification d'échec d'opération
     */
    public function notifyFailure(string $operation, string $error): void
    {
        $message = "Operation '{$operation}' failed: {$error}";
        
        $this->send(auth()->user()->email ?? 'admin@example.com', 'Operation Failed', $message, 'error');
    }

    /**
     * Envoyer une notification par email
     */
    protected function send(string $recipient, string $subject, string $message, string $level = 'info'): void
    {
        // Email notification
        Mail::raw($message, function($mail) use ($recipient, $subject) {
            $mail->to($recipient)
                 ->subject($subject);
        });
    }

    /**
     * Envoyer à un canal spécifique
     */
    protected function sendToChannel(string $channel, string $subject, string $message): void
    {
        switch ($channel) {
            case 'email':
                $this->send('admin@example.com', $subject, $message);
                break;
                
            case 'slack':
                $this->sendToSlack($message);
                break;
                
            case 'webhook':
                $this->sendToWebhook($message);
                break;
        }
    }

    /**
     * Envoyer à Slack
     */
    protected function sendToSlack(string $message): void
    {
        $webhookUrl = config('services.slack.webhook_url');
        
        if ($webhookUrl) {
            $data = ['text' => $message];
            
            $ch = curl_init($webhookUrl);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_exec($ch);
            curl_close($ch);
        }
    }

    /**
     * Envoyer à un webhook
     */
    protected function sendToWebhook(string $message): void
    {
        $webhookUrl = config('services.webhook.url');
        
        if ($webhookUrl) {
            $data = [
                'message' => $message,
                'timestamp' => now()->toIso8601String()
            ];
            
            $ch = curl_init($webhookUrl);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_exec($ch);
            curl_close($ch);
        }
    }

    /**
     * Programmer une notification
     */
    public function scheduleNotification(string $recipient, string $subject, string $message, \DateTime $when): void
    {
        // Use Laravel's notification scheduling
        // Notification::send($recipient, (new CustomNotification($subject, $message))->delay($when));
    }

    /**
     * Envoyer une notification en masse
     */
    public function notifyBulk(array $recipients, string $subject, string $message): void
    {
        foreach ($recipients as $recipient) {
            $this->send($recipient, $subject, $message);
        }
    }
}

