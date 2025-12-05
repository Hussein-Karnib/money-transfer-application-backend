<?php

namespace App\Notifications\Agent;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CashPositionAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $alertType, // 'low_balance', 'critical_balance'
        public float $currentBalance,
        public float $minimumThreshold,
        public string $currency
    ) {
        $this->queue = 'notifications';
    }

    public function via($notifiable): array
    {
        $prefs = $notifiable->getOrCreateNotificationPreference();
        
        if ($this->alertType === 'critical_balance') {
            // Critical alerts always go through
            return array_merge($prefs->getEnabledChannels(), ['sms', 'push', 'in_app']);
        }

        if ($prefs->isMuted() || !$prefs->isNotificationTypeEnabled('settlement_alert')) {
            return [];
        }

        return $prefs->getEnabledChannels();
    }

    public function toMail($notifiable): MailMessage
    {
        $subject = $this->alertType === 'critical_balance' 
            ? 'CRITICAL: Cash Balance Alert' 
            : 'Low Cash Balance Alert';

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Hello {$notifiable->user->name},")
            ->line($this->getAlertMessage())
            ->line("**Current Balance:** {$this->currentBalance} {$this->currency}")
            ->line("**Minimum Threshold:** {$this->minimumThreshold} {$this->currency}")
            ->action('Check Cash Position', url('/agent/cash'))
            ->line('Maintain adequate cash float to avoid disruptions.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'cash_position_alert',
            'title' => ucfirst(str_replace('_', ' ', $this->alertType)),
            'message' => "Low cash alert: Below minimum threshold of {$this->minimumThreshold} {$this->currency}",
            'alert_type' => $this->alertType,
            'current_balance' => $this->currentBalance,
            'minimum_threshold' => $this->minimumThreshold,
            'currency' => $this->currency,
        ];
    }

    private function getAlertMessage(): string
    {
        return match ($this->alertType) {
            'low_balance' => 'Your cash balance is running low.',
            'critical_balance' => 'Your cash balance is critically low. Immediate action required!',
            default => 'Your cash position needs attention.',
        };
    }
}
