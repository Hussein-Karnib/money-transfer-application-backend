<?php

namespace App\Notifications\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerificationsPendingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $pendingCount,
        public string $verificationType, // 'id_verification', 'bank_account', etc.
        public int $averageWaitingDays = 0
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
            ->subject("{$this->pendingCount} Pending Verifications Awaiting Review")
            ->greeting("Hello {$notifiable->user->name},")
            ->line("There are {$this->pendingCount} documents awaiting {$this->verificationType} review.")
            ->when($this->averageWaitingDays > 0, fn($mail) => $mail->line("Average waiting time: {$this->averageWaitingDays} days"))
            ->action('Review Verifications', url('/admin/verifications/pending'))
            ->line('Please review and approve/reject these verifications.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'verification_pending',
            'title' => "{$this->pendingCount} Verifications Pending",
            'message' => "{$this->pendingCount} documents awaiting {$this->verificationType} review",
            'pending_count' => $this->pendingCount,
            'verification_type' => $this->verificationType,
            'average_waiting_days' => $this->averageWaitingDays,
        ];
    }
}
