<?php

namespace App\Notifications\Agent;

use App\Models\Transfer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewTransferAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Transfer $transfer,
        public string $senderName,
        public string $beneficiaryName,
        public float $senderAmount,
        public float $payoutAmount,
        public string $currencyFrom,
        public string $currencyTo
    ) {
        $this->queue = 'notifications';
    }

    public function via($notifiable): array
    {
        $prefs = $notifiable->getOrCreateNotificationPreference();
        
        if ($prefs->isMuted() || !$prefs->isNotificationTypeEnabled('transfer_initiated')) {
            return [];
        }

        // Agents always get urgent notifications on multiple channels
        return array_intersect($prefs->getEnabledChannels(), ['sms', 'push', 'in_app', 'dashboard']);
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New Transfer Assigned - {$this->transfer->reference}")
            ->greeting("Hello {$notifiable->user->name},")
            ->line("A new transfer has been assigned to you!")
            ->line("**Sender:** {$this->senderName}")
            ->line("**Recipient:** {$this->beneficiaryName}")
            ->line("**Collect:** {$this->senderAmount} {$this->currencyFrom}")
            ->line("**Pay Out:** {$this->payoutAmount} {$this->currencyTo}")
            ->line("**Reference:** {$this->transfer->reference}")
            ->action('View Transfer Details', url("/agent/transfers/{$this->transfer->id}"))
            ->line('Process this transfer in your agent dashboard.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'transfer_assigned',
            'title' => 'New Transfer Assigned',
            'message' => "Collect {$this->senderAmount} {$this->currencyFrom} from {$this->senderName}, pay {$this->payoutAmount} {$this->currencyTo} to {$this->beneficiaryName}",
            'transfer_id' => $this->transfer->id,
            'reference' => $this->transfer->reference,
            'sender' => $this->senderName,
            'beneficiary' => $this->beneficiaryName,
            'sender_amount' => $this->senderAmount,
            'payout_amount' => $this->payoutAmount,
            'currency_from' => $this->currencyFrom,
            'currency_to' => $this->currencyTo,
        ];
    }
}
