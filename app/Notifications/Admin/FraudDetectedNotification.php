<?php

namespace App\Notifications\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FraudDetectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $userName,
        public string $userId,
        public float $riskScore,
        public string $fraudReason,
        public array $details = []
    ) {
        $this->queue = 'notifications';
    }

    public function via($notifiable): array
    {
        $prefs = $notifiable->getOrCreateNotificationPreference();
        
        // Critical fraud alerts always go through all channels
        if ($this->riskScore >= 80) {
            return array_merge($prefs->getEnabledChannels(), ['email', 'sms', 'push', 'in_app', 'dashboard']);
        }

        if ($prefs->isMuted() || !$prefs->isNotificationTypeEnabled('system_alert')) {
            return [];
        }

        return $prefs->getEnabledChannels();
    }

    public function toMail($notifiable): MailMessage
    {
        $severity = $this->riskScore >= 80 ? 'CRITICAL' : ($this->riskScore >= 50 ? 'HIGH' : 'MEDIUM');

        return (new MailMessage)
            ->subject("[$severity] Fraud Alert - {$this->userName}")
            ->greeting("Alert {$notifiable->user->name},")
            ->line("A suspicious activity has been detected!")
            ->line("**Severity:** $severity")
            ->line("**Risk Score:** {$this->riskScore}/100")
            ->line("**User:** {$this->userName} (ID: {$this->userId})")
            ->line("**Reason:** {$this->fraudReason}")
            ->line("**Details:**")
            ->when(count($this->details) > 0, function ($mail) {
                foreach ($this->details as $key => $value) {
                    $mail->line("- " . str_replace('_', ' ', $key) . ": $value");
                }
                return $mail;
            })
            ->action('Review Fraud Alert', url("/admin/fraud-alerts/{$this->userId}"))
            ->line('Immediate action may be required.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'fraud_detected',
            'title' => 'Fraud Alert - ' . $this->userName,
            'message' => "Fraud detected - {$this->fraudReason}. Risk score: {$this->riskScore}/100",
            'user_name' => $this->userName,
            'user_id' => $this->userId,
            'risk_score' => $this->riskScore,
            'reason' => $this->fraudReason,
            'details' => $this->details,
        ];
    }
}
