<?php

namespace App\Notifications\Agent;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommissionEarnedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public float $commissionAmount,
        public string $currency,
        public string $transferReference,
        public float $totalEarned
    ) {
        $this->queue = 'notifications';
    }

    public function via($notifiable): array
    {
        $prefs = $notifiable->getOrCreateNotificationPreference();
        
        if ($prefs->isMuted() || !$prefs->isNotificationTypeEnabled('commission_earned')) {
            return [];
        }

        return $prefs->getEnabledChannels();
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Commission Earned - {$this->commissionAmount} {$this->currency}")
            ->greeting("Hello {$notifiable->user->name},")
            ->line("You've earned a commission!")
            ->line("**Amount:** {$this->commissionAmount} {$this->currency}")
            ->line("**Transfer:** {$this->transferReference}")
            ->line("**Total Earned Today:** {$this->totalEarned} {$this->currency}")
            ->action('View Commissions', url('/agent/commissions'))
            ->line('Keep up the great work!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'commission_earned',
            'title' => 'Commission Earned',
            'message' => "You earned {$this->commissionAmount} {$this->currency} from transfer {$this->transferReference}",
            'commission' => $this->commissionAmount,
            'currency' => $this->currency,
            'transfer_reference' => $this->transferReference,
            'total_earned' => $this->totalEarned,
        ];
    }
}
