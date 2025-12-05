<?php

namespace App\Notifications\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SystemAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $alertType, // 'high_load', 'database_error', 'maintenance', etc.
        public string $message,
        public ?string $severity = 'warning', // 'info', 'warning', 'error', 'critical'
        public array $metrics = []
    ) {
        $this->queue = 'notifications';
    }

    public function via($notifiable): array
    {
        $prefs = $notifiable->getOrCreateNotificationPreference();
        
        // Critical system alerts always go through
        if ($this->severity === 'critical') {
            return array_merge($prefs->getEnabledChannels(), ['email', 'sms', 'push', 'in_app']);
        }

        if ($prefs->isMuted() || !$prefs->isNotificationTypeEnabled('system_alert')) {
            return [];
        }

        return $prefs->getEnabledChannels();
    }

    public function toMail($notifiable): MailMessage
    {
        $severityLabel = strtoupper($this->severity ?? 'warning');

        return (new MailMessage)
            ->subject("[$severityLabel] System Alert - {$this->message}")
            ->greeting("Hello {$notifiable->user->name},")
            ->line($this->message)
            ->when(count($this->metrics) > 0, function ($mail) {
                $mail->line("**Metrics:**");
                foreach ($this->metrics as $key => $value) {
                    $mail->line("- " . str_replace('_', ' ', ucwords($key)) . ": $value");
                }
                return $mail;
            })
            ->action('View System Status', url('/admin/system-status'))
            ->line('Monitor your system performance.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'system_alert',
            'title' => 'System Alert',
            'message' => $this->message,
            'alert_type' => $this->alertType,
            'severity' => $this->severity,
            'metrics' => $this->metrics,
        ];
    }
}
