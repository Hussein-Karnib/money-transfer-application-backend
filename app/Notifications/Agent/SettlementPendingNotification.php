<?php

namespace App\Notifications\Agent;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SettlementPendingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public float $settlementAmount,
        public string $currency,
        public int $transferCount,
        public string $settlementDate
    ) {
        $this->queue = 'notifications';
    }

    public function via($notifiable): array
    {
        $prefs = $notifiable->getOrCreateNotificationPreference();
        
        if ($prefs->isMuted() || !$prefs->isNotificationTypeEnabled('settlement_alert')) {
            return [];
        }

        return $prefs->getEnabledChannels();
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Settlement Pending - {$this->settlementAmount} {$this->currency}")
            ->greeting("Hello {$notifiable->user->name},")
            ->line("You have a settlement pending!")
            ->line("**Amount:** {$this->settlementAmount} {$this->currency}")
            ->line("**Transfers:** {$this->transferCount}")
            ->line("**Settlement Date:** {$this->settlementDate}")
            ->action('View Settlement', url('/agent/settlements'))
            ->line('Your settlement will be processed on the scheduled date.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'settlement_alert',
            'title' => 'Settlement Pending',
            'message' => "{$this->settlementAmount} {$this->currency} waiting in settlement queue",
            'amount' => $this->settlementAmount,
            'currency' => $this->currency,
            'transfer_count' => $this->transferCount,
            'settlement_date' => $this->settlementDate,
        ];
    }
}
