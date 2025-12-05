<?php

namespace App\Notifications\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UnusualActivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $activityType, // 'volume_spike', 'failed_transfers', 'suspicious_pattern'
        public string $description,
        public array $metrics = [],
        public int $affectedUsersCount = 0
    ) {
        $this->queue = 'notifications';
    }

    public function via($notifiable): array
    {
        $prefs = $notifiable->getOrCreateNotificationPreference();
        
        if ($prefs->isMuted() || !$prefs->isNotificationTypeEnabled('system_alert')) {
            return [];
        }

        return $prefs->getEnabledChannels();
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Unusual Activity Detected - {$this->description}")
            ->greeting("Hello {$notifiable->user->name},")
            ->line("Unusual activity has been detected on the platform!")
            ->line("**Activity Type:** " . str_replace('_', ' ', ucwords($this->activityType)))
            ->line("**Description:** {$this->description}")
            ->when($this->affectedUsersCount > 0, fn($mail) => $mail->line("**Affected Users:** {$this->affectedUsersCount}"))
            ->when(count($this->metrics) > 0, function ($mail) {
                $mail->line("**Metrics:**");
                foreach ($this->metrics as $key => $value) {
                    $mail->line("- " . str_replace('_', ' ', ucwords($key)) . ": $value");
                }
                return $mail;
            })
            ->action('Investigate Activity', url('/admin/analytics'))
            ->line('Monitor and investigate this activity.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'unusual_activity',
            'title' => 'Unusual Activity Detected',
            'message' => "Transfer volume spike +200% in last hour",
            'activity_type' => $this->activityType,
            'description' => $this->description,
            'metrics' => $this->metrics,
            'affected_users' => $this->affectedUsersCount,
        ];
    }
}
